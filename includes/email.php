<?php
declare(strict_types=1);

require_once __DIR__.'/../config/email.php';

function email_log(string $message): void
{
    $dir = __DIR__.'/../storage';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    @file_put_contents($dir.'/email.log', '['.date('Y-m-d H:i:s').'] '.$message.PHP_EOL, FILE_APPEND);
}

function smtp_read($socket): string
{
    $data = '';
    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) break;
        $data .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') break;
    }
    return $data;
}

function smtp_expect($socket, array $codes): void
{
    $reply = smtp_read($socket);
    $code = (int)substr(trim($reply), 0, 3);
    if (!in_array($code, $codes, true)) {
        throw new RuntimeException('SMTP server rejected command: '.trim($reply));
    }
}

function smtp_cmd($socket, string $command, array $codes): void
{
    fwrite($socket, $command."\r\n");
    smtp_expect($socket, $codes);
}

function normalize_header_value(string $value): string
{
    return trim(str_replace(["\r","\n"], '', $value));
}

function email_html_template(string $title, string $bodyHtml): string
{
    $church = htmlspecialchars(EMAIL_FROM_NAME, ENT_QUOTES, 'UTF-8');
    return '<!doctype html><html><body style="margin:0;background:#f5f8fb;font-family:Arial,sans-serif;color:#172b3a">
    <div style="max-width:680px;margin:30px auto;background:#fff;border:1px solid #e5ebf0;border-radius:18px;overflow:hidden">
      <div style="background:#102a43;color:#fff;padding:24px 28px">
        <div style="font-size:20px;font-weight:700">'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</div>
        <div style="font-size:12px;color:#d7e6f1;margin-top:5px">'.$church.'</div>
      </div>
      <div style="padding:28px;line-height:1.65;font-size:14px">'.$bodyHtml.'</div>
      <div style="padding:18px 28px;background:#f7fafc;color:#718096;font-size:11px">This is an automated message from the FGCK Joyland appointment system. Please do not reply if you do not need assistance.</div>
    </div></body></html>';
}

function send_smtp_email(string $to, string $toName, string $subject, string $html): bool
{
    if (!EMAIL_ENABLED) return false;
    $to = normalize_header_value($to);
    $toName = normalize_header_value($toName);
    $subject = normalize_header_value($subject);

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        email_log('Invalid recipient: '.$to);
        return false;
    }

    $errno = 0; $errstr = '';
    try {
        $transport = strtolower(EMAIL_SMTP_ENCRYPTION) === 'ssl'
            ? 'ssl://'.EMAIL_SMTP_HOST
            : EMAIL_SMTP_HOST;

        $socket = @fsockopen($transport, EMAIL_SMTP_PORT, $errno, $errstr, 15);
        if (!$socket) throw new RuntimeException("Connection failed: $errstr ($errno)");

        stream_set_timeout($socket, 15);
        smtp_expect($socket, [220]);

        smtp_cmd($socket, 'EHLO fgckjoyland.local', [250]);

        if (strtolower(EMAIL_SMTP_ENCRYPTION) === 'tls') {
            smtp_cmd($socket, 'STARTTLS', [220]);
            if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('TLS negotiation failed.');
            }
            smtp_cmd($socket, 'EHLO fgckjoyland.local', [250]);
        }

        smtp_cmd($socket, 'AUTH LOGIN', [334]);
        smtp_cmd($socket, base64_encode(EMAIL_SMTP_USERNAME), [334]);
        smtp_cmd($socket, base64_encode(EMAIL_SMTP_PASSWORD), [235]);

        smtp_cmd($socket, 'MAIL FROM:<'.normalize_header_value(EMAIL_FROM_EMAIL).'>', [250]);
        smtp_cmd($socket, 'RCPT TO:<'.$to.'>', [250,251]);
        smtp_cmd($socket, 'DATA', [354]);

        $safeSubject = '=?UTF-8?B?'.base64_encode($subject).'?=';
        $safeName = '=?UTF-8?B?'.base64_encode(EMAIL_FROM_NAME).'?=';
        $reply = normalize_header_value(EMAIL_REPLY_TO);
        $boundary = '=_FGCK_'.bin2hex(random_bytes(10));

        $headers = [
            'Date: '.date(DATE_RFC2822),
            'From: '.$safeName.' <'.normalize_header_value(EMAIL_FROM_EMAIL).'>',
            'To: '.($toName ? '=?UTF-8?B?'.base64_encode($toName).'?= <'.$to.'>' : '<'.$to.'>'),
            'Reply-To: <'.$reply.'>',
            'Subject: '.$safeSubject,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="'.$boundary.'"',
            'X-Mailer: FGCK-Joyland-Appointment-System'
        ];

        $plain = trim(html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $html)), ENT_QUOTES, 'UTF-8'));
        $message = implode("\r\n",$headers)."\r\n\r\n";
        $message .= '--'.$boundary."\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $plain."\r\n\r\n";
        $message .= '--'.$boundary."\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $html."\r\n\r\n";
        $message .= '--'.$boundary."--\r\n";

        // SMTP DATA terminator and dot-stuffing.
        $message = preg_replace('/^\./m', '..', $message);
        fwrite($socket, $message."\r\n.\r\n");
        smtp_expect($socket, [250]);

        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return true;
    } catch (Throwable $e) {
        if (isset($socket) && is_resource($socket)) @fclose($socket);
        email_log('SMTP error for '.$to.': '.$e->getMessage());
        return false;
    }
}

function send_system_email(string $to, string $toName, string $subject, string $bodyHtml): bool
{
    $html = email_html_template($subject, $bodyHtml);
    return send_smtp_email($to, $toName, $subject, $html);
}

function email_pastors_about_booking(PDO $p, int $appointmentId): void
{
    $q = $p->prepare("SELECT a.*,m.full_name,m.phone,m.email,s.appointment_date,
                      COALESCE(a.adjusted_start_time,s.start_time) AS start_time,
                      COALESCE(a.adjusted_end_time,s.end_time) AS end_time
                      FROM appointments a
                      JOIN members m ON m.id=a.member_id
                      JOIN appointment_slots s ON s.id=a.slot_id
                      WHERE a.id=?");
    $q->execute([$appointmentId]);
    $a = $q->fetch();
    if (!$a) return;

    $staff = $p->query("SELECT full_name,email FROM users WHERE role='pastor' AND status='active' AND email<>'' ORDER BY id")->fetchAll();
    if (!$staff) {
        $staff = $p->query("SELECT full_name,email FROM users WHERE role='admin' AND status='active' AND email<>'' ORDER BY id LIMIT 1")->fetchAll();
    }

    $body = '<p>A new appointment request has been submitted and is waiting for your review.</p>
      <table style="width:100%;border-collapse:collapse">
      <tr><td style="padding:7px 0"><b>Member</b></td><td>'.e($a['full_name']).'</td></tr>
      <tr><td style="padding:7px 0"><b>Phone</b></td><td>'.e($a['phone']).'</td></tr>
      <tr><td style="padding:7px 0"><b>Email</b></td><td>'.e($a['email']).'</td></tr>
      <tr><td style="padding:7px 0"><b>Date</b></td><td>'.e(fmt_date($a['appointment_date'])).'</td></tr>
      <tr><td style="padding:7px 0"><b>Time</b></td><td>'.e(fmt_time($a['start_time'])).' – '.e(fmt_time($a['end_time'])).'</td></tr>
      <tr><td style="padding:7px 0"><b>Purpose</b></td><td>'.e($a['purpose']).'</td></tr>
      <tr><td style="padding:7px 0"><b>Reference</b></td><td><b>'.e($a['appointment_no']).'</b></td></tr>
      </table>
      <p style="margin-top:22px">Please open the pastor portal to review and manage this request.</p>';

    foreach ($staff as $s) {
        send_system_email((string)$s['email'], (string)$s['full_name'], 'New appointment request – '.$a['appointment_no'], $body);
    }
}

function email_member_status_update(PDO $p, int $appointmentId, string $status): void
{
    $q = $p->prepare("SELECT a.*,m.full_name,m.email,s.appointment_date,
                      COALESCE(a.adjusted_start_time,s.start_time) AS start_time,
                      COALESCE(a.adjusted_end_time,s.end_time) AS end_time
                      FROM appointments a
                      JOIN members m ON m.id=a.member_id
                      JOIN appointment_slots s ON s.id=a.slot_id
                      WHERE a.id=?");
    $q->execute([$appointmentId]);
    $a = $q->fetch();
    if (!$a || !filter_var($a['email'], FILTER_VALIDATE_EMAIL)) return;

    $statusText = [
        'confirmed' => ['Appointment confirmed','Your appointment has been confirmed by the pastor.','success'],
        'declined' => ['Appointment declined','The pastor was unable to confirm this appointment. Please log in to the portal to choose another available slot.','warning'],
        'cancelled' => ['Appointment cancelled','This appointment has been cancelled. Please log in to the portal for more information.','warning'],
        'completed' => ['Appointment completed','Your appointment has been marked as completed. Thank you for using the FGCK Joyland appointment service.','success'],
        'no_show' => ['Appointment marked as no-show','The system has recorded that the appointment was not attended. Please contact the church if you need assistance.','warning']
    ];
    if (!isset($statusText[$status])) return;

    [$title,$intro] = $statusText[$status];
    $body = '<p>Dear '.e($a['full_name']).',</p>
      <p>'.$intro.'</p>
      <table style="width:100%;border-collapse:collapse">
      <tr><td style="padding:7px 0"><b>Reference</b></td><td>'.e($a['appointment_no']).'</td></tr>
      <tr><td style="padding:7px 0"><b>Date</b></td><td>'.e(fmt_date($a['appointment_date'])).'</td></tr>
      <tr><td style="padding:7px 0"><b>Time</b></td><td>'.e(fmt_time($a['start_time'])).' – '.e(fmt_time($a['end_time'])).'</td></tr>
      <tr><td style="padding:7px 0"><b>Purpose</b></td><td>'.e($a['purpose']).'</td></tr>
      </table>
      <p style="margin-top:20px">Please sign in to the FGCK Joyland member portal to view your appointment.</p>';
    send_system_email((string)$a['email'], (string)$a['full_name'], $title.' – '.$a['appointment_no'], $body);
}

function email_member_time_change(PDO $p, int $appointmentId, string $oldStart, string $oldEnd): void
{
    $q=$p->prepare("SELECT a.*,m.full_name,m.email,s.appointment_date,
                    COALESCE(a.adjusted_start_time,s.start_time) AS start_time,
                    COALESCE(a.adjusted_end_time,s.end_time) AS end_time
                    FROM appointments a JOIN members m ON m.id=a.member_id
                    JOIN appointment_slots s ON s.id=a.slot_id WHERE a.id=?");
    $q->execute([$appointmentId]); $a=$q->fetch();
    if(!$a || !filter_var($a['email'],FILTER_VALIDATE_EMAIL)) return;
    $body='<p>Dear '.e($a['full_name']).',</p>
      <p>The pastor has adjusted your appointment time.</p>
      <p><b>Previous:</b> '.e(fmt_time($oldStart)).' – '.e(fmt_time($oldEnd)).'<br>
      <b>New:</b> '.e(fmt_time($a['start_time'])).' – '.e(fmt_time($a['end_time'])).'<br>
      <b>Date:</b> '.e(fmt_date($a['appointment_date'])).'<br>
      <b>Reference:</b> '.e($a['appointment_no']).'</p>
      <p>Please check your member portal for the latest schedule.</p>';
    send_system_email((string)$a['email'], (string)$a['full_name'], 'Appointment time changed – '.$a['appointment_no'], $body);
}
