<?php
require_once __DIR__.'/../includes/bootstrap.php';
member_required();
$member=current_member($pdo);
$date=$_GET['date']??next_wednesday();
if(!is_wednesday($date)||$date<date('Y-m-d'))$date=next_wednesday();
$error='';$success='';
ensure_wednesday_slots($pdo,$date);
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$date=$_POST['appointment_date']??'';$sid=(int)($_POST['slot_id']??0);$purpose=trim($_POST['purpose']??'');$notes=trim($_POST['notes']??'');
 try{
  if(setting($pdo,'office_status','available')!=='available')throw new RuntimeException("The pastor's office is currently closed. Please check again later.");
  if(!is_wednesday($date)||$date<date('Y-m-d'))throw new RuntimeException('Please select a valid future Wednesday.');
  if($purpose==='')throw new RuntimeException('Please enter the purpose of your appointment.');
  $pdo->beginTransaction();
  [$open,$close,$slotMinutes]=appointment_schedule($pdo);
  $q=$pdo->prepare("SELECT s.* FROM appointment_slots s WHERE s.id=? AND s.appointment_date=? AND s.availability='available' AND s.start_time>=? AND s.end_time<=? FOR UPDATE");$q->execute([$sid,$date,$open.':00',$close.':00']);$slot=$q->fetch();
  if(!$slot)throw new RuntimeException('That slot is closed or no longer available.');
  $q=$pdo->prepare("SELECT id FROM appointments WHERE slot_id=? AND status IN('pending','confirmed') FOR UPDATE");$q->execute([$sid]);if($q->fetch())throw new RuntimeException('That slot has just been booked by another member.');
  $q=$pdo->prepare("SELECT a.id FROM appointments a JOIN appointment_slots s ON s.id=a.slot_id WHERE a.member_id=? AND a.status IN('pending','confirmed') AND s.appointment_date>=CURDATE() LIMIT 1 FOR UPDATE");$q->execute([$member['id']]);if($q->fetch())throw new RuntimeException('You already have an upcoming appointment.');
  $ref=appointment_no();$q=$pdo->prepare("INSERT INTO appointments(appointment_no,member_id,slot_id,purpose,notes) VALUES(?,?,?,?,?)");$q->execute([$ref,$member['id'],$sid,$purpose,$notes]);log_activity($pdo,$member['id'],null,'appointment_booked',"Appointment $ref booked");$appointmentId=(int)$pdo->lastInsertId();$pdo->commit();$success=$ref;
  // Email failures must never cancel a successful booking. The in-portal record remains authoritative.
  email_pastors_about_booking($pdo,$appointmentId);
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e->getMessage();}
}
ensure_wednesday_slots($pdo,$date);
[$open,$close,$slotMinutes]=appointment_schedule($pdo);
$slots=schedule_slots($pdo,$date);
$officeStatus=setting($pdo,'office_status','available');$bookingNotice=setting($pdo,'booking_notice','Please arrive a few minutes before your appointment.');
$page_title='Book Appointment';require __DIR__.'/../includes/header.php';
?>
<div>
    <?php require __DIR__.'/../includes/sidebar.php';?>
    <main class="main">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i
                        class="bi bi-list"></i></button>
                <div>
                    <h1>Book Appointment</h1>
                    <p>Choose an available Wednesday slot.</p>
                </div>
            </div>
        </header>
        <div class="content">
            <div id="officeStatusBox"
                class="alert <?= $officeStatus==='available'?'alert-success':'alert-danger' ?> d-flex align-items-center gap-2 py-3">
                <i class="bi <?= $officeStatus==='available'?'bi-check-circle':'bi-door-closed' ?> fs-5"></i>
                <div><strong id="officeStatusTitle">
                        <?= $officeStatus==='available'?"Pastor's Office is Open":"Pastor's Office is Closed" ?>
                    </strong>
                    <div class="small" id="officeStatusText">
                        <?= $officeStatus==='available'?e($bookingNotice):'Appointments are temporarily closed. Please check again later.' ?>
                    </div>
                </div>
            </div>

            <?php if($success):?>
            <div class="cardx p-5 text-center">
                <div class="brand-mark mx-auto mb-3"><i class="bi bi-check2"></i></div>
                <h2 class="section-title fs-4">Appointment request submitted</h2>
                <p class="text-muted small">Your reference number</p>
                <h3>
                    <?=e($success)?>
                </h3>
                <p class="small text-muted">The pastor can now review and confirm your appointment.</p><a
                    class="btn btn-primary" href="/fgck_joyland/member/appointments.php">View Appointment</a>
            </div>
            <?php else:?>
            <?php if($error):?>
            <div class="alert alert-danger">
                <?=e($error)?>
            </div>
            <?php endif;?>
            <div class="cardx p-4 mb-4">
                <div class="row align-items-end g-3">
                    <div class="col-md-5"><label class="form-label">Appointment Wednesday</label><input type="date"
                            class="form-control" id="datePicker" value="<?=e($date)?>" min="<?=e(date('Y-m-d'))?>">
                    </div>
                    <div class="col-md-7">
                        <div class="alert alert-info mb-0" id="durationNotice">Each appointment is 30 minutes. The
                            Pastor controls the opening time; all appointments end by 3:00 PM.</div>
                    </div>
                </div>
            </div>
            <div class="cardx p-4">
                <div class="d-flex justify-content-between mb-3">
                    <h3 class="section-title">Available time slots</h3><span class="small text-muted">
                        <?=e(fd($date))?>
                    </span>
                </div>
                <div id="slotsGrid" class="row g-3">
                    <?php foreach($slots as $s):?>
                    <div class="col-sm-6 col-lg-4">
                        <div class="slot <?=$s['booked']?'booked':($s['availability']!=='available'?'closed':'')?>">
                            <?php if($officeStatus!=='available'):?><span
                                class="badge text-bg-danger float-end">Closed</span>
                            <?php elseif($s['booked']):?><span class="badge text-bg-secondary float-end">Booked</span>
                            <?php elseif($s['availability']!=='available'):?><span
                                class="badge text-bg-danger float-end">Closed</span>
                            <?php else:?><span class="badge-soft float-end">Available</span>
                            <?php endif;?>
                            <div class="slot-time">
                                <?=e(ft($s['start_time']))?>
                            </div>
                            <div class="slot-duration">
                                <?=e(ft($s['start_time']))?> –
                                <?=e(ft($s['end_time']))?>
                            </div>
                            <?php if(!$s['booked'] && $s['availability']==='available' && $officeStatus==='available'):?><button
                                class="btn btn-primary btn-sm mt-3 w-100" data-slot="<?=$s['id']?>"
                                data-time="<?=e(ft($s['start_time']))?>" data-bs-toggle="modal"
                                data-bs-target="#bookingModal">Select Slot</button>
                            <?php endif;?>
                        </div>
                    </div>
                    <?php endforeach;?>
                </div>
            </div>
            <?php endif;?>
        </div>
    </main>
</div>
<div class="modal fade" id="bookingModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden"
                    name="appointment_date" value="<?=e($date)?>"><input type="hidden" name="slot_id" id="slotId">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm appointment</h5><button class="btn-close" data-bs-dismiss="modal"
                        type="button"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light"><b id="selectedTime"></b><br><small id="selectedDuration">Appointment
                            time controlled by the Pastor</small></div><label class="form-label">Purpose</label><input
                        class="form-control mb-3" name="purpose" maxlength="120"
                        placeholder="e.g. Counselling, prayer, guidance" required><label class="form-label">Additional
                        notes <span class="text-muted">(optional)</span></label><textarea class="form-control"
                        name="notes" rows="4"></textarea>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light"
                        data-bs-dismiss="modal">Back</button><button class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    const datePicker = document.getElementById('datePicker');
    datePicker?.addEventListener('change', e => {let d = new Date(e.target.value + 'T00:00:00'); if (d.getDay() !== 3) {alert('Please select a Wednesday.'); return } location.href = '?date=' + e.target.value});
    function bindSlotButtons() {document.querySelectorAll('[data-slot]').forEach(b => b.addEventListener('click', () => {document.getElementById('slotId').value = b.dataset.slot; document.getElementById('selectedTime').textContent = b.dataset.time;}));}
    bindSlotButtons();
    let lastSignature = '';
    async function syncAvailability() {try {const r = await fetch('/fgck_joyland/api/availability.php?date=<?=rawurlencode($date)?>', {cache: 'no-store'}); const d = await r.json(); if (!d.ok) return; if (lastSignature && d.signature !== lastSignature) {location.reload(); return } lastSignature = d.signature;} catch (e) { } }
    setInterval(syncAvailability, 5000); syncAvailability();
</script>
<?php require __DIR__.'/../includes/footer.php';?>