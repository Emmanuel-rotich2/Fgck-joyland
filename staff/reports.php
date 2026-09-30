<?php
require __DIR__.'/../includes/bootstrap.php';staff_required();
$q=$pdo->query('SELECT status,COUNT(*) total FROM appointments GROUP BY status');$stats=[];foreach($q as $r)$stats[$r['status']]=(int)$r['total'];
$q=$pdo->query("SELECT DATE_FORMAT(s.appointment_date,'%b %Y') label,DATE_FORMAT(s.appointment_date,'%Y-%m') ym,COUNT(*) total FROM appointments a JOIN appointment_slots s ON s.id=a.slot_id GROUP BY ym,label ORDER BY ym ASC LIMIT 12");$monthly=$q->fetchAll();
$q=$pdo->query("SELECT DATE_FORMAT(s.appointment_date,'%W') day_name,COUNT(*) total FROM appointments a JOIN appointment_slots s ON s.id=a.slot_id GROUP BY DAYOFWEEK(s.appointment_date),day_name ORDER BY DAYOFWEEK(s.appointment_date)");$days=$q->fetchAll();
$total=array_sum($stats);$completed=(int)($stats['completed']??0);$cancelled=(int)($stats['cancelled']??0);$pending=(int)($stats['pending']??0)+(int)($stats['confirmed']??0);$completionRate=$total?round($completed/$total*100):0;
$labels=[];$vals=[];foreach($monthly as $m){$labels[]=$m['label'];$vals[]=(int)$m['total'];}
$line=["type"=>"line","data"=>["labels"=>$labels,"datasets"=>[["label"=>"Appointments","data"=>$vals,"tension"=>.35,"fill"=>true,"borderWidth"=>2]]],"options"=>["plugins"=>["legend"=>["display"=>false]]]];
$donut=["type"=>"doughnut","data"=>["labels"=>['Completed','Pending','Confirmed','Cancelled','Other'],"datasets"=>[["data"=>[$completed,(int)($stats['pending']??0),(int)($stats['confirmed']??0),$cancelled,max(0,$total-$completed-(int)($stats['pending']??0)-(int)($stats['confirmed']??0)-$cancelled)]]]],"options"=>["plugins"=>["legend"=>["position"=>"bottom"]]]];
$dayLabels=[];$dayVals=[];foreach($days as $d){$dayLabels[]=$d['day_name'];$dayVals[]=(int)$d['total'];}$bar=["type"=>"bar","data"=>["labels"=>$dayLabels,"datasets"=>[["label"=>"Appointments","data"=>$dayVals,"borderRadius"=>8]]],"options"=>["plugins"=>["legend"=>["display"=>false]]]];
$page_title='Reports & Analytics';require __DIR__.'/../includes/header.php';?>
<div>
    <?php require __DIR__.'/../includes/staff_sidebar.php';?>
    <main class="main">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i
                        class="bi bi-list"></i></button>
                <div>
                    <h1>Reports & Analytics</h1>
                    <p>Professional operational intelligence for the pastor and church leadership.</p>
                </div>
            </div><span class="live-pill">Live data</span>
        </header>
        <div class="content">
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="cardx kpi-modern">
                        <div class="icon"><i class="bi bi-collection"></i></div>
                        <div class="label">All appointments</div>
                        <div class="value">
                            <?=$total?>
                        </div>
                        <div class="hint">All recorded bookings</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="cardx kpi-modern">
                        <div class="icon"><i class="bi bi-check-circle"></i></div>
                        <div class="label">Completion rate</div>
                        <div class="value">
                            <?=$completionRate?>%
                        </div>
                        <div class="hint">Completed / all appointments</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="cardx kpi-modern">
                        <div class="icon"><i class="bi bi-clock-history"></i></div>
                        <div class="label">Active queue</div>
                        <div class="value">
                            <?=$pending?>
                        </div>
                        <div class="hint">Pending + confirmed</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="cardx kpi-modern">
                        <div class="icon"><i class="bi bi-x-circle"></i></div>
                        <div class="label">Cancelled</div>
                        <div class="value">
                            <?=$cancelled?>
                        </div>
                        <div class="hint">Cancelled appointments</div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-lg-8">
                    <div class="cardx analytics-card">
                        <div class="analytics-head">
                            <div>
                                <h3>Booking trend</h3>
                                <p>Appointment demand by month.</p>
                            </div>
                        </div>
                        <div class="chart-wrap tall"><canvas class="js-chart"
                                data-config='<?=e(json_encode($line))?>'></canvas></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="cardx analytics-card">
                        <div class="analytics-head">
                            <div>
                                <h3>Appointment outcomes</h3>
                                <p>How appointments are currently distributed.</p>
                            </div>
                        </div>
                        <div class="chart-wrap tall"><canvas class="js-chart"
                                data-config='<?=e(json_encode($donut))?>'></canvas></div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-lg-7">
                    <div class="cardx analytics-card">
                        <div class="analytics-head">
                            <div>
                                <h3>Demand by day</h3>
                                <p>Booking distribution across available service days.</p>
                            </div>
                        </div>
                        <div class="chart-wrap"><canvas class="js-chart"
                                data-config='<?=e(json_encode($bar))?>'></canvas></div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="cardx analytics-card">
                        <div class="analytics-head">
                            <div>
                                <h3>Service health</h3>
                                <p>A quick operational snapshot.</p>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="insight">
                                    <div class="insight-icon"><i class="bi bi-graph-up-arrow"></i></div>
                                    <div><strong>Completion performance</strong><span>
                                            <?=$completionRate?>% of all recorded appointments are completed.
                                        </span>
                                        <div class="progress-thin mt-2"><span
                                                style="width:<?=$completionRate?>%"></span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="insight">
                                    <div class="insight-icon"><i class="bi bi-broadcast"></i></div>
                                    <div><strong>Live queue control</strong><span>Pastor time adjustments can
                                            automatically shift later appointments and notify affected members.</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="insight">
                                    <div class="insight-icon"><i class="bi bi-clock"></i></div>
                                    <div><strong>Fixed service window</strong><span>Appointment slots are generated in
                                            30-minute intervals up to the 3:00 PM closing time.</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="cardx analytics-card">
                <div class="analytics-head">
                    <div>
                        <h3>Monthly report table</h3>
                        <p>Exact counts behind the trend chart.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-clean">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Appointments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($monthly as $m):?>
                            <tr>
                                <td>
                                    <?=e($m['label'])?>
                                </td>
                                <td><b>
                                        <?=e((string)$m['total'])?>
                                    </b></td>
                            </tr>
                            <?php endforeach;if(!$monthly):?>
                            <tr>
                                <td colspan="2" class="empty">No appointment data yet.</td>
                            </tr>
                            <?php endif;?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require __DIR__.'/../includes/footer.php';?>