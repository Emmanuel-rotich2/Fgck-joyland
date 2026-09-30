<?php require_once __DIR__.'/../includes/bootstrap.php';member_required();$member=current_member($pdo);$msg='';$error='';if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$n=trim($_POST['full_name']??'');$p=trim($_POST['phone']??'');$g=trim($_POST['gender']??'');if(strlen($n)<3||strlen($p)<7)$error='Please provide valid details.';else try{$q=$pdo->prepare('UPDATE members SET full_name=?,phone=?,gender=? WHERE id=?');$q->execute([$n,$p,$g,$member['id']]);$msg='Profile updated successfully.';$member=current_member($pdo);}catch(PDOException $e){$error='That phone number may already be in use.';}}$page_title='My Profile';require __DIR__.'/../includes/header.php';?>
<div>
    <?php require __DIR__.'/../includes/sidebar.php';?>
    <main class="main">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i
                        class="bi bi-list"></i></button>
                <div>
                    <h1>My Profile</h1>
                    <p>Manage your member information.</p>
                </div>
            </div>
        </header>
        <div class="content">
            <div class="cardx p-4" style="max-width:760px">
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
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Membership number</label><input class="form-control"
                            value="<?=e($member['membership_no'])?>" disabled></div>
                    <div class="col-md-6"><label class="form-label">Email</label><input class="form-control"
                            value="<?=e($member['email'])?>" disabled></div>
                </div>
                <hr class="my-4">
                <form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label
                        class="form-label">Full name</label><input class="form-control mb-3" name="full_name"
                        value="<?=e($member['full_name'])?>" required>
                    <div class="row">
                        <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control mb-3"
                                name="phone" value="<?=e($member['phone'])?>" required></div>
                        <div class="col-md-6"><label class="form-label">Gender</label><select class="form-select mb-3"
                                name="gender">
                                <option value="">Prefer not to say</option>
                                <option <?=$member['gender']==='Male' ?'selected':''?>>Male</option>
                                <option <?=$member['gender']==='Female' ?'selected':''?>>Female</option>
                            </select></div>
                    </div><button class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </main>
</div>
<?php require __DIR__.'/../includes/footer.php';?>