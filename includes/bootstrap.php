<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
require_once __DIR__.'/../config/database.php';
function e(?string $v):string{return htmlspecialchars($v??'',ENT_QUOTES,'UTF-8');}
function redirect(string $u):never{header("Location: $u");exit;}
function csrf_token():string{if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
function verify_csrf():void{if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')){http_response_code(419);exit('Security token expired.');}}
function member_required():void
{
    global $pdo;
    if (empty($_SESSION['member_id'])) redirect('/fgck_joyland/auth/login.php');
    if (!current_member($pdo)) {
        $_SESSION = [];
        session_destroy();
        redirect('/fgck_joyland/auth/login.php');
    }
}
function staff_required():void{if(empty($_SESSION['staff_id']))redirect('/fgck_joyland/staff/login.php');}
function current_member(PDO $p):?array{if(empty($_SESSION['member_id']))return null;$q=$p->prepare("SELECT * FROM members WHERE id=? AND status='active'");$q->execute([$_SESSION['member_id']]);return $q->fetch()?:null;}
function current_staff(PDO $p):?array{if(empty($_SESSION['staff_id']))return null;$q=$p->prepare("SELECT * FROM users WHERE id=? AND status='active'");$q->execute([$_SESSION['staff_id']]);return $q->fetch()?:null;}
function member(PDO $p):?array{return current_member($p);}
function staff(PDO $p):?array{return current_staff($p);}
function fd(string $d):string{return fmt_date($d);}
function ft(string $t):string{return fmt_time($t);}
function appointment_no():string{return ref_no();}
function is_wednesday(string $d):bool{return (int)date('N',strtotime($d))===3;}
function next_wednesday():string{$d=new DateTimeImmutable('today');$n=(int)$d->format('N');$x=(3-$n+7)%7;if($x===0)$x=7;return $d->modify("+$x days")->format('Y-m-d');}
function appointment_schedule(PDO $p): array
{
    $open = setting($p, 'opening_time', '09:00');
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $open)) {
        $open = '09:00';
    }

    // The closing time is deliberately NOT read from the database.
    // 3:00 PM is a business rule and cannot be changed from the UI.
    $close = '15:00';
    $duration = 30;

    return [$open, $close, $duration];
}

function ensure_wednesday_slots(PDO $p, string $date): void
{
    if (!is_wednesday($date) || $date < date('Y-m-d')) return;

    [$open, $close, $slotMinutes] = appointment_schedule($p);
    $start = new DateTimeImmutable($date . ' ' . $open . ':00');
    $end   = new DateTimeImmutable($date . ' ' . $close . ':00');

    // Remove every unbooked slot outside the current schedule. This means a
    // change from 9:00 AM to 11:00 AM immediately removes 9:00-11:00 slots.
    $cleanup = $p->prepare("DELETE s FROM appointment_slots s
        LEFT JOIN appointments a ON a.slot_id=s.id
        WHERE s.appointment_date=?
          AND a.id IS NULL
          AND (s.start_time < ? OR s.end_time > ? OR TIME_TO_SEC(TIMEDIFF(s.end_time,s.start_time)) <> ?)");
    $cleanup->execute([
        $date,
        $start->format('H:i:s'),
        $end->format('H:i:s'),
        $slotMinutes * 60
    ]);

    if ($start >= $end) return;

    $insert = $p->prepare("INSERT IGNORE INTO appointment_slots
        (appointment_date,start_time,end_time,availability)
        VALUES(?,?,?,'available')");

    for ($cursor = $start; $cursor < $end; $cursor = $cursor->modify("+{$slotMinutes} minutes")) {
        $next = $cursor->modify("+{$slotMinutes} minutes");
        if ($next > $end) break; // Never create a partial appointment.
        $insert->execute([$date, $cursor->format('H:i:s'), $next->format('H:i:s')]);
    }
}

function sync_future_wednesday_slots(PDO $p, int $weeks = 52): void
{
    $today = new DateTimeImmutable('today');
    $cursor = $today;
    $daysUntilWednesday = (3 - (int)$cursor->format('N') + 7) % 7;
    if ($daysUntilWednesday === 0) $daysUntilWednesday = 0;
    $cursor = $cursor->modify('+' . $daysUntilWednesday . ' days');

    for ($i = 0; $i < $weeks; $i++) {
        ensure_wednesday_slots($p, $cursor->format('Y-m-d'));
        $cursor = $cursor->modify('+7 days');
    }
}

function rebuild_day_queue(PDO $p, string $date, ?int $fromAppointmentId = null): void
{
    // Keep active appointments in their booking order. If the pastor changes one
    // appointment's duration/time, later appointments are moved forward/backward
    // automatically so members never receive overlapping or stale times.
    $q=$p->prepare("SELECT a.id,a.slot_id,a.status,a.adjusted_start_time,a.adjusted_end_time,
                           s.start_time AS slot_start,s.end_time AS slot_end
                    FROM appointments a
                    JOIN appointment_slots s ON s.id=a.slot_id
                    WHERE s.appointment_date=? AND a.status IN('pending','confirmed')
                    ORDER BY COALESCE(a.adjusted_start_time,s.start_time), a.id");
    $q->execute([$date]);
    $rows=$q->fetchAll();
    if(!$rows)return;

    [$open,$close,$minutes]=appointment_schedule($p);
    $cursor=new DateTimeImmutable($date.' '.$open.':00');
    $closeTs=new DateTimeImmutable($date.' '.$close.':00');
    $anchorFound = ($fromAppointmentId===null);

    foreach($rows as $r){
        $originalStart=$r['adjusted_start_time'] ?: $r['slot_start'];
        $originalEnd=$r['adjusted_end_time'] ?: $r['slot_end'];
        if(!$anchorFound){
            if((int)$r['id'] !== $fromAppointmentId) continue;
            $start=new DateTimeImmutable($date.' '.$originalStart);
            $end=new DateTimeImmutable($date.' '.$originalEnd);
            if($end <= $start) $end=$start->modify("+{$minutes} minutes");
            $cursor=$end;
            $anchorFound=true;
            continue;
        }

        // Before an anchor, leave the appointment untouched. After an anchor,
        // rebuild a clean 30-minute queue from the previous appointment's end.
        $start=$cursor;
        $end=$start->modify("+{$minutes} minutes");
        if($end>$closeTs) {
            // Keep the appointment record but do not create an impossible time.
            continue;
        }
        $newStart=$start->format('H:i:s');
        $newEnd=$end->format('H:i:s');
        if($r['adjusted_start_time'] !== $newStart || $r['adjusted_end_time'] !== $newEnd){
            $u=$p->prepare("UPDATE appointments SET adjusted_start_time=?, adjusted_end_time=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $u->execute([$newStart,$newEnd,$r['id']]);
            notify_member($p,(int)($p->query("SELECT member_id FROM appointments WHERE id=".(int)$r['id'])->fetchColumn()),
                'Appointment queue updated',
                'Your appointment time has been automatically adjusted because an earlier conversation time changed. Your new time is '.fmt_time($newStart).' – '.fmt_time($newEnd).'.', 'info');
        }
        $cursor=$end;
    }
}

function live_queue_info(PDO $p, int $memberId, string $date): array
{
    $q=$p->prepare("SELECT a.id,a.member_id,a.appointment_no,a.status,
                           COALESCE(a.adjusted_start_time,s.start_time) AS start_time,
                           COALESCE(a.adjusted_end_time,s.end_time) AS end_time,
                           s.appointment_date,m.full_name
                    FROM appointments a
                    JOIN appointment_slots s ON s.id=a.slot_id
                    JOIN members m ON m.id=a.member_id
                    WHERE s.appointment_date=? AND a.status IN('pending','confirmed')
                    ORDER BY COALESCE(a.adjusted_start_time,s.start_time), a.id");
    $q->execute([$date]); $rows=$q->fetchAll();
    $mine=null; $position=0; $current=null;
    $now=time();
    foreach($rows as $i=>$r){
        if((int)$r['member_id']===$memberId){$mine=$r;$position=$i+1;break;}
    }
    foreach($rows as $r){
        $st=strtotime($date.' '.$r['start_time']); $en=strtotime($date.' '.$r['end_time']);
        if($now >= $st && $now < $en){$current=$r;break;}
    }
    if(!$mine)return ['has_appointment'=>false];
    $mineStart=strtotime($date.' '.$mine['start_time']);
    $mineEnd=strtotime($date.' '.$mine['end_time']);
    $waiting=max(0,$position-1);
    $expected=$mineStart;
    if($mineStart>$now && $current && $current['member_id']!=$memberId){$expected=strtotime($date.' '.$current['end_time']);}
    return [
        'has_appointment'=>true,'position'=>$position,'waiting_count'=>$waiting,
        'start_time'=>$mine['start_time'],'end_time'=>$mine['end_time'],
        'expected_timestamp'=>$expected,'is_current'=>(int)$mine['member_id']===$memberId && $now >= $mineStart && $now < $mineEnd,
        'current_end'=>$current['end_time']??null,
        'current_is_other'=>($current && (int)$current['member_id']!==$memberId),
    ];
}

function schedule_slots(PDO $p, string $date): array
{
    ensure_wednesday_slots($p, $date);
    [$open, $close] = appointment_schedule($p);

    $q = $p->prepare("SELECT s.*, CASE WHEN a.id IS NULL THEN 0 ELSE 1 END AS booked
        FROM appointment_slots s
        LEFT JOIN appointments a ON a.slot_id=s.id AND a.status IN ('pending','confirmed')
        WHERE s.appointment_date=?
          AND s.start_time>=?
          AND s.end_time<=?
          AND TIME_TO_SEC(TIMEDIFF(s.end_time,s.start_time))=1800
        ORDER BY s.start_time");
    $q->execute([$date, $open . ':00', $close . ':00']);
    return $q->fetchAll();
}
function fmt_date(string $d):string{return date('l, d M Y',strtotime($d));} function fmt_time(string $t):string{return date('g:i A',strtotime($t));}
function ref_no():string{return 'FGCK-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));}
function log_activity(PDO $p,?int $mid,?int $uid,string $a,string $d=''):void{$q=$p->prepare("INSERT INTO activity_logs(member_id,user_id,action,description,ip_address,user_agent) VALUES(?,?,?,?,?,?)");$q->execute([$mid,$uid,$a,$d,$_SERVER['REMOTE_ADDR']??'',substr($_SERVER['HTTP_USER_AGENT']??'',0,255)]);}
function notify_member(PDO $p,int $id,string $title,string $message,string $type='info'):void{$q=$p->prepare("INSERT INTO notifications(member_id,title,message,type) VALUES(?,?,?,?)");$q->execute([$id,$title,$message,$type]);}
function setting(PDO $p,string $k,string $default=''):string{$q=$p->prepare("SELECT setting_value FROM settings WHERE setting_key=?");$q->execute([$k]);return (string)($q->fetchColumn()??$default);}

function unread_notification_count(PDO $p, int $memberId): int
{
    $q=$p->prepare("SELECT COUNT(*) FROM notifications WHERE member_id=? AND is_read=0");
    $q->execute([$memberId]);
    return (int)$q->fetchColumn();
}

function unread_announcement_count(PDO $p, int $memberId): int
{
    $q=$p->prepare("SELECT COUNT(*) FROM announcements a
        LEFT JOIN announcement_reads ar ON ar.announcement_id=a.id AND ar.member_id=?
        WHERE a.status='published' AND (a.audience='all' OR EXISTS(
            SELECT 1 FROM appointments ap
            JOIN appointment_slots aps ON aps.id=ap.slot_id
            WHERE ap.member_id=? AND ap.status IN('pending','confirmed')
              AND aps.appointment_date>=CURDATE()
        )) AND ar.id IS NULL");
    $q->execute([$memberId,$memberId]);
    return (int)$q->fetchColumn();
}

function upcoming_event_count(PDO $p): int
{
    return (int)$p->query("SELECT COUNT(*) FROM church_events WHERE status='published' AND event_date>=CURDATE()")->fetchColumn();
}

function event_category_label(string $category): string
{
    return ucwords(str_replace('_',' ',$category));
}

function notify_all_members(PDO $p, string $title, string $message, string $type='info', ?string $audience=null): int
{
    $sql = "SELECT id FROM members WHERE status='active'";
    if ($audience === 'appointment_members') {
        $sql .= " AND EXISTS(
            SELECT 1 FROM appointments ap
            JOIN appointment_slots aps ON aps.id=ap.slot_id
            WHERE ap.member_id=members.id AND ap.status IN('pending','confirmed')
              AND aps.appointment_date>=CURDATE()
        )";
    }
    $ids = $p->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    if (!$ids) return 0;
    $q = $p->prepare("INSERT INTO notifications(member_id,title,message,type) VALUES(?,?,?,?)");
    foreach ($ids as $id) $q->execute([(int)$id,$title,$message,$type]);
    return count($ids);
}

// Email notifications are loaded after all shared helper functions are declared.
require_once __DIR__.'/email.php';
