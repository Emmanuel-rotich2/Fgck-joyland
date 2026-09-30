<?php
require __DIR__.'/../includes/bootstrap.php';
staff_required();
$staff=staff($pdo);
$msg='';$error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $action=$_POST['action']??'';
    try{
        if($action==='create'){
            $title=trim((string)($_POST['title']??''));
            $message=trim((string)($_POST['message']??''));
            $type=(string)($_POST['type']??'announcement');
            $priority=(string)($_POST['priority']??'normal');
            $audience=(string)($_POST['audience']??'all');
            $status=(string)($_POST['status']??'published');
            $allowedTypes=['announcement','prayer','pastoral','urgent','reminder'];
            $allowedPriority=['normal','important','urgent'];
            $allowedAudience=['all','appointment_members'];
            $allowedStatus=['draft','published'];
            if($title===''||mb_strlen($title)>180) throw new RuntimeException('Please provide a message title up to 180 characters.');
            if($message==='') throw new RuntimeException('Please write the message you want members to receive.');
            if(!in_array($type,$allowedTypes,true)||!in_array($priority,$allowedPriority,true)||!in_array($audience,$allowedAudience,true)||!in_array($status,$allowedStatus,true)) throw new RuntimeException('One of the selected communication options is invalid.');

            $publishedAt=$status==='published'?date('Y-m-d H:i:s'):null;
            $pdo->beginTransaction();
            $q=$pdo->prepare("INSERT INTO announcements(user_id,title,message,type,priority,audience,status,published_at) VALUES(?,?,?,?,?,?,?,?)");
            $q->execute([(int)$staff['id'],$title,$message,$type,$priority,$audience,$status,$publishedAt]);
            $announcementId=(int)$pdo->lastInsertId();

            $recipients=0;$emails=0;
            if($status==='published'){
                $notificationType=$priority==='urgent'?'danger':($type==='prayer'||$type==='pastoral'?'info':'success');
                $recipients=notify_all_members($pdo,$title,$message,$notificationType,$audience==='appointment_members'?'appointment_members':null);

                $sql="SELECT full_name,email FROM members WHERE status='active' AND email<>''";
                if($audience==='appointment_members'){
                    $sql.=" AND EXISTS(SELECT 1 FROM appointments ap JOIN appointment_slots aps ON aps.id=ap.slot_id WHERE ap.member_id=members.id AND ap.status IN('pending','confirmed') AND aps.appointment_date>=CURDATE())";
                }
                $members=$pdo->query($sql)->fetchAll();
                $pdo->commit();

                $emailTitle='FGCK Joyland: '.$title;
                $emailBody='<p>Dear member,</p><p>'.nl2br(e($message)).'</p><p>Please sign in to your FGCK Joyland member portal for the full message and other church updates.</p>';
                foreach($members as $m){
                    if(send_system_email((string)$m['email'],(string)$m['full_name'],$emailTitle,$emailBody)) $emails++;
                }
                log_activity($pdo,null,(int)$staff['id'],'announcement_published','Published announcement #'.$announcementId.' to '.$recipients.' member(s). Emails sent: '.$emails);
                $msg='Message published successfully. '.$recipients.' member notification(s) created'.($emails?' and '.$emails.' email(s) sent.':'.');
            }else{
                $pdo->commit();
                log_activity($pdo,null,(int)$staff['id'],'announcement_draft','Saved announcement draft #'.$announcementId);
                $msg='Draft saved successfully. It has not been sent to members.';
            }
        }elseif($action==='archive'){
            $id=(int)($_POST['id']??0);
            $q=$pdo->prepare("UPDATE announcements SET status='archived' WHERE id=? AND status<>'archived'");
            $q->execute([$id]);
            log_activity($pdo,null,(int)$staff['id'],'announcement_archived','Archived announcement #'.$id);
            $msg=$q->rowCount()?'Announcement archived.':'Announcement was already archived or not found.';
        }else{
            throw new RuntimeException('Unknown communication action.');
        }
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        $error=$e instanceof RuntimeException?$e->getMessage():'The communication could not be completed.';
    }
}

$rows=$pdo->query("SELECT a.*,u.full_name AS author,
    (SELECT COUNT(*) FROM announcement_reads ar WHERE ar.announcement_id=a.id) AS read_count
    FROM announcements a LEFT JOIN users u ON u.id=a.user_id
    ORDER BY COALESCE(a.published_at,a.created_at) DESC LIMIT 30")->fetchAll();

$totalSent=(int)$pdo->query("SELECT COUNT(*) FROM announcements WHERE status='published'")->fetchColumn();
$drafts=(int)$pdo->query("SELECT COUNT(*) FROM announcements WHERE status='draft'")->fetchColumn();
$reads=(int)$pdo->query("SELECT COUNT(*) FROM announcement_reads ar JOIN announcements a ON a.id=ar.announcement_id WHERE a.status='published'")->fetchColumn();
$page_title='Communications Center';
require __DIR__.'/../includes/header.php';
?>
<div>
<?php require __DIR__.'/../includes/staff_sidebar.php';?>
<main class="main">
<header class="topbar">
 <div class="d-flex align-items-center gap-3">
  <button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button>
  <div><h1>Communications Center</h1><p>Share pastoral messages, reminders, prayer points and urgent church updates.</p></div>
 </div>
 <span class="live-pill">Member engagement</span>
</header>
<div class="content">
<?php if($msg):?><div class="alert alert-success small"><i class="bi bi-check-circle me-1"></i><?=e($msg)?></div><?php endif;?>
<?php if($error):?><div class="alert alert-danger small"><i class="bi bi-exclamation-triangle me-1"></i><?=e($error)?></div><?php endif;?>

<div class="message-hero mb-4">
 <div class="position-relative" style="z-index:1">
  <div class="small text-uppercase fw-bold mb-2" style="letter-spacing:1px;color:#f0c866">Pastoral communication</div>
  <h2>Keep the church informed, encouraged and connected.</h2>
  <p>Publish one message and the system places it in the members' portal. You can also send it to members' email addresses when email is configured.</p>
 </div>
</div>

<div class="row g-3 mb-4">
 <div class="col-6 col-lg-4"><div class="ministry-stat"><div class="num"><?=$totalSent?></div><div class="label">Published messages</div></div></div>
 <div class="col-6 col-lg-4"><div class="ministry-stat"><div class="num"><?=$drafts?></div><div class="label">Saved drafts</div></div></div>
 <div class="col-12 col-lg-4"><div class="ministry-stat"><div class="num"><?=$reads?></div><div class="label">Member reads recorded</div></div></div>
</div>

<div class="row g-4">
<div class="col-xl-5">
<div class="cardx p-4">
 <div class="d-flex justify-content-between align-items-start mb-3"><div><h3 class="section-title mb-1">Create a message</h3><p class="text-muted small mb-0">A clear subject and short, caring message works best.</p></div><i class="bi bi-megaphone fs-3 text-warning"></i></div>
 <form method="post">
  <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
  <input type="hidden" name="action" value="create">
  <div class="mb-3"><label class="form-label">Message title</label><input name="title" class="form-control" maxlength="180" required placeholder="e.g. Wednesday Prayer Meeting"></div>
  <div class="row g-3">
   <div class="col-md-6"><label class="form-label">Message type</label><select name="type" class="form-select">
    <option value="announcement">Announcement</option><option value="pastoral">Pastoral Message</option><option value="prayer">Prayer Point</option><option value="reminder">Reminder</option><option value="urgent">Urgent Notice</option>
   </select></div>
   <div class="col-md-6"><label class="form-label">Priority</label><select name="priority" class="form-select">
    <option value="normal">Normal</option><option value="important">Important</option><option value="urgent">Urgent</option>
   </select></div>
  </div>
  <div class="mb-3 mt-3"><label class="form-label">Send to</label><select name="audience" class="form-select">
   <option value="all">All active members</option><option value="appointment_members">Members with upcoming appointments</option>
  </select></div>
  <div class="mb-3"><label class="form-label">Message</label><textarea name="message" class="form-control" rows="8" required placeholder="Write the message, prayer point, reminder or encouragement here..."></textarea></div>
  <div class="d-flex flex-wrap gap-2">
   <button name="status" value="draft" class="btn btn-outline-secondary"><i class="bi bi-file-earmark me-1"></i>Save Draft</button>
   <button name="status" value="published" class="btn btn-primary"><i class="bi bi-send me-1"></i>Publish & Notify</button>
  </div>
  <div class="form-text mt-3"><i class="bi bi-info-circle me-1"></i> Publishing creates an in-portal notification. Email is sent when SMTP/email is enabled in your configuration.</div>
 </form>
</div>
</div>

<div class="col-xl-7">
<div class="cardx p-4">
 <div class="analytics-head"><div><h3>Communication history</h3><p>Recent messages and member engagement.</p></div></div>
 <div class="table-responsive">
 <table class="table table-clean align-middle">
  <thead><tr><th>Message</th><th>Audience</th><th>Reads</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach($rows as $r):?>
  <tr>
   <td><strong><?=e($r['title'])?></strong><br><small class="text-muted"><?=e(event_category_label($r['type']))?> · <?=e($r['published_at']?date('d M Y, g:i A',strtotime($r['published_at'])):date('d M Y, g:i A',strtotime($r['created_at'])))?></small></td>
   <td><?=e($r['audience']==='all'?'All members':'Appointment members')?></td>
   <td><span class="badge-soft"><?=e((string)$r['read_count'])?></span></td>
   <td><span class="badge text-bg-<?=$r['status']==='published'?'success':($r['status']==='draft'?'warning':'secondary')?>"><?=e(ucfirst($r['status']))?></span></td>
   <td>
    <?php if($r['status']!=='archived'):?><form method="post" onsubmit="return confirm('Archive this message?');"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="archive"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-archive"></i></button></form><?php endif;?>
   </td>
  </tr>
  <?php endforeach;?>
  <?php if(!$rows):?><tr><td colspan="5" class="empty">No communications have been created yet.</td></tr><?php endif;?>
  </tbody>
 </table>
 </div>
</div>
</div>
</div>
</div>
</main>
</div>
<?php require __DIR__.'/../includes/footer.php';?>
