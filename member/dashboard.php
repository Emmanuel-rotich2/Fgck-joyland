<?php
require_once __DIR__.'/../includes/bootstrap.php';
member_required();
$member=current_member($pdo);
$firstName=explode(' ',trim($member['full_name']??'Member'))[0];

$q=$pdo->prepare("SELECT a.*,s.appointment_date,COALESCE(a.adjusted_start_time,s.start_time) AS start_time,COALESCE(a.adjusted_end_time,s.end_time) AS end_time
 FROM appointments a JOIN appointment_slots s ON s.id=a.slot_id
 WHERE a.member_id=? AND a.status IN('pending','confirmed') AND s.appointment_date>=CURDATE()
 ORDER BY s.appointment_date,start_time LIMIT 1");
$q->execute([$member['id']]);$up=$q->fetch();

$q=$pdo->prepare('SELECT COUNT(*) FROM appointments WHERE member_id=?');$q->execute([$member['id']]);$total=(int)$q->fetchColumn();
$q=$pdo->prepare("SELECT COUNT(*) FROM appointments WHERE member_id=? AND status='completed'");$q->execute([$member['id']]);$done=(int)$q->fetchColumn();
$q=$pdo->prepare("SELECT COUNT(*) FROM appointments WHERE member_id=? AND status='cancelled'");$q->execute([$member['id']]);$cancel=(int)$q->fetchColumn();

$unread=unread_notification_count($pdo,(int)$member['id']);
$unreadMessages=unread_announcement_count($pdo,(int)$member['id']);
$eventCount=upcoming_event_count($pdo);

$mq=$pdo->prepare("SELECT a.*,ar.read_at AS read_at,u.full_name AS author FROM announcements a
 LEFT JOIN announcement_reads ar ON ar.announcement_id=a.id AND ar.member_id=?
 LEFT JOIN users u ON u.id=a.user_id
 WHERE a.status='published' AND (a.audience='all' OR EXISTS(
   SELECT 1 FROM appointments ap JOIN appointment_slots aps ON aps.id=ap.slot_id
   WHERE ap.member_id=? AND ap.status IN('pending','confirmed') AND aps.appointment_date>=CURDATE()
 ))
 ORDER BY COALESCE(a.published_at,a.created_at) DESC LIMIT 1");
$mq->execute([(int)$member['id'],(int)$member['id']]);$latestMessage=$mq->fetch();

$eq=$pdo->query("SELECT * FROM church_events WHERE status='published' AND event_date>=CURDATE() ORDER BY event_date,start_time LIMIT 2");$upcomingEventRows=$eq->fetchAll();
$officeStatus=setting($pdo,'office_status','available');

$page_title='Member Dashboard';require __DIR__.'/../includes/header.php';?>
<div>
<?php require __DIR__.'/../includes/sidebar.php';?>
<main class="main">
<header class="topbar">
 <div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button><div><h1>Member Dashboard</h1><p>Welcome back, <?=e($firstName)?>. Stay connected with FGCK Joyland.</p></div></div>
 <a class="btn btn-primary btn-sm" href="/fgck_joyland/member/book.php"><i class="bi bi-calendar-plus me-1"></i>Book Appointment</a>
</header>
<div class="content">
<div id="dashboardOfficeStatus" class="alert <?=$officeStatus==='available'?'alert-success':'alert-danger'?> py-2 small"><i class="bi <?=$officeStatus==='available'?'bi-check-circle':'bi-door-closed'?> me-1"></i><?=$officeStatus==='available'?"Pastor's Office is currently open for appointments.":"Pastor's Office is currently closed. New bookings are temporarily unavailable."?></div>

<div class="welcome mb-4">
 <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
  <div><h2>Welcome to your church portal.</h2><p>Manage your pastor appointments, receive church messages and keep up with upcoming FGCK Joyland events in one secure place.</p></div>
  <div class="d-flex gap-2 flex-wrap"><a href="/fgck_joyland/member/announcements.php" class="btn btn-light btn-sm"><i class="bi bi-megaphone me-1"></i>Church Messages</a><a href="/fgck_joyland/member/events.php" class="btn btn-outline-light btn-sm"><i class="bi bi-calendar2-heart me-1"></i>Events</a></div>
 </div>
</div>

<div class="row g-3 mb-4">
 <div class="col-6 col-xl-3"><div class="cardx kpi-modern"><div class="icon"><i class="bi bi-calendar-check"></i></div><div class="label">Appointments</div><div class="value"><?=$total?></div><div class="hint">Your appointment history</div></div></div>
 <div class="col-6 col-xl-3"><div class="cardx kpi-modern"><div class="icon"><i class="bi bi-check2-circle"></i></div><div class="label">Completed</div><div class="value"><?=$done?></div><div class="hint">Completed conversations</div></div></div>
 <div class="col-6 col-xl-3"><div class="cardx kpi-modern"><div class="icon"><i class="bi bi-megaphone"></i></div><div class="label">Unread messages</div><div class="value"><?=$unreadMessages?></div><div class="hint">Pastoral communication</div></div></div>
 <div class="col-6 col-xl-3"><div class="cardx kpi-modern"><div class="icon"><i class="bi bi-calendar2-heart"></i></div><div class="label">Upcoming events</div><div class="value"><?=$eventCount?></div><div class="hint">Published church events</div></div></div>
</div>

<div class="row g-4 mb-4">
 <div class="col-xl-7">
  <div class="cardx p-4 h-100">
   <div class="analytics-head"><div><h3>Upcoming appointment</h3><p>Your next conversation with the pastor.</p></div><a href="/fgck_joyland/member/appointments.php" class="small">View history</a></div>
   <?php if($up):?>
   <div class="queue-highlight">
    <span class="badge-soft">30 MINUTES · <?=e(ucfirst($up['status']))?></span>
    <h3 class="mt-3 mb-1"><?=e(fd($up['appointment_date']))?></h3>
    <div class="queue-time"><?=e(ft($up['start_time']))?> – <?=e(ft($up['end_time']))?></div>
    <div class="small mt-3"><b>Reference:</b> <?=e($up['appointment_no'])?> · <b>Purpose:</b> <?=e($up['purpose'])?></div>
    <div class="small text-muted mt-2"><i class="bi bi-broadcast me-1"></i>Time changes from the pastor are synchronized automatically.</div>
   </div>
   <?php else:?>
   <div class="empty"><i class="bi bi-calendar2-plus fs-1"></i><p class="mt-3">You do not have an upcoming appointment.</p><?php if($officeStatus==='available'):?><a class="btn btn-primary" href="/fgck_joyland/member/book.php">Find an available slot</a><?php else:?><span class="text-muted small">New bookings are currently unavailable.</span><?php endif;?></div>
   <?php endif;?>
  </div>
 </div>

 <div class="col-xl-5">
  <div class="cardx p-4 h-100">
   <div class="analytics-head"><div><h3>Latest church message</h3><p>Stay informed by the pastor.</p></div><a href="/fgck_joyland/member/announcements.php" class="small">All messages</a></div>
   <?php if($latestMessage):?>
   <div class="communication-card <?=$latestMessage['read_at']?'':'unread-card'?>">
    <span class="message-type <?=$latestMessage['type']==='urgent'?'urgent':($latestMessage['type']==='prayer'?'prayer':($latestMessage['type']==='pastoral'?'pastoral':''))?>"><?=e(event_category_label($latestMessage['type']))?></span>
    <h4 class="fw-bold mt-2 mb-1"><?=e($latestMessage['title'])?></h4>
    <div class="small text-muted mb-2"><?=e($latestMessage['published_at']?date('d M Y, g:i A',strtotime($latestMessage['published_at'])):date('d M Y, g:i A',strtotime($latestMessage['created_at'])))?></div>
    <div class="small text-secondary detail-message"><?=e(mb_strimwidth($latestMessage['message'],0,220,'…'))?></div>
    <a href="/fgck_joyland/member/announcements.php" class="btn btn-sm btn-outline-primary mt-3">Read message</a>
   </div>
   <?php else:?><div class="empty">No church messages yet.</div><?php endif;?>
  </div>
 </div>
</div>

<div class="row g-4">
 <div class="col-xl-7">
  <div class="cardx p-4">
   <div class="analytics-head"><div><h3>Upcoming church events</h3><p>Activities published by church leadership.</p></div><a href="/fgck_joyland/member/events.php" class="small">View calendar</a></div>
   <div class="d-grid gap-3">
  <?php foreach($upcomingEventRows as $ev):?>
    <div class="d-flex gap-3 align-items-center p-3 rounded-3 border">
     <div class="event-date-box"><div class="month"><?=e(date('M',strtotime($ev['event_date'])))?></div><div class="day"><?=e(date('d',strtotime($ev['event_date'])))?></div><div class="dow"><?=e(date('D',strtotime($ev['event_date'])))?></div></div>
     <div class="flex-grow-1"><span class="event-category"><?=e(event_category_label($ev['category']))?></span><h4 class="fs-6 fw-bold mt-1 mb-1"><?=e($ev['title'])?></h4><div class="small text-muted"><?=e($ev['start_time']?fmt_time($ev['start_time']):'Time to be announced')?><?= $ev['venue']?' · '.e($ev['venue']):'' ?></div></div>
    </div>
   <?php endforeach;?>
  <?php if(!$upcomingEventRows):?><div class="empty">No upcoming events have been published.</div><?php endif;?>
   </div>
  </div>
 </div>
 <div class="col-xl-5">
  <div class="cardx p-4">
   <div class="analytics-head"><div><h3>Stay connected</h3><p>Quick access to your church services.</p></div></div>
   <div class="row g-2">
    <div class="col-6"><a class="insight text-decoration-none" href="/fgck_joyland/member/notifications.php"><div class="insight-icon"><i class="bi bi-bell"></i></div><div><strong><?=$unread?> unread alerts</strong><span>Appointment and system notifications.</span></div></a></div>
    <div class="col-6"><a class="insight text-decoration-none" href="/fgck_joyland/member/appointments.php"><div class="insight-icon"><i class="bi bi-calendar-check"></i></div><div><strong>My appointments</strong><span>Track your requests and times.</span></div></a></div>
    <div class="col-6"><a class="insight text-decoration-none" href="/fgck_joyland/member/reports.php"><div class="insight-icon"><i class="bi bi-bar-chart"></i></div><div><strong>My reports</strong><span>Review your appointment activity.</span></div></a></div>
    <div class="col-6"><a class="insight text-decoration-none" href="/fgck_joyland/member/profile.php"><div class="insight-icon"><i class="bi bi-person"></i></div><div><strong>My profile</strong><span>Keep your contact details current.</span></div></a></div>
   </div>
  </div>
 </div>
</div>
</div>
</main>
</div>
<?php if($up):?><script>(function(){let sig='';async function sync(){try{const r=await fetch('/fgck_joyland/api/availability.php?date=<?=rawurlencode($up['appointment_date'])?>',{cache:'no-store'});const d=await r.json();if(!d.ok)return;if(sig&&sig!==d.signature)location.reload();sig=d.signature}catch(e){}}setInterval(sync,5000);sync();})();</script><?php endif;?>
<?php require __DIR__.'/../includes/footer.php';?>
