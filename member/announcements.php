<?php
require_once __DIR__.'/../includes/bootstrap.php';
member_required();
$member=current_member($pdo);
$msg='';$error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        $action=$_POST['action']??'';
        if($action==='read'){
            $id=(int)($_POST['id']??0);
            $q=$pdo->prepare("INSERT IGNORE INTO announcement_reads(announcement_id,member_id) SELECT ?,? FROM announcements a WHERE a.id=? AND a.status='published'");
            $q->execute([$id,(int)$member['id'],$id]);
            $msg='Message marked as read.';
        }elseif($action==='all_read'){
            $sql="INSERT IGNORE INTO announcement_reads(announcement_id,member_id)
                SELECT a.id,? FROM announcements a
                WHERE a.status='published' AND (a.audience='all' OR EXISTS(
                    SELECT 1 FROM appointments ap JOIN appointment_slots aps ON aps.id=ap.slot_id
                    WHERE ap.member_id=? AND ap.status IN('pending','confirmed') AND aps.appointment_date>=CURDATE()
                ))";
            $pdo->prepare($sql)->execute([(int)$member['id'],(int)$member['id']]);
            $msg='All available church messages marked as read.';
        }else throw new RuntimeException('Invalid action.');
    }catch(Throwable $e){$error='The message status could not be updated.';}
}

$q=$pdo->prepare("SELECT a.*,ar.read_at,u.full_name AS author
    FROM announcements a
    LEFT JOIN announcement_reads ar ON ar.announcement_id=a.id AND ar.member_id=?
    LEFT JOIN users u ON u.id=a.user_id
    WHERE a.status='published' AND (a.audience='all' OR EXISTS(
        SELECT 1 FROM appointments ap JOIN appointment_slots aps ON aps.id=ap.slot_id
        WHERE ap.member_id=? AND ap.status IN('pending','confirmed') AND aps.appointment_date>=CURDATE()
    ))
    ORDER BY COALESCE(a.published_at,a.created_at) DESC");
$q->execute([(int)$member['id'],(int)$member['id']]);
$rows=$q->fetchAll();
$unread=count(array_filter($rows,fn($r)=>empty($r['read_at'])));
$page_title='Church Messages';
require __DIR__.'/../includes/header.php';
?>
<div>
<?php require __DIR__.'/../includes/sidebar.php';?>
<main class="main">
<header class="topbar">
 <div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button><div><h1>Church Messages</h1><p>Pastoral updates, prayer points, reminders and important notices.</p></div></div>
 <?php if($unread):?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="all_read"><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-check2-all me-1"></i>Mark all read</button></form><?php endif;?>
</header>
<div class="content">
<?php if($msg):?><div class="alert alert-success small"><?=e($msg)?></div><?php endif;?>
<?php if($error):?><div class="alert alert-danger small"><?=e($error)?></div><?php endif;?>
<div class="message-hero mb-4"><div class="position-relative" style="z-index:1"><div class="small text-uppercase fw-bold mb-2" style="letter-spacing:1px;color:#f0c866">Pastoral connection</div><h2>Stay connected with the life of FGCK Joyland.</h2><p>Read messages from the pastor and church leadership. Important updates are kept here so you can return to them whenever you need them.</p></div></div>

<div class="d-flex justify-content-between align-items-center mb-3"><div><strong><?=$unread?></strong> unread message<?=$unread===1?'':'s'?></div><span class="text-muted small"><?=count($rows)?> available message<?=count($rows)===1?'':'s'?></span></div>
<div class="d-grid gap-3">
<?php foreach($rows as $r):?>
<div class="communication-card <?=empty($r['read_at'])?'unread-card':''?>">
 <div class="d-flex justify-content-between gap-3 flex-wrap">
  <div>
   <span class="message-type <?=$r['type']==='urgent'?'urgent':($r['type']==='prayer'?'prayer':($r['type']==='pastoral'?'pastoral':''))?>"><i class="bi bi-megaphone"></i><?=e(event_category_label($r['type']))?></span>
   <?php if(empty($r['read_at'])):?><span class="ms-2 read-dot" title="Unread"></span><?php endif;?>
   <h3 class="fs-5 fw-bold mt-2 mb-1"><?=e($r['title'])?></h3>
   <div class="small text-muted"><?=e($r['published_at']?date('d M Y, g:i A',strtotime($r['published_at'])):date('d M Y, g:i A',strtotime($r['created_at'])))?> · From <?=e($r['author']??'FGCK Joyland')?></div>
  </div>
  <?php if(empty($r['read_at'])):?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-sm btn-outline-primary">Mark read</button></form><?php else:?><span class="text-success small fw-bold"><i class="bi bi-check2-all"></i> Read</span><?php endif;?>
 </div>
 <div class="detail-message mt-3"><?=e($r['message'])?></div>
 <?php if($r['priority']!=='normal'):?><div class="mt-3"><span class="badge text-bg-<?=$r['priority']==='urgent'?'danger':'warning'?>"><?=e(ucfirst($r['priority']))?> priority</span></div><?php endif;?>
</div>
<?php endforeach;?>
<?php if(!$rows):?><div class="cardx p-5 text-center"><i class="bi bi-megaphone fs-1 text-muted"></i><h3 class="section-title mt-3">No messages yet</h3><p class="text-muted small mb-0">Church announcements and pastoral messages will appear here.</p></div><?php endif;?>
</div>
</div>
</main>
</div>
<?php require __DIR__.'/../includes/footer.php';?>
