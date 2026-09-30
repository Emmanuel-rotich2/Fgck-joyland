<?php
require_once __DIR__.'/../includes/bootstrap.php';
member_required();
$member=current_member($pdo);
$msg='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $action=$_POST['action']??'';
    if($action==='read'){
        $id=(int)($_POST['id']??0);
        $q=$pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND member_id=?");
        $q->execute([$id,(int)$member['id']]);
        $msg='Notification marked as read.';
    }elseif($action==='all_read'){
        $q=$pdo->prepare("UPDATE notifications SET is_read=1 WHERE member_id=? AND is_read=0");
        $q->execute([(int)$member['id']]);
        $msg='All notifications marked as read.';
    }
}
$q=$pdo->prepare("SELECT * FROM notifications WHERE member_id=? ORDER BY created_at DESC LIMIT 80");
$q->execute([(int)$member['id']]);$rows=$q->fetchAll();
$unread=(int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE member_id=".(int)$member['id']." AND is_read=0")->fetchColumn();
$page_title='Notifications';
require __DIR__.'/../includes/header.php';
?>
<div>
<?php require __DIR__.'/../includes/sidebar.php';?>
<main class="main">
<header class="topbar">
 <div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i class="bi bi-list"></i></button><div><h1>Notifications</h1><p>Appointment updates and important system alerts.</p></div></div>
 <?php if($unread):?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="all_read"><button class="btn btn-sm btn-outline-secondary">Mark all read</button></form><?php endif;?>
</header>
<div class="content">
<?php if($msg):?><div class="alert alert-success small"><?=e($msg)?></div><?php endif;?>
<div class="cardx p-4">
 <div class="analytics-head"><div><h3>Notification inbox</h3><p><?=$unread?> unread notification<?=$unread===1?'':'s'?>.</p></div><i class="bi bi-bell fs-3 text-warning"></i></div>
 <div class="d-grid gap-2">
 <?php foreach($rows as $n):?>
 <div class="p-3 rounded-3 border <?=$n['is_read']?'':'unread-card'?>" style="background:<?=$n['is_read']?'#fff':'#fffdf7'?>">
  <div class="d-flex gap-3 align-items-start">
   <div class="insight-icon"><i class="bi bi-<?=($n['type']==='success'?'check-circle':($n['type']==='warning'?'exclamation-triangle':($n['type']==='danger'?'exclamation-octagon':'info-circle')))?>"></i></div>
   <div class="flex-grow-1"><strong><?=e($n['title'])?></strong><div class="small text-secondary mt-1"><?=e($n['message'])?></div><div class="small text-muted mt-2"><?=e(date('d M Y, g:i A',strtotime($n['created_at'])))?></div></div>
   <?php if(!$n['is_read']):?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?=$n['id']?>"><button class="btn btn-sm btn-outline-primary">Read</button></form><?php endif;?>
  </div>
 </div>
 <?php endforeach;?>
 <?php if(!$rows):?><div class="empty">You have no notifications yet.</div><?php endif;?>
 </div>
</div>
</div>
</main>
</div>
<?php require __DIR__.'/../includes/footer.php';?>
