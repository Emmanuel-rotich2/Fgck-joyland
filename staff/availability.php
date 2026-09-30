<?php require __DIR__.'/../includes/bootstrap.php';staff_required();$date=$_GET['date']??next_wednesday();if(!is_wednesday($date)||$date<date('Y-m-d'))$date=next_wednesday();ensure_wednesday_slots($pdo,$date);$msg='';if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$id=(int)$_POST['slot_id'];$state=$_POST['availability']==='available'?'available':'unavailable';$q=$pdo->prepare("SELECT s.id,CASE WHEN a.id IS NULL THEN 0 ELSE 1 END booked FROM appointment_slots s LEFT JOIN appointments a ON a.slot_id=s.id AND a.status IN('pending','confirmed') WHERE s.id=?");$q->execute([$id]);$slot=$q->fetch();if(!$slot){$msg='Slot not found.';}elseif($slot['booked']){$msg='Booked slots cannot be closed or reopened here.';}else{$pdo->prepare('UPDATE appointment_slots SET availability=? WHERE id=?')->execute([$state,$id]);$msg='Slot updated.';}}[$open,$close,$slotMinutes]=appointment_schedule($pdo);
$q=$pdo->prepare("SELECT s.*,a.appointment_no,a.status,m.full_name FROM appointment_slots s LEFT JOIN appointments a ON a.slot_id=s.id AND a.status IN('pending','confirmed') LEFT JOIN members m ON m.id=a.member_id WHERE s.appointment_date=? AND s.start_time>=? AND s.end_time<=? AND TIME_TO_SEC(TIMEDIFF(s.end_time,s.start_time))=1800 ORDER BY s.start_time");$q->execute([$date,$open.':00',$close.':00']);$rows=$q->fetchAll();$page_title='Slot Availability';require __DIR__.'/../includes/header.php';?>
<div>
    <?php require __DIR__.'/../includes/staff_sidebar.php';?>
    <main class="main">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i
                        class="bi bi-list"></i></button>
                <div>
                    <h1>Slot Availability</h1>
                    <p>Control individual 30-minute Wednesday slots. The schedule follows the Pastor's opening time and
                        always ends at 3:00 PM.</p>
                </div>
            </div>
        </header>
        <div class="content">
            <?php if($msg):?>
            <div class="alert alert-success small">
                <?=e($msg)?>
            </div>
            <?php endif;?>
            <div class="cardx p-4 mb-4"><label class="form-label">Wednesday</label><input id="d" class="form-control"
                    style="max-width:280px" type="date" value="<?=e($date)?>"></div>
            <div class="row g-3">
                <?php foreach($rows as $r):?>
                <div class="col-sm-6 col-lg-4">
                    <div
                        class="slot <?=($r['appointment_no']?'booked':($r['availability']==='available'?'available':'closed'))?>">
                        <span
                            class="badge <?= $r['appointment_no']?'text-bg-warning':($r['availability']==='available'?'text-bg-success':'text-bg-secondary')?> float-end">
                            <?=$r['appointment_no']?'Booked':ucfirst($r['availability'])?>
                        </span>
                        <div class="slot-time">
                            <?=e(fmt_time($r['start_time']))?>
                        </div>
                        <div class="slot-sub">
                            <?=e(fmt_time($r['start_time']))?> –
                            <?=e(fmt_time($r['end_time']))?>
                        </div>
                        <?php if($r['appointment_no']):?>
                        <div class="small mt-3"><b>
                                <?=e($r['full_name'])?>
                            </b><br>
                            <?=e($r['status'])?>
                        </div>
                        <?php else:?>
                        <form method="post" class="mt-3"><input type="hidden" name="csrf"
                                value="<?=e(csrf_token())?>"><input type="hidden" name="slot_id"
                                value="<?=$r['id']?>"><input type="hidden" name="availability"
                                value="<?=$r['availability']==='available'?'unavailable':'available'?>"><button
                                class="btn btn-sm <?=($r['availability']==='available'?'btn-outline-danger':'btn-primary')?> w-100">
                                <?=$r['availability']==='available'?'Close Slot':'Open Slot'?>
                            </button></form>
                        <?php endif;?>
                    </div>
                </div>
                <?php endforeach;?>
            </div>
        </div>
    </main>
</div>
<script>d.addEventListener('change', e => {let x = new Date(e.target.value + 'T00:00:00'); if (x.getDay() != 3) {alert('Select Wednesday.'); return } location.href = '?date=' + e.target.value});</script>
<?php require __DIR__.'/../includes/footer.php';?>