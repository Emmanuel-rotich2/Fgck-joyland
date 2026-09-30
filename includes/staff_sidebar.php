<?php
$s=$staff??staff($pdo);
$currentStaffPage=basename($_SERVER['PHP_SELF']);
function staff_nav_active($pages): string {
    global $currentStaffPage;
    return in_array($currentStaffPage,(array)$pages,true) ? 'active' : '';
}
$pendingMessages=(int)$pdo->query("SELECT COUNT(*) FROM announcements WHERE status='draft'")->fetchColumn();
$upcomingEventCount=upcoming_event_count($pdo);
?>
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-logo"><img src="/fgck_joyland/assets/images/full_gospel_churches_logo.png" alt="FGCK Joyland"></div>
        <div><b>FGCK Joyland</b><small>Pastor Portal</small></div>
    </div>

    <div class="sidebar-label">PASTOR MINISTRY</div>
    <nav>
        <a href="/fgck_joyland/staff/dashboard.php" class="<?=staff_nav_active('dashboard.php')?>"><i class="bi bi-grid"></i><span>Dashboard</span></a>
        <a href="/fgck_joyland/staff/appointments.php" class="<?=staff_nav_active('appointments.php')?>"><i class="bi bi-calendar-check"></i><span>Appointments</span></a>
        <a href="/fgck_joyland/staff/calendar.php" class="<?=staff_nav_active('calendar.php')?>"><i class="bi bi-calendar3"></i><span>Live Calendar</span></a>
        <a href="/fgck_joyland/staff/availability.php" class="<?=staff_nav_active('availability.php')?>"><i class="bi bi-clock"></i><span>Slot Availability</span></a>
    </nav>

    <div class="sidebar-label mt-3">MEMBER ENGAGEMENT</div>
    <nav>
        <a href="/fgck_joyland/staff/communications.php" class="<?=staff_nav_active('communications.php')?>">
            <i class="bi bi-megaphone"></i><span>Communications</span>
            <?php if($pendingMessages): ?><span class="nav-count soft"><?=$pendingMessages?></span><?php endif; ?>
        </a>
        <a href="/fgck_joyland/staff/events.php" class="<?=staff_nav_active('events.php')?>">
            <i class="bi bi-calendar2-heart"></i><span>Church Events</span>
            <?php if($upcomingEventCount): ?><span class="nav-count soft"><?=$upcomingEventCount?></span><?php endif; ?>
        </a>
        <a href="/fgck_joyland/staff/members.php" class="<?=staff_nav_active('members.php')?>"><i class="bi bi-people"></i><span>Members</span></a>
        <a href="/fgck_joyland/staff/reports.php" class="<?=staff_nav_active('reports.php')?>"><i class="bi bi-bar-chart"></i><span>Reports & Analytics</span></a>
    </nav>

    <div class="sidebar-label mt-3">SYSTEM</div>
    <nav>
        <a href="/fgck_joyland/staff/settings.php" class="<?=staff_nav_active('settings.php')?>"><i class="bi bi-gear"></i><span>Settings</span></a>
        <a href="/fgck_joyland/staff/change_password.php" class="<?=staff_nav_active('change_password.php')?>"><i class="bi bi-key"></i><span>Change Password</span></a>
    </nav>

    <div class="side-bottom sidebar-bottom">
        <div class="mini-user"><span><i class="bi bi-person"></i></span>
            <div><b><?=e($s['full_name']??'Staff')?></b><small><?=e(ucfirst($s['role']??'staff'))?></small></div>
        </div>
        <a href="/fgck_joyland/staff/logout.php" class="logout" onclick="return confirm('Sign out of the pastor portal?')"><i class="bi bi-box-arrow-right"></i><span>Sign out</span></a>
    </div>
</aside>
