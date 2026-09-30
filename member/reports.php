<?php
require_once __DIR__.'/../includes/bootstrap.php';
member_required();
$member=current_member($pdo);
$mid=(int)$member['id'];
$q=$pdo->prepare("SELECT status,COUNT(*) total FROM appointments WHERE member_id=? GROUP BY status");$q->execute([$mid]);$stats=[];foreach($q as $r)$stats[$r['status']]=(int)$r['total'];
$q=$pdo->prepare("SELECT DATE_FORMAT(s.appointment_date,'%b %Y') label,DATE_FORMAT(s.appointment_date,'%Y-%m') ym,COUNT(*) total FROM appointments a JOIN appointment_slots s ON s.id=a.slot_id WHERE a.member_id=? GROUP BY ym,label ORDER BY ym ASC LIMIT 12");$q->execute([$mid]);$monthly=$q->fetchAll();
$q=$pdo->prepare("SELECT COUNT(*) FROM appointments WHERE member_id=? AND status IN('pending','confirmed') AND created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY)");$q->execute([$mid]);$recent=(int)$q->fetchColumn();
$total=array_sum($stats);$completed=(int)($stats['completed']??0);$cancelled=(int)($stats['cancelled']??0);$pending=(int)($stats['pending']??0)+(int)($stats['confirmed']??0);
$page_title='My Reports';require __DIR__.'/../includes/header.php';
$chartStatus=json_encode(["labels"=>['Completed','Pending / Confirmed','Cancelled','Other'],"datasets"=>[["label"=>'Appointments',"data"=>[$completed,$pending,$cancelled,max(0,$total-$completed-$pending-$cancelled)],"borderWidth"=>0]]],JSON_UNESCAPED_SLASHES);

$labels=[];$vals=[];foreach($monthly as $m){$labels[]=$m['label'];$vals[]=(int)$m['total'];}
$chartMonthly=json_encode(["labels"=>$labels,"datasets"=>[["label"=>'Appointments',"data"=>$vals,"tension"=>.35,"fill"=>true,"borderWidth"=>2]]],JSON_UNESCAPED_SLASHES);
?>
<div>
    <?php require __DIR__.'/../includes/sidebar.php';?>
    <main class="main">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i
                        class="bi bi-list"></i></button>
                <div>
                    <h1>My Reports</h1>
                    <p>Your private appointment history and engagement insights.</p>
                </div>
            </div><span class="live-pill">Personal analytics</span>
        </header>
        <div class="content">
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="cardx kpi-modern">
                        <div class="icon"><i class="bi bi-calendar2-check"></i></div>
                        <div class="label">Total appointments</div>
                        <div class="value">
                            <?=$total?>
                        </div>
                        <div class="hint">All-time bookings</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="cardx kpi-modern">
                        <div class="icon"><i class="bi bi-check2-circle"></i></div>
                        <div class="label">Completed</div>
                        <div class="value">
                            <?=$completed?>
                        </div>
                        <div class="hint">Conversations completed</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="cardx kpi-modern">
                        <div class="icon"><i class="bi bi-hourglass-split"></i></div>
                        <div class="label">Upcoming</div>
                        <div class="value">
                            <?=$pending?>
                        </div>
                        <div class="hint">Pending or confirmed</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="cardx kpi-modern">
                        <div class="icon"><i class="bi bi-arrow-repeat"></i></div>
                        <div class="label">Last 30 days</div>
                        <div class="value">
                            <?=$recent?>
                        </div>
                        <div class="hint">Recent bookings</div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="cardx analytics-card">
                        <div class="analytics-head">
                            <div>
                                <h3>Appointment activity</h3>
                                <p>Your booking trend over recent months.</p>
                            </div>
                        </div>
                        <div class="chart-wrap"><canvas class="js-chart"
                                data-config='<?=e(json_encode(["type"=>"line","data"=>json_decode($chartMonthly,true),"options"=>["plugins"=>["legend"=>["display"=>false]]]]))?>'></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="cardx analytics-card">
                        <div class="analytics-head">
                            <div>
                                <h3>Appointment status</h3>
                                <p>A simple view of your completed and upcoming conversations.</p>
                            </div>
                        </div>
                        <div class="chart-wrap"><canvas class="js-chart"
                                data-config='<?=e(json_encode(["type"=>"doughnut","data"=>json_decode($chartStatus,true),"options"=>["plugins"=>["legend"=>["position"=>"bottom"]]]]))?>'></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <div class="insight">
                        <div class="insight-icon"><i class="bi bi-shield-check"></i></div>
                        <div><strong>Private & personal</strong><span>These analytics only show your own appointment
                                activity.</span></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="insight">
                        <div class="insight-icon"><i class="bi bi-bell"></i></div>
                        <div><strong>Live schedule updates</strong><span>Your appointment time can change when the
                                pastor adjusts the live queue.</span></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="insight">
                        <div class="insight-icon"><i class="bi bi-clock-history"></i></div>
                        <div><strong>Stay informed</strong><span>Use My Appointments to see the latest confirmed time
                                before your visit.</span></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require __DIR__.'/../includes/footer.php';?>