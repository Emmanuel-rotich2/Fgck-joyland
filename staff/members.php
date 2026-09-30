<?php require __DIR__.'/../includes/bootstrap.php';staff_required();$q=$pdo->query("SELECT m.*,COUNT(a.id) appointment_count FROM members m LEFT JOIN appointments a ON a.member_id=m.id GROUP BY m.id ORDER BY m.created_at DESC");$rows=$q->fetchAll();$page_title='Members';require __DIR__.'/../includes/header.php';?>
<div>
    <?php require __DIR__.'/../includes/staff_sidebar.php';?>
    <main class="main">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i
                        class="bi bi-list"></i></button>
                <div>
                    <h1>Members</h1>
                    <p>Registered members and appointment activity.</p>
                </div>
            </div>
        </header>
        <div class="content">
            <div class="cardx p-4">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Membership</th>
                                <th>Contact</th>
                                <th>Appointments</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rows as $m):?>
                            <tr>
                                <td><b>
                                        <?=e($m['full_name'])?>
                                    </b></td>
                                <td>
                                    <?=e($m['membership_no'])?>
                                </td>
                                <td>
                                    <?=e($m['phone'])?><br>
                                    <?=e($m['email'])?>
                                </td>
                                <td>
                                    <?=$m['appointment_count']?>
                                </td>
                                <td>
                                    <?=e(ucfirst($m['status']))?>
                                </td>
                            </tr>
                            <?php endforeach;?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require __DIR__.'/../includes/footer.php';?>