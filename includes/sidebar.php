<?php
$member=$member??current_member($pdo);
$currentPage=basename($_SERVER['PHP_SELF']);
$unreadNotifications=$member ? unread_notification_count($pdo,(int)$member['id']) : 0;
$unreadAnnouncements=$member ? unread_announcement_count($pdo,(int)$member['id']) : 0;
$upcomingEvents=upcoming_event_count($pdo);
function member_nav_active($pages): string {
    global $currentPage;
    return in_array($currentPage,(array)$pages,true) ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-logo"><img src="/fgck_joyland/assets/images/full_gospel_churches_logo.png" alt="FGCK Joyland"></div>
        <div><strong>FGCK Joyland</strong><small>Member Portal</small></div>
    </div>

    <div class="sidebar-label">MEMBER AREA</div>
    <nav>
        <a href="/fgck_joyland/member/dashboard.php" class="<?=member_nav_active('dashboard.php')?>">
            <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
        </a>
        <a href="/fgck_joyland/member/book.php" class="<?=member_nav_active('book.php')?>">
            <i class="bi bi-calendar-plus"></i><span>Book Appointment</span>
        </a>
        <a href="/fgck_joyland/member/appointments.php" class="<?=member_nav_active('appointments.php')?>">
            <i class="bi bi-calendar-check"></i><span>My Appointments</span>
        </a>
        <a href="/fgck_joyland/member/announcements.php" class="<?=member_nav_active('announcements.php')?>">
            <i class="bi bi-megaphone"></i><span>Church Messages</span>
            <?php if($unreadAnnouncements): ?><span class="nav-count"><?=$unreadAnnouncements?></span><?php endif; ?>
        </a>
        <a href="/fgck_joyland/member/events.php" class="<?=member_nav_active('events.php')?>">
            <i class="bi bi-calendar2-heart"></i><span>Church Events</span>
            <?php if($upcomingEvents): ?><span class="nav-count soft"><?=$upcomingEvents?></span><?php endif; ?>
        </a>
        <a href="/fgck_joyland/member/notifications.php" class="<?=member_nav_active('notifications.php')?>">
            <i class="bi bi-bell"></i><span>Notifications</span>
            <?php if($unreadNotifications): ?><span class="nav-count"><?=$unreadNotifications?></span><?php endif; ?>
        </a>
        <a href="/fgck_joyland/member/reports.php" class="<?=member_nav_active('reports.php')?>">
            <i class="bi bi-bar-chart-line"></i><span>My Reports</span>
        </a>
        <a href="/fgck_joyland/member/profile.php" class="<?=member_nav_active('profile.php')?>">
            <i class="bi bi-person"></i><span>My Profile</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <div class="mini-user">
            <span><?=e(strtoupper(substr($member['full_name']??'M',0,1)))?></span>
            <div><b><?=e($member['full_name']??'Member')?></b><small><?=e($member['membership_no']??'')?></small></div>
        </div>
        <a href="/fgck_joyland/auth/logout.php" class="logout" onclick="return confirm('Sign out of your member portal?')">
            <i class="bi bi-box-arrow-right"></i><span>Sign out</span>
        </a>
    </div>
</aside>
