<?php
require_once __DIR__.'/../includes/bootstrap.php';
member_required();
$member=current_member($pdo);
$msg='';$error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        $eventId=(int)($_POST['event_id']??0);
        $rsvp=(string)($_POST['rsvp']??'');
        if(!in_array($rsvp,['going','maybe','not_going'],true)) throw new RuntimeException('Invalid RSVP choice.');
        $q=$pdo->prepare("SELECT * FROM church_events WHERE id=? AND status='published' FOR UPDATE");
        $q->execute([$eventId]);$event=$q->fetch();
        if(!$event) throw new RuntimeException('That event is no longer available.');
        if($event['event_date']<date('Y-m-d')) throw new RuntimeException('RSVP is closed because this event has already passed.');
        if(!(int)$event['registration_required']) throw new RuntimeException('This event does not require an RSVP.');
        if($event['registration_deadline'] && strtotime($event['registration_deadline'])<time()) throw new RuntimeException('Registration for this event has closed.');
        $q=$pdo->prepare("INSERT INTO event_rsvps(event_id,member_id,status) VALUES(?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status),updated_at=CURRENT_TIMESTAMP");
        $q->execute([$eventId,(int)$member['id'],$rsvp]);
        $msg='Your RSVP has been updated to “'.ucwords(str_replace('_',' ',$rsvp)).'”.';
    }catch(Throwable $e){$error=$e instanceof RuntimeException?$e->getMessage():'Your RSVP could not be updated.';}
}

$q=$pdo->prepare("SELECT e.*,r.status AS my_rsvp
    FROM church_events e
    LEFT JOIN event_rsvps r ON r.event_id=e.id AND r.member_id=?
    WHERE e.status='published' AND e.event_date>=CURDATE()
    ORDER BY e.event_date ASC,e.start_time ASC");
$q->execute([(int)$member['id']]);$events=$q->fetchAll();

$page_title='Church Events';
require __DIR__.'/../includes/header.php';
?>
<div>
<?php require __DIR__.'/../includes/sidebar.php';?>
<main class="main">
<header class="topbar">
 <div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button><div><h1>Church Events</h1><p>See what's happening at FGCK Joyland and respond when registration is enabled.</p></div></div>
 <span class="live-pill"><?=count($events)?> upcoming</span>
</header>
<div class="content">
<?php if($msg):?><div class="alert alert-success small"><i class="bi bi-check-circle me-1"></i><?=e($msg)?></div><?php endif;?>
<?php if($error):?><div class="alert alert-danger small"><i class="bi bi-exclamation-triangle me-1"></i><?=e($error)?></div><?php endif;?>
<div class="message-hero mb-4"><div class="position-relative" style="z-index:1"><div class="small text-uppercase fw-bold mb-2" style="letter-spacing:1px;color:#f0c866">Church calendar</div><h2>Come together, grow together, serve together.</h2><p>Find worship services, Bible studies, prayer meetings, youth fellowships, outreach, conferences and special church gatherings in one place.</p></div></div>

<div class="row g-4">
<?php foreach($events as $ev):?>
<div class="col-lg-6">
<div class="event-card">
 <div class="d-flex gap-3">
  <div class="event-date-box"><div class="month"><?=e(date('M',strtotime($ev['event_date'])))?></div><div class="day"><?=e(date('d',strtotime($ev['event_date'])))?></div><div class="dow"><?=e(date('D',strtotime($ev['event_date'])))?></div></div>
  <div class="flex-grow-1">
   <div class="d-flex justify-content-between gap-2"><span class="event-category"><?=e(event_category_label($ev['category']))?></span><?php if($ev['my_rsvp']):?><span class="badge text-bg-success"><?=e(ucwords(str_replace('_',' ',$ev['my_rsvp'])))?></span><?php endif;?></div>
   <h3 class="fs-5 fw-bold mt-2 mb-1"><?=e($ev['title'])?></h3>
   <div class="small text-muted mb-2"><i class="bi bi-clock me-1"></i><?=e($ev['start_time']?fmt_time($ev['start_time']):'Time to be announced')?><?= $ev['end_time']?' – '.e(fmt_time($ev['end_time'])):'' ?><br><?php if($ev['venue']):?><i class="bi bi-geo-alt me-1"></i><?=e($ev['venue'])?><?php endif;?></div>
   <?php if($ev['description']):?><div class="detail-message small"><?=e($ev['description'])?></div><?php endif;?>
   <?php if($ev['registration_required']):?>
   <?php $registrationOpen=!$ev['registration_deadline']||strtotime($ev['registration_deadline'])>=time();?>
   <div class="mt-3 p-3 rounded-3 bg-light border">
    <div class="small fw-bold mb-2"><i class="bi bi-person-check me-1"></i>Member RSVP</div>
    <?php if($registrationOpen):?>
    <form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="event_id" value="<?=$ev['id']?>">
     <div class="d-flex flex-wrap gap-2">
      <label class="rsvp-choice"><input type="radio" name="rsvp" value="going" <?=$ev['my_rsvp']==='going'?'checked':''?>> Going</label>
      <label class="rsvp-choice"><input type="radio" name="rsvp" value="maybe" <?=$ev['my_rsvp']==='maybe'?'checked':''?>> Maybe</label>
      <label class="rsvp-choice"><input type="radio" name="rsvp" value="not_going" <?=$ev['my_rsvp']==='not_going'?'checked':''?>> Not going</label>
      <button class="btn btn-primary btn-sm">Save RSVP</button>
     </div>
    </form>
    <?php else:?><span class="text-muted small">Registration closed<?= $ev['registration_deadline']?' on '.e(date('d M Y, g:i A',strtotime($ev['registration_deadline']))):''?>.</span><?php endif;?>
   </div>
   <?php else:?><div class="mt-3 small text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>No RSVP required — everyone is welcome.</div><?php endif;?>
  </div>
 </div>
</div>
</div>
<?php endforeach;?>
<?php if(!$events):?><div class="col-12"><div class="cardx p-5 text-center"><i class="bi bi-calendar2-heart fs-1 text-muted"></i><h3 class="section-title mt-3">No upcoming events</h3><p class="text-muted small">New church activities published by the pastor will appear here.</p></div></div><?php endif;?>
</div>
</div>
</main>
</div>
<?php require __DIR__.'/../includes/footer.php';?>
