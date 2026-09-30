<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
$office=setting($pdo,'office_status','available');
$notice=setting($pdo,'booking_notice','Please check your appointment details.');
echo json_encode(['ok'=>true,'office_status'=>$office,'booking_notice'=>$notice], JSON_UNESCAPED_SLASHES);