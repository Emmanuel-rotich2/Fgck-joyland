<?php
require __DIR__.'/../includes/bootstrap.php';
member_required();
$date=$_GET['date']??next_wednesday();
if(!is_wednesday($date)||$date<date('Y-m-d')){http_response_code(422);echo json_encode(['ok'=>false,'message'=>'Invalid Wednesday.']);exit;}
ensure_wednesday_slots($pdo,$date);
[$open,$close,$slotMinutes]=appointment_schedule($pdo);
$slots=schedule_slots($pdo,$date);
$max=$slots?max(array_map(fn($r)=>strtotime($r['updated_at']),$slots)):0;
$q=$pdo->prepare("SELECT COALESCE(MAX(a.updated_at),'1970-01-01 00:00:00') FROM appointments a JOIN appointment_slots s ON s.id=a.slot_id WHERE s.appointment_date=?");
$q->execute([$date]);
$appointmentsUpdated=(string)$q->fetchColumn();
$office=setting($pdo,'office_status','available');
$settingsUpdated='';$q=$pdo->query("SELECT MAX(updated_at) FROM settings");$settingsUpdated=(string)$q->fetchColumn();
$signature=sha1($date.'|'.$office.'|'.$settingsUpdated.'|'.$appointmentsUpdated.'|'.$max.'|'.json_encode($slots));
$queue=null;
if(isset($_GET['member_queue']) && !empty($_SESSION['member_id'])){
    $queue=live_queue_info($pdo,(int)$_SESSION['member_id'],$date);
    if(!empty($queue['has_appointment'])){
        $queue['start_formatted']=ft($queue['start_time']);
        $queue['end_formatted']=ft($queue['end_time']);
        $queue['current_end_formatted']=$queue['current_end']?ft($queue['current_end']):null;
    }
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>true,'date'=>$date,'office_status'=>$office,'booking_notice'=>setting($pdo,'booking_notice',''),'signature'=>$signature,'slots'=>$slots,'queue'=>$queue],JSON_UNESCAPED_SLASHES);