<?php
require __DIR__.'/../includes/bootstrap.php';
staff_required();
$staff = staff($pdo);
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'adjust_time') {
        $start = trim((string)($_POST['start_time'] ?? ''));
        $end   = trim((string)($_POST['end_time'] ?? ''));

        try {
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $start) ||
                !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $end)) {
                throw new RuntimeException('Enter a valid start and end time.');
            }

            $pdo->beginTransaction();

            $q = $pdo->prepare("SELECT a.*, m.full_name, m.id AS member_id,
                    s.appointment_date, s.start_time AS slot_start, s.end_time AS slot_end
                    FROM appointments a
                    JOIN members m ON m.id = a.member_id
                    JOIN appointment_slots s ON s.id = a.slot_id
                    WHERE a.id = ? FOR UPDATE");
            $q->execute([$id]);
            $a = $q->fetch();

            if (!$a) throw new RuntimeException('Appointment not found.');
            if (!in_array($a['status'], ['pending','confirmed'], true)) {
                throw new RuntimeException('Only pending or confirmed appointments can be adjusted.');
            }
            if ($a['appointment_date'] < date('Y-m-d')) {
                throw new RuntimeException('Past appointments cannot be adjusted.');
            }
            if (!is_wednesday($a['appointment_date'])) {
                throw new RuntimeException('Appointments can only be adjusted on Wednesday.');
            }

            $startTs = strtotime($a['appointment_date'].' '.$start);
            $endTs   = strtotime($a['appointment_date'].' '.$end);
            if ($endTs <= $startTs) throw new RuntimeException('End time must be after start time.');

            $minutes = (int)(($endTs - $startTs) / 60);
            if ($minutes < 5 || $minutes > 120) {
                throw new RuntimeException('Appointment duration must be between 5 and 120 minutes.');
            }
            if (((int)date('i', $startTs) % 5) !== 0 || ((int)date('i', $endTs) % 5) !== 0) {
                throw new RuntimeException('Use times in 5-minute increments, for example 10:00 or 10:15.');
            }

            $open  = setting($pdo, 'opening_time', '09:00');
            $close = '15:00'; // Fixed appointment closing time
            if ($start < $open || $end > $close) {
                throw new RuntimeException("Adjusted time must stay between $open and $close.");
            }

            // Do not allow the adjusted appointment to overlap another active appointment.
            $q = $pdo->prepare("SELECT a.appointment_no
                FROM appointments a
                JOIN appointment_slots s ON s.id = a.slot_id
                WHERE a.id <> ?
                  AND a.status IN ('pending','confirmed')
                  AND s.appointment_date = ?
                  AND COALESCE(a.adjusted_start_time, s.start_time) < ?
                  AND COALESCE(a.adjusted_end_time, s.end_time) > ?
                FOR UPDATE");
            $q->execute([$id, $a['appointment_date'], $end.':00', $start.':00']);
            if ($conflict = $q->fetch()) {
                throw new RuntimeException('The new time overlaps another member appointment ('.$conflict['appointment_no'].').');
            }

            // Store the adjustment on the appointment itself. The original slot remains
            // the booking/availability record and is never destroyed by an adjustment.
            $oldStart = $a['adjusted_start_time'] ?: $a['slot_start'];
            $oldEnd   = $a['adjusted_end_time'] ?: $a['slot_end'];

            $q = $pdo->prepare("UPDATE appointments
                SET adjusted_start_time = ?, adjusted_end_time = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?");
            $q->execute([$start.':00', $end.':00', $id]);

            // Rebuild the queue immediately. All later members receive new times
            // based on the adjusted appointment's new end time.
            rebuild_day_queue($pdo, $a['appointment_date'], $id);

            notify_member(
                $pdo,
                (int)$a['member_id'],
                'Appointment time adjusted',
                $a['appointment_no'].': your appointment has been changed from '.fmt_time($oldStart).' – '.fmt_time($oldEnd).' to '.fmt_time($start).' – '.fmt_time($end).'. Please check your member portal.',
                'info'
            );
            log_activity(
                $pdo,
                null,
                $staff['id'],
                'appointment_time_adjusted',
                $a['appointment_no'].' '.$oldStart.'-'.$oldEnd.' -> '.$start.'-'.$end
            );

            $pdo->commit();
            // Email is sent after the database update is committed.
            email_member_time_change($pdo, $id, $oldStart, $oldEnd);
            $msg = 'Appointment time updated successfully. The member portal will show the new time automatically.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'The appointment time could not be updated.';
        }
    } else {
        $map = [
            'confirm'  => ['confirmed','Appointment confirmed.','success'],
            'decline'  => ['declined','Appointment declined.','warning'],
            'complete' => ['completed','Appointment completed.','success'],
            'cancel'   => ['cancelled','Appointment cancelled.','warning'],
            'no_show'  => ['no_show','Appointment marked as no-show.','warning']
        ];
        $allowed = [
            'pending'   => ['confirmed','declined','cancelled'],
            'confirmed' => ['completed','no_show','cancelled']
        ];

        if (isset($map[$action])) {
            [$status,$text,$type] = $map[$action];
            $q = $pdo->prepare("SELECT a.*, m.id AS member_id FROM appointments a JOIN members m ON m.id=a.member_id WHERE a.id=?");
            $q->execute([$id]);
            $a = $q->fetch();

            if (!$a) {
                $error = 'Appointment not found.';
            } elseif (!in_array($status, $allowed[$a['status']] ?? [], true)) {
                $error = 'That appointment cannot be moved to the selected status.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $fields = 'status=?';
                    $params = [$status];
                    if ($status === 'confirmed') $fields .= ',confirmed_at=NOW()';
                    if ($status === 'completed') $fields .= ',completed_at=NOW()';
                    if ($status === 'cancelled') $fields .= ",cancelled_by='pastor',cancelled_at=NOW()";
                    $params[] = $id;
                    $pdo->prepare("UPDATE appointments SET $fields WHERE id=?")->execute($params);
                    notify_member($pdo,(int)$a['member_id'],'Appointment update',$a['appointment_no'].': '.$text,$type);
                    log_activity($pdo,null,$staff['id'],'appointment_status',$a['appointment_no'].' -> '.$status);
                    $pdo->commit();
                    // The portal notification is immediate; email is an additional notification.
                    email_member_status_update($pdo, $id, $status);
                    $msg = $text;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $error = 'The appointment could not be updated.';
                }
            }
        }
    }
}

$filter = $_GET['status'] ?? 'all';
$validFilters = ['all','pending','confirmed','completed','cancelled','declined','no_show'];
if (!in_array($filter,$validFilters,true)) $filter='all';

$sql = "SELECT a.*,m.full_name,m.phone,m.email,s.appointment_date,
        s.start_time AS slot_start,s.end_time AS slot_end,
        COALESCE(a.adjusted_start_time,s.start_time) AS display_start,
        COALESCE(a.adjusted_end_time,s.end_time) AS display_end
        FROM appointments a
        JOIN members m ON m.id=a.member_id
        JOIN appointment_slots s ON s.id=a.slot_id";
$params=[];
if ($filter !== 'all') { $sql .= ' WHERE a.status=?'; $params[]=$filter; }
$sql .= ' ORDER BY s.appointment_date DESC, display_start DESC';
$q=$pdo->prepare($sql);$q->execute($params);$rows=$q->fetchAll();

$page_title='Appointments';
require __DIR__.'/../includes/header.php';
?>
<div>
<?php require __DIR__.'/../includes/staff_sidebar.php'; ?>
<main class="main">
<header class="topbar">
    <div class="d-flex align-items-center gap-3">
        <button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button>
        <div><h1>Appointments</h1><p>Manage requests, confirmations and appointment times.</p></div>
    </div>
</header>
<div class="content">
<div class="cardx p-4">
<?php if($msg): ?><div class="alert alert-success small"><?=e($msg)?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger small"><?=e($error)?></div><?php endif; ?>
<div class="d-flex gap-2 flex-wrap mb-3">
<?php foreach($validFilters as $f): ?>
<a class="btn btn-sm <?=($filter===$f?'btn-primary':'btn-outline-secondary')?>" href="?status=<?=$f?>"><?=ucwords(str_replace('_',' ',$f))?></a>
<?php endforeach; ?>
</div>
<div class="table-responsive">
<table class="table align-middle">
<thead><tr><th>Member</th><th>Appointment</th><th>Purpose</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($rows as $a): ?>
<tr>
<td><b><?=e($a['full_name'])?></b><br><small><?=e($a['phone'])?></small></td>
<td>
<?=e(fmt_date($a['appointment_date']))?><br>
<strong><?=e(fmt_time($a['display_start']))?> – <?=e(fmt_time($a['display_end']))?></strong>
<?php if($a['adjusted_start_time']): ?><br><small class="text-primary"><i class="bi bi-pencil-square"></i> Adjusted by pastor</small><?php endif; ?>
<br><small><?=e($a['appointment_no'])?></small>
</td>
<td><?=e($a['purpose'])?></td>
<td><?=e(ucwords(str_replace('_',' ',$a['status'])))?></td>
<td>
<?php if(in_array($a['status'],['pending','confirmed'],true)): ?>
    <?php if($a['status']==='pending'): ?>
    <form method="post" class="d-inline">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <input type="hidden" name="id" value="<?=$a['id']?>">
        <button name="action" value="confirm" class="btn btn-sm btn-success">Confirm</button>
        <button name="action" value="decline" class="btn btn-sm btn-outline-danger">Decline</button>
        <button name="action" value="cancel" class="btn btn-sm btn-outline-secondary">Cancel</button>
    </form>
    <?php else: ?>
    <form method="post" class="d-inline">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <input type="hidden" name="id" value="<?=$a['id']?>">
        <button name="action" value="complete" class="btn btn-sm btn-primary">Complete</button>
        <button name="action" value="no_show" class="btn btn-sm btn-outline-secondary">No-show</button>
        <button name="action" value="cancel" class="btn btn-sm btn-outline-danger">Cancel</button>
    </form>
    <?php endif; ?>
    <button type="button" class="btn btn-sm btn-outline-primary mt-1 adjust-btn"
        data-bs-toggle="modal" data-bs-target="#adjustModal"
        data-id="<?=$a['id']?>"
        data-member="<?=e($a['full_name'])?>"
        data-start="<?=e(substr($a['display_start'],0,5))?>"
        data-end="<?=e(substr($a['display_end'],0,5))?>">
        <i class="bi bi-clock-history"></i> Adjust Time
    </button>
<?php else: ?><span class="text-muted">—</span><?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="5" class="empty">No appointments found.</td></tr><?php endif; ?>
</tbody></table></div>
</div></div>
</main></div>

<!-- TIME ADJUSTMENT MODAL -->
<div class="modal fade" id="adjustModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content border-0 rounded-4 shadow">
<form method="post" id="adjustForm">
<input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<input type="hidden" name="id" id="adjustId">
<input type="hidden" name="action" value="adjust_time">
<div class="modal-header">
    <div><h5 class="modal-title mb-1">Adjust Appointment Time</h5><div class="small text-muted" id="adjustMember"></div></div>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="alert alert-info small">The member will automatically see the new time in their portal. You can shorten or extend the appointment in 5-minute increments.</div>
    <div class="row g-3">
        <div class="col-6"><label class="form-label">Start time</label><input type="time" class="form-control" name="start_time" id="adjustStart" step="300" required></div>
        <div class="col-6"><label class="form-label">End time</label><input type="time" class="form-control" name="end_time" id="adjustEnd" step="300" required></div>
    </div>
    <div class="small text-muted mt-3" id="adjustDuration"></div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-primary" id="saveAdjustment"><i class="bi bi-check2-circle"></i> Save New Time</button>
</div>
</form>
</div></div></div>

<script>
(function(){
    const modal=document.getElementById('adjustModal');
    const id=document.getElementById('adjustId');
    const member=document.getElementById('adjustMember');
    const start=document.getElementById('adjustStart');
    const end=document.getElementById('adjustEnd');
    const duration=document.getElementById('adjustDuration');
    const form=document.getElementById('adjustForm');
    const save=document.getElementById('saveAdjustment');

    function updateDuration(){
        if(!start.value || !end.value){duration.textContent='';return;}
        const a=start.value.split(':').map(Number), b=end.value.split(':').map(Number);
        const mins=(b[0]*60+b[1])-(a[0]*60+a[1]);
        duration.textContent=mins>0 ? ('Duration: '+mins+' minutes') : 'End time must be after start time.';
        duration.className='small mt-3 '+(mins>0&&mins<=120?'text-success':'text-danger');
    }
    [start,end].forEach(x=>x.addEventListener('input',updateDuration));

    document.querySelectorAll('.adjust-btn').forEach(btn=>btn.addEventListener('click',()=>{
        id.value=btn.dataset.id;
        member.textContent=btn.dataset.member+' • Current: '+btn.dataset.start+' – '+btn.dataset.end;
        start.value=btn.dataset.start;
        end.value=btn.dataset.end;
        updateDuration();
    }));

    form.addEventListener('submit',()=>{
        save.disabled=true;
        save.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
    });
})();
</script>
<?php require __DIR__.'/../includes/footer.php'; ?>
