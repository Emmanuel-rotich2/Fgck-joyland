<?php require_once __DIR__.'/../includes/bootstrap.php';member_required();$member=current_member($pdo);if(!$member){$_SESSION=[];session_destroy();redirect('/fgck_joyland/auth/login.php');}$msg='';$error='';if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$id=(int)($_POST['id']??0);try{$pdo->beginTransaction();$q=$pdo->prepare("SELECT a.*,s.appointment_date,s.start_time FROM appointments a JOIN appointment_slots s ON s.id=a.slot_id WHERE a.id=? AND a.member_id=? AND a.status IN('pending','confirmed') FOR UPDATE");$q->execute([$id,$member['id']]);$a=$q->fetch();if(!$a || $a['appointment_date']<date('Y-m-d'))throw new RuntimeException('That appointment can no longer be cancelled.');$pdo->prepare("UPDATE appointments SET status='cancelled',cancelled_by='member',cancelled_at=NOW() WHERE id=?")->execute([$id]);notify_member($pdo,$member['id'],'Appointment cancelled',"{$a['appointment_no']} has been cancelled.",'warning');log_activity($pdo,$member['id'],null,'appointment_cancelled',"Appointment {$a['appointment_no']} cancelled by member");$pdo->commit();$msg='Appointment cancelled successfully.';}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e instanceof RuntimeException?$e->getMessage():'Cancellation could not be completed.';}}$q=$pdo->prepare("SELECT a.*,s.appointment_date,COALESCE(a.adjusted_start_time,s.start_time) AS start_time,COALESCE(a.adjusted_end_time,s.end_time) AS end_time FROM appointments a JOIN appointment_slots s ON s.id=a.slot_id WHERE a.member_id=? ORDER BY s.appointment_date DESC,s.start_time DESC");$q->execute([$member['id']]);$rows=$q->fetchAll();$liveDate=null; foreach($rows as $rr){if(in_array($rr['status'],['pending','confirmed'],true) && $rr['appointment_date']>=date('Y-m-d')){$liveDate=$rr['appointment_date'];break;}} $queue=$liveDate?live_queue_info($pdo,(int)$member['id'],$liveDate):['has_appointment'=>false];$page_title='My Appointments';require __DIR__.'/../includes/header.php';?>
<div>
    <?php require __DIR__.'/../includes/sidebar.php';?>
    <main class="main">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i
                        class="bi bi-list"></i></button>
                <div>
                    <h1>My Appointments</h1>
                    <p>Track your requests and history.</p>
                </div>
            </div><a class="btn btn-primary btn-sm" href="/fgck_joyland/member/book.php">New Booking</a>
        </header>
        <div class="content">
            <?php if($msg):?>
            <div class="alert alert-success">
                <?=e($msg)?>
            </div>
            <?php endif;?>
            <?php if($error):?>
            <div class="alert alert-danger">
                <?=e($error)?>
            </div>
            <?php endif;?>
            <?php if($queue['has_appointment']): ?>
            <div id="liveQueue" class="cardx p-4 mb-4 border-start border-4 border-primary">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Live appointment queue</div>
                        <h3 class="section-title fs-5 mb-1">Your expected time: <span id="queueTime">
                                <?=e(ft($queue['start_time']))?> –
                                <?=e(ft($queue['end_time']))?>
                            </span></h3>
                        <div id="queueMessage" class="text-muted small">
                            <?php if($queue['is_current']): ?>Your conversation is currently in progress.
                            <?php elseif($queue['current_is_other']): ?>Another conversation is currently in progress
                            and is expected to finish at <strong>
                                <?=e(ft($queue['current_end']))?>
                            </strong>. Your turn will follow the updated queue.
                            <?php else: ?>There are <strong>
                                <?=e((string)$queue['waiting_count'])?>
                            </strong> appointment(s) before yours.
                            <?php endif; ?>
                        </div>
                    </div><span id="queueBadge" class="badge text-bg-info">Position #
                        <?=e((string)$queue['position'])?>
                    </span>
                </div>
                <div class="mt-3 p-3 rounded-3 bg-light"><i class="bi bi-info-circle me-1"></i> The pastor may adjust an
                    appointment while a conversation is in progress. If that happens, the following appointment times
                    are automatically recalculated and this page updates.
                </div>
            </div>
            <?php endif; ?>
            <div class="cardx p-3 p-md-4 table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Date & Time</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!$rows):?>
                        <tr>
                            <td colspan="5">
                                <div class="empty">No appointments found.</div>
                            </td>
                        </tr>
                        <?php else:foreach($rows as $r):?>
                        <tr>
                            <td><b>
                                    <?=e($r['appointment_no'])?>
                                </b></td>
                            <td>
                                <?=e(fd($r['appointment_date']))?><br><span class="text-muted">
                                    <?=e(ft($r['start_time']))?> –
                                    <?=e(ft($r['end_time']))?>
                                </span>
                            </td>
                            <td>
                                <?=e($r['purpose'])?>
                            </td>
                            <td><span
                                    class="badge text-bg-<?=in_array($r['status'],['confirmed','completed'])?'success':($r['status']==='cancelled'||$r['status']==='declined'?'secondary':'warning')?>">
                                    <?=e(ucfirst($r['status']))?>
                                </span></td>
                            <td>
                                <?php if(in_array($r['status'],['pending','confirmed'],true) && $r['appointment_date']>=date('Y-m-d')):?>
                                <form method="post" onsubmit="return confirm('Cancel this appointment?');"><input
                                        type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden"
                                        name="id" value="<?=$r['id']?>"><button
                                        class="btn btn-sm btn-outline-danger">Cancel</button></form>
                                <?php else:?><span class="text-muted">—</span>
                                <?php endif;?>
                            </td>
                        </tr>
                        <?php endforeach;endif;?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php if($liveDate):?>
<script>(function () {let sig = ''; async function sync() {try {const r = await fetch('/fgck_joyland/api/availability.php?date=<?=rawurlencode($liveDate)?>&member_queue=1', {cache: 'no-store'}); const d = await r.json(); if (!d.ok) return; if (sig && sig !== d.signature) {location.reload(); return } sig = d.signature; if (d.queue && d.queue.has_appointment) {const t = document.getElementById('queueTime'); const m = document.getElementById('queueMessage'); const b = document.getElementById('queueBadge'); if (t) t.textContent = d.queue.start_formatted + ' – ' + d.queue.end_formatted; if (b) b.textContent = 'Position #' + d.queue.position; if (m) {if (d.queue.is_current) m.innerHTML = 'Your conversation is currently in progress.'; else if (d.queue.current_is_other) m.innerHTML = 'Another conversation is currently in progress and is expected to finish at <strong>' + d.queue.current_end_formatted + '</strong>. Your turn will follow the updated queue.'; else m.innerHTML = 'There are <strong>' + d.queue.waiting_count + '</strong> appointment(s) before yours.';} } } catch (e) { } } setInterval(sync, 3000); sync();})();</script>
<?php endif;?>
<?php require __DIR__.'/../includes/footer.php';?>