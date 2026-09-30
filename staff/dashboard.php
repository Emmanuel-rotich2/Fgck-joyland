<?php
require __DIR__.'/../includes/bootstrap.php';staff_required();$staff=staff($pdo);$today=date('Y-m-d');
$q=$pdo->prepare("SELECT COUNT(*) FROM appointments a JOIN appointment_slots s ON s.id=a.slot_id WHERE s.appointment_date=? AND a.status IN('pending','confirmed')");$q->execute([$today]);$todayCount=(int)$q->fetchColumn();
$pending=(int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status='pending'")->fetchColumn();$members=(int)$pdo->query("SELECT COUNT(*) FROM members WHERE status='active'")->fetchColumn();$completed=(int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status='completed'")->fetchColumn();$cancelled=(int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status='cancelled'")->fetchColumn();
$publishedMessages=(int)$pdo->query("SELECT COUNT(*) FROM announcements WHERE status='published'")->fetchColumn();
$draftMessages=(int)$pdo->query("SELECT COUNT(*) FROM announcements WHERE status='draft'")->fetchColumn();
$upcomingEventsCount=upcoming_event_count($pdo);
$upcomingEventRows=$pdo->query("SELECT * FROM church_events WHERE status='published' AND event_date>=CURDATE() ORDER BY event_date,start_time LIMIT 3")->fetchAll();
$recentMessages=$pdo->query("SELECT * FROM announcements WHERE status='published' ORDER BY COALESCE(published_at,created_at) DESC LIMIT 3")->fetchAll();

$q=$pdo->query("SELECT DATE_FORMAT(s.appointment_date,'%b %Y') label,DATE_FORMAT(s.appointment_date,'%Y-%m') ym,COUNT(*) total FROM appointments a JOIN appointment_slots s ON s.id=a.slot_id GROUP BY ym,label ORDER BY ym ASC LIMIT 12");$monthly=$q->fetchAll();
$q=$pdo->query("SELECT status,COUNT(*) total FROM appointments GROUP BY status");$status=[];foreach($q as $r)$status[$r['status']]=(int)$r['total'];
$q=$pdo->query("SELECT a.*,m.full_name,m.phone,s.appointment_date,COALESCE(a.adjusted_start_time,s.start_time) AS start_time,COALESCE(a.adjusted_end_time,s.end_time) AS end_time FROM appointments a JOIN members m ON m.id=a.member_id JOIN appointment_slots s ON s.id=a.slot_id WHERE s.appointment_date>=CURDATE() AND a.status IN('pending','confirmed') ORDER BY s.appointment_date,start_time LIMIT 8");$rows=$q->fetchAll();
$labels=[];$vals=[];foreach($monthly as $m){$labels[]=$m['label'];$vals[]=(int)$m['total'];}
$line=["type"=>"line","data"=>["labels"=>$labels,"datasets"=>[["label"=>"Appointments","data"=>$vals,"tension"=>.35,"fill"=>true,"borderWidth"=>2]]],"options"=>["plugins"=>["legend"=>["display"=>false]]]];
$donut=["type"=>"doughnut","data"=>["labels"=>['Completed','Pending','Confirmed','Cancelled','Other'],"datasets"=>[["data"=>[(int)($status['completed']??0),(int)($status['pending']??0),(int)($status['confirmed']??0),(int)($status['cancelled']??0),max(0,array_sum($status)-(int)($status['completed']??0)-(int)($status['pending']??0)-(int)($status['confirmed']??0)-(int)($status['cancelled']??0))]]],],"options"=>["plugins"=>["legend"=>["position"=>"bottom"]]]];
$page_title='Pastor Dashboard';require __DIR__.'/../includes/header.php';?>
<div>
    <?php require __DIR__.'/../includes/staff_sidebar.php';?>
    <main class="main">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3"><button class="menu-btn" data-sidebar-toggle><i
                        class="bi bi-list"></i></button>
                <div>
                    <h1>Pastor Dashboard</h1>
                    <p>Live appointment operations, member activity and service insights.</p>
                </div>
            </div><span class="live-pill">Live operations</span>
        </header>
        <div class="content">
            <div class="welcome mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
                    <div>
                        <h2>Welcome,
                            <?=e($staff['full_name']??'Pastor')?>.
                        </h2>
                        <p>Manage the appointment queue, monitor service demand and keep members informed with real-time
                            schedule changes.</p>
                    </div><a href="/fgck_joyland/staff/calendar.php" class="btn btn-light fw-bold px-4"><i
                            class="bi bi-calendar3 me-2"></i>Open live calendar</a>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="cardx kpi-modern">
                        <div class="icon"><i class="bi bi-calendar-event"></i></div>
                        <div class="label">Today's queue</div>
                        <div class="value">
                            <?=$todayCount?>
                        </div>
                        <div class="hint">Pending / confirmed today</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="cardx kpi-modern">
                        <div class="icon"><i class="bi bi-people"></i></div>
                        <div class="label">Active members</div>
                        <div class="value">
                            <?=$members?>
                        </div>
                        <div class="hint">Registered active members</div>
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
                        <div class="icon"><i class="bi bi-hourglass"></i></div>
                        <div class="label">Pending</div>
                        <div class="value">
                            <?=$pending?>
                        </div>
                        <div class="hint">Awaiting confirmation</div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="cardx kpi-modern">
                        <div class="icon"><i class="bi bi-megaphone"></i></div>
                        <div class="label">Published messages</div>
                        <div class="value"><?=$publishedMessages?></div>
                        <div class="hint"><?=$draftMessages?> draft(s) waiting</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="cardx kpi-modern">
                        <div class="icon"><i class="bi bi-calendar2-heart"></i></div>
                        <div class="label">Upcoming events</div>
                        <div class="value"><?=$upcomingEventsCount?></div>
                        <div class="hint">Published church activities</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="cardx kpi-modern">
                        <div class="icon"><i class="bi bi-send-check"></i></div>
                        <div class="label">Member engagement</div>
                        <div class="value"><?=count($recentMessages)?></div>
                        <div class="hint">Recent communications</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <a href="/fgck_joyland/staff/communications.php" class="cardx kpi-modern d-block text-decoration-none">
                        <div class="icon"><i class="bi bi-plus-circle"></i></div>
                        <div class="label">Quick action</div>
                        <div class="value" style="font-size:18px">Send message</div>
                        <div class="hint">Notify church members</div>
                    </a>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-6">
                    <div class="cardx analytics-card h-100">
                        <div class="analytics-head">
                            <div><h3>Recent pastoral messages</h3><p>Latest updates shared with members.</p></div>
                            <a href="/fgck_joyland/staff/communications.php" class="btn btn-sm btn-outline-secondary">Manage</a>
                        </div>
                        <div class="d-grid gap-2">
                            <?php foreach($recentMessages as $rm): ?>
                            <div class="p-3 rounded-3 border">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong class="small"><?=e($rm['title'])?></strong>
                                    <span class="message-type <?=($rm['type']==='urgent'?'urgent':($rm['type']==='prayer'?'prayer':($rm['type']==='pastoral'?'pastoral':'')))?>"><?=e(event_category_label($rm['type']))?></span>
                                </div>
                                <div class="small text-muted mt-1"><?=e(mb_strimwidth($rm['message'],0,120,'…'))?></div>
                            </div>
                            <?php endforeach; ?>
                            <?php if(!$recentMessages): ?><div class="empty">No published messages yet.</div><?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="cardx analytics-card h-100">
                        <div class="analytics-head">
                            <div><h3>Upcoming church events</h3><p>What members will see on their calendar.</p></div>
                            <a href="/fgck_joyland/staff/events.php" class="btn btn-sm btn-outline-secondary">Manage</a>
                        </div>
                        <div class="d-grid gap-2">
                            <?php foreach($upcomingEventRows as $ue): ?>
                            <div class="d-flex gap-3 align-items-center p-2 rounded-3 border">
                                <div class="event-date-box"><div class="month"><?=e(date('M',strtotime($ue['event_date'])))?></div><div class="day"><?=e(date('d',strtotime($ue['event_date'])))?></div></div>
                                <div><strong class="small"><?=e($ue['title'])?></strong><div class="small text-muted"><?=e(event_category_label($ue['category']))?> · <?=e($ue['start_time']?fmt_time($ue['start_time']):'Time TBA')?></div></div>
                            </div>
                            <?php endforeach; ?>
                            <?php if(!$upcomingEventRows): ?><div class="empty">No upcoming events published.</div><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-8">
                    <div class="cardx analytics-card">
                        <div class="analytics-head">
                            <div>
                                <h3>Appointment demand</h3>
                                <p>Monthly booking volume across the church appointment service.</p>
                            </div><a class="btn btn-sm btn-outline-secondary"
                                href="/fgck_joyland/staff/reports.php">Full reports</a>
                        </div>
                        <div class="chart-wrap"><canvas class="js-chart"
                                data-config='<?=e(json_encode($line))?>'></canvas></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="cardx analytics-card">
                        <div class="analytics-head">
                            <div>
                                <h3>Service status</h3>
                                <p>Current appointment lifecycle distribution.</p>
                            </div>
                        </div>
                        <div class="chart-wrap"><canvas class="js-chart"
                                data-config='<?=e(json_encode($donut))?>'></canvas></div>
                    </div>
                </div>
            </div>
            <div class="cardx analytics-card">
                <div class="analytics-head">
                    <div>
                        <h3>Upcoming live queue</h3>
                        <p>Members currently scheduled for upcoming conversations.</p>
                    </div><span class="live-pill">Auto-updating queue</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-clean">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Purpose</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rows as $r):?>
                            <tr>
                                <td><b>
                                        <?=e($r['full_name'])?>
                                    </b><br><small class="text-muted">
                                        <?=e($r['phone'])?>
                                    </small></td>
                                <td>
                                    <?=e(fmt_date($r['appointment_date']))?>
                                </td>
                                <td><b>
                                        <?=e(fmt_time($r['start_time']))?> –
                                        <?=e(fmt_time($r['end_time']))?>
                                    </b></td>
                                <td>
                                    <?=e($r['purpose'])?>
                                </td>
                                <td><span class="badge-soft">
                                        <?=e(ucfirst($r['status']))?>
                                    </span></td>
                            </tr>
                            <?php endforeach;if(!$rows):?>
                            <tr>
                                <td colspan="5" class="empty">No upcoming appointments.</td>
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