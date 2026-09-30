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
            $description=trim((string)($_POST['description']??''));
            $category=(string)($_POST['category']??'other');
            $date=(string)($_POST['event_date']??'');
            $start=trim((string)($_POST['start_time']??''));
            $end=trim((string)($_POST['end_time']??''));
            $venue=trim((string)($_POST['venue']??''));
            $registration=!empty($_POST['registration_required'])?1:0;
            $deadline=trim((string)($_POST['registration_deadline']??''));
            $status=(string)($_POST['status']??'published');
            $cats=['worship','bible_study','prayer','youth','outreach','conference','fellowship','special_service','other'];
            if($title===''||mb_strlen($title)>180) throw new RuntimeException('Please provide an event title up to 180 characters.');
            if(!in_array($category,$cats,true)||!in_array($status,['draft','published'],true)) throw new RuntimeException('Invalid event category or status.');
            $dt=DateTime::createFromFormat('Y-m-d',$date);
            if(!$dt||$dt->format('Y-m-d')!==$date) throw new RuntimeException('Please select a valid event date.');
            if($start!=='' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$start)) throw new RuntimeException('Please enter a valid start time.');
            if($end!=='' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$end)) throw new RuntimeException('Please enter a valid end time.');
            if($start!=='' && $end!=='' && $end<=$start) throw new RuntimeException('Event end time must be after the start time.');
            if($registration && $deadline!==''){
                $deadline=$deadline.':00';
                $dlt=DateTime::createFromFormat('Y-m-d\TH:i:s',$deadline);
                if(!$dlt) throw new RuntimeException('Please enter a valid registration deadline.');
            }else $deadline=null;

            $pdo->beginTransaction();
            $q=$pdo->prepare("INSERT INTO church_events(user_id,title,description,category,event_date,start_time,end_time,venue,registration_required,registration_deadline,status) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
            $q->execute([(int)$staff['id'],$title,$description,$category,$date,$start!==''?$start.':00':null,$end!==''?$end.':00':null,$venue,$registration,$deadline,$status]);
            $eventId=(int)$pdo->lastInsertId();
            $recipients=0;$emails=0;
            if($status==='published'){
                $recipients=notify_all_members($pdo,'New church event: '.$title,'A new church event has been published for '.fmt_date($date).($start?' at '.fmt_time($start):'').'. Please open Church Events in your member portal for details.','info');
                $members=$pdo->query("SELECT full_name,email FROM members WHERE status='active' AND email<>''")->fetchAll();
                $pdo->commit();
                $body='<p>Dear member,</p><p>FGCK Joyland has published a new church event.</p><p><b>'.e($title).'</b><br>'.e(fmt_date($date)).($start?'<br>'.e(fmt_time($start)).($end?' – '.e(fmt_time($end)):''):'').($venue?'<br>'.e($venue):'').'</p><p>'.nl2br(e($description)).'</p><p>Please sign in to the member portal to view the event and RSVP if registration is enabled.</p>';
                foreach($members as $m) if(send_system_email((string)$m['email'],(string)$m['full_name'],'New church event – '.$title,$body)) $emails++;
                log_activity($pdo,null,(int)$staff['id'],'event_published','Published event #'.$eventId.' to '.$recipients.' member(s). Emails sent: '.$emails);
                $msg='Event published successfully. Members have been notified'.($emails?' and '.$emails.' email(s) were sent.':'.');
            }else{
                $pdo->commit();
                log_activity($pdo,null,(int)$staff['id'],'event_draft','Saved event draft #'.$eventId);
                $msg='Event draft saved successfully.';
            }
        }elseif(in_array($action,['cancel','complete'],true)){
            $id=(int)($_POST['id']??0);
            $new=$action==='cancel'?'cancelled':'completed';
            $q=$pdo->prepare("UPDATE church_events SET status=? WHERE id=? AND status IN('published','draft')");
            $q->execute([$new,$id]);
            if($q->rowCount()){
                if($new==='cancelled'){
                    $e=$pdo->prepare("SELECT title,event_date FROM church_events WHERE id=?");$e->execute([$id]);$ev=$e->fetch();
                    if($ev) notify_all_members($pdo,'Event cancelled: '.$ev['title'],'The church event scheduled for '.fmt_date($ev['event_date']).' has been cancelled. Please check the member portal for further updates.','warning');
                }
                log_activity($pdo,null,(int)$staff['id'],'event_status','Event #'.$id.' -> '.$new);
                $msg='Event status updated.';
            }else $error='The event could not be updated.';
        }else throw new RuntimeException('Unknown event action.');
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        $error=$e instanceof RuntimeException?$e->getMessage():'The event could not be saved.';
    }
}

$events=$pdo->query("SELECT e.*,u.full_name AS author,
    (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id=e.id AND r.status='going') AS going_count,
    (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id=e.id AND r.status='maybe') AS maybe_count,
    (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id=e.id AND r.status='not_going') AS not_going_count
    FROM church_events e LEFT JOIN users u ON u.id=e.user_id
    ORDER BY e.event_date DESC,e.start_time DESC LIMIT 40")->fetchAll();

$page_title='Church Events';
require __DIR__.'/../includes/header.php';
?>
<div>
<?php require __DIR__.'/../includes/staff_sidebar.php';?>
<main class="main">
<header class="topbar">
 <div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button><div><h1>Church Events</h1><p>Plan, publish and monitor church activities and member participation.</p></div></div>
 <a href="#createEvent" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Event</a>
</header>
<div class="content">
<?php if($msg):?><div class="alert alert-success small"><i class="bi bi-check-circle me-1"></i><?=e($msg)?></div><?php endif;?>
<?php if($error):?><div class="alert alert-danger small"><i class="bi bi-exclamation-triangle me-1"></i><?=e($error)?></div><?php endif;?>

<div class="message-hero mb-4">
 <div class="position-relative" style="z-index:1"><div class="small text-uppercase fw-bold mb-2" style="letter-spacing:1px;color:#f0c866">Church calendar</div><h2>Bring the church together around what matters.</h2><p>Create worship services, Bible studies, prayer meetings, youth fellowships, outreach activities, conferences and special services. Published events appear immediately in members' portals.</p></div>
</div>

<div class="row g-4">
<div class="col-xl-5" id="createEvent">
<div class="cardx p-4">
 <div class="d-flex justify-content-between align-items-start mb-3"><div><h3 class="section-title mb-1">Create an event</h3><p class="text-muted small mb-0">Publish now or save it as a draft.</p></div><i class="bi bi-calendar2-heart fs-3 text-warning"></i></div>
 <form method="post">
  <input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="create">
  <div class="mb-3"><label class="form-label">Event title</label><input name="title" class="form-control" maxlength="180" required placeholder="e.g. Thanksgiving Service"></div>
  <div class="row g-3">
   <div class="col-md-6"><label class="form-label">Category</label><select name="category" class="form-select">
    <?php foreach(['worship','bible_study','prayer','youth','outreach','conference','fellowship','special_service','other'] as $c):?><option value="<?=$c?>"><?=e(event_category_label($c))?></option><?php endforeach;?>
   </select></div>
   <div class="col-md-6"><label class="form-label">Date</label><input type="date" name="event_date" class="form-control" min="<?=date('Y-m-d')?>" required></div>
   <div class="col-md-6"><label class="form-label">Start time <span class="text-muted">(optional)</span></label><input type="time" name="start_time" class="form-control"></div>
   <div class="col-md-6"><label class="form-label">End time <span class="text-muted">(optional)</span></label><input type="time" name="end_time" class="form-control"></div>
  </div>
  <div class="mb-3 mt-3"><label class="form-label">Venue / meeting link</label><input name="venue" class="form-control" maxlength="180" placeholder="Church sanctuary, Google Meet, Joyland Hall..."></div>
  <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="5" placeholder="Share what members should know, what to bring, who is leading, etc."></textarea></div>
  <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="registration_required" id="regRequired"><label class="form-check-label small" for="regRequired"><strong>Enable member RSVP</strong> — members can respond Going / Maybe / Not going.</label></div>
  <div class="mb-3"><label class="form-label">Registration deadline <span class="text-muted">(optional)</span></label><input type="datetime-local" name="registration_deadline" class="form-control"></div>
  <div class="d-flex gap-2"><button name="status" value="draft" class="btn btn-outline-secondary">Save Draft</button><button name="status" value="published" class="btn btn-primary"><i class="bi bi-send me-1"></i>Publish Event</button></div>
 </form>
</div>
</div>

<div class="col-xl-7">
<div class="cardx p-4">
 <div class="analytics-head"><div><h3>Event register</h3><p>Upcoming and previous church events with RSVP activity.</p></div></div>
 <?php if(!$events):?><div class="empty">No church events have been created yet.</div><?php endif;?>
 <div class="d-grid gap-3">
 <?php foreach($events as $ev):?>
 <div class="event-card">
  <div class="d-flex gap-3">
   <div class="event-date-box"><div class="month"><?=e(date('M',strtotime($ev['event_date'])))?></div><div class="day"><?=e(date('d',strtotime($ev['event_date'])))?></div><div class="dow"><?=e(date('D',strtotime($ev['event_date'])))?></div></div>
   <div class="flex-grow-1">
    <div class="d-flex justify-content-between gap-2 flex-wrap"><div><span class="event-category"><?=e(event_category_label($ev['category']))?></span><h4 class="fs-6 fw-bold mt-2 mb-1"><?=e($ev['title'])?></h4></div><span class="badge text-bg-<?=$ev['status']==='published'?'success':($ev['status']==='draft'?'warning':($ev['status']==='cancelled'?'danger':'secondary'))?>"><?=e(ucfirst($ev['status']))?></span></div>
    <div class="small text-muted"><?=e($ev['start_time']?fmt_time($ev['start_time']):'Time to be announced')?><?= $ev['end_time']?' – '.e(fmt_time($ev['end_time'])):'' ?><?= $ev['venue']?' · '.e($ev['venue']):'' ?></div>
    <?php if($ev['description']):?><p class="small mt-2 mb-2 text-secondary"><?=e(mb_strimwidth($ev['description'],0,190,'…'))?></p><?php endif;?>
    <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
      <?php if($ev['registration_required']):?><span class="badge-soft"><i class="bi bi-person-check me-1"></i><?=$ev['going_count']?> Going · <?=$ev['maybe_count']?> Maybe · <?=$ev['not_going_count']?> Not going</span><?php else:?><span class="text-muted small">RSVP not required</span><?php endif;?>
      <?php if($ev['status']==='published' && $ev['event_date']>=date('Y-m-d')):?>
      <form method="post" class="ms-auto" onsubmit="return confirm('Cancel this event? Members will be notified.');"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?=$ev['id']?>"><button class="btn btn-sm btn-outline-danger">Cancel Event</button></form>
      <?php elseif($ev['status']==='published' && $ev['event_date']<date('Y-m-d')):?>
      <form method="post" class="ms-auto"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="complete"><input type="hidden" name="id" value="<?=$ev['id']?>"><button class="btn btn-sm btn-outline-secondary">Mark Completed</button></form>
      <?php endif;?>
    </div>
   </div>
  </div>
 </div>
 <?php endforeach;?>
 </div>
</div>
</div>
</div>
</div>
</main>
</div>
<?php require __DIR__.'/../includes/footer.php';?>
