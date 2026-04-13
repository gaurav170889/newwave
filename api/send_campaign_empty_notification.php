<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/variables.php';
require_once __DIR__ . '/../includes/functions.php';

function writeEmailNotificationLog(array $context): void {
    $logPath = dirname(__DIR__) . '/email-notification.log';
    $payload = array_merge([
        'logged_at_utc' => gmdate('Y-m-d H:i:s'),
    ], $context);

    $line = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if ($line === false) {
        $line = json_encode([
            'logged_at_utc' => gmdate('Y-m-d H:i:s'),
            'event' => 'log_encode_failed'
        ]);
    }

    @file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND);
}

function jsonResponse(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function loadMailerLibrary(): bool {
    $autoloadCandidates = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/vendor/autoload.php'
    ];

    foreach ($autoloadCandidates as $composerAutoload) {
        if (file_exists($composerAutoload)) {
            require_once $composerAutoload;
            if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                return true;
            }
        }
    }

    $phpMailerBase = __DIR__ . '/PHPMailer/src/';
    if (
        file_exists($phpMailerBase . 'Exception.php') &&
        file_exists($phpMailerBase . 'PHPMailer.php') &&
        file_exists($phpMailerBase . 'SMTP.php')
    ) {
        require_once $phpMailerBase . 'Exception.php';
        require_once $phpMailerBase . 'PHPMailer.php';
        require_once $phpMailerBase . 'SMTP.php';
        return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    }

    return false;
}

if (!loadMailerLibrary()) {
    writeEmailNotificationLog([
        'event' => 'mailer_missing',
        'success' => false,
        'message' => 'PHPMailer not found.'
    ]);
    jsonResponse([
        'success' => false,
        'message' => 'PHPMailer not found. Install it in /vendor via Composer or copy it into /api/PHPMailer/.'
    ], 500);
}

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$campaignId = isset($payload['campaign_id']) ? intval($payload['campaign_id']) : 0;
$companyId = isset($payload['company_id']) ? intval($payload['company_id']) : 0;

if ($campaignId <= 0 || $companyId <= 0) {
    writeEmailNotificationLog([
        'event' => 'invalid_request',
        'success' => false,
        'campaign_id' => $campaignId,
        'company_id' => $companyId,
        'message' => 'Missing campaign_id or company_id.'
    ]);
    jsonResponse(['success' => false, 'message' => 'Missing campaign_id or company_id.'], 400);
}

$conn = ConnectDB();
$stmt = $conn->prepare(
    "SELECT c.id, c.company_id, c.name, c.notify_no_leads_email, c.notify_email, c.notify_email_sent_at,
            c.dpd_filter_from, c.dpd_filter_to, co.name AS company_name
     FROM campaign c
     LEFT JOIN companies co ON co.id = c.company_id
     WHERE c.id = ? AND c.company_id = ?
     LIMIT 1"
);

if (!$stmt) {
    writeEmailNotificationLog([
        'event' => 'campaign_lookup_prepare_failed',
        'success' => false,
        'campaign_id' => $campaignId,
        'company_id' => $companyId,
        'message' => 'Failed to prepare campaign lookup.'
    ]);
    jsonResponse(['success' => false, 'message' => 'Failed to prepare campaign lookup.'], 500);
}

$stmt->bind_param('ii', $campaignId, $companyId);
$stmt->execute();
$result = $stmt->get_result();
$campaign = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$campaign) {
    writeEmailNotificationLog([
        'event' => 'campaign_not_found',
        'success' => false,
        'campaign_id' => $campaignId,
        'company_id' => $companyId,
        'message' => 'Campaign not found.'
    ]);
    jsonResponse(['success' => false, 'message' => 'Campaign not found.'], 404);
}

if (intval($campaign['notify_no_leads_email']) !== 1 || empty($campaign['notify_email'])) {
    writeEmailNotificationLog([
        'event' => 'notification_disabled',
        'success' => false,
        'campaign_id' => $campaignId,
        'company_id' => $companyId,
        'recipient' => $campaign['notify_email'] ?? '',
        'message' => 'Notification disabled or no email configured.'
    ]);
    jsonResponse(['success' => false, 'message' => 'Notification disabled or no email configured.']);
}

if (!empty($campaign['notify_email_sent_at'])) {
    writeEmailNotificationLog([
        'event' => 'notification_already_sent',
        'success' => false,
        'campaign_id' => $campaignId,
        'company_id' => $companyId,
        'recipient' => $campaign['notify_email'] ?? '',
        'message' => 'Notification already sent.',
        'notify_email_sent_at' => $campaign['notify_email_sent_at']
    ]);
    jsonResponse(['success' => false, 'message' => 'Notification already sent.']);
}

$claimStmt = $conn->prepare(
    "UPDATE campaign
     SET notify_email_sent_at = UTC_TIMESTAMP()
     WHERE id = ? AND company_id = ?
       AND notify_no_leads_email = 1
       AND notify_email IS NOT NULL
       AND notify_email <> ''
       AND notify_email_sent_at IS NULL"
);

if (!$claimStmt) {
    writeEmailNotificationLog([
        'event' => 'notification_claim_prepare_failed',
        'success' => false,
        'campaign_id' => $campaignId,
        'company_id' => $companyId,
        'recipient' => $campaign['notify_email'] ?? '',
        'message' => 'Failed to reserve notification send.'
    ]);
    jsonResponse(['success' => false, 'message' => 'Failed to reserve notification send.'], 500);
}

$claimStmt->bind_param('ii', $campaignId, $companyId);
$claimStmt->execute();
$claimed = $claimStmt->affected_rows > 0;
$claimStmt->close();

if (!$claimed) {
    writeEmailNotificationLog([
        'event' => 'notification_not_claimed',
        'success' => false,
        'campaign_id' => $campaignId,
        'company_id' => $companyId,
        'recipient' => $campaign['notify_email'] ?? '',
        'message' => 'Notification already claimed or sent.'
    ]);
    jsonResponse(['success' => false, 'message' => 'Notification already claimed or sent.']);
}

$dpdSummary = 'All currently eligible numbers have been dialed.';
if ($campaign['dpd_filter_from'] !== null && $campaign['dpd_filter_to'] !== null) {
    $dpdSummary = 'No more numbers were found inside the active DPD/PST range of ' . $campaign['dpd_filter_from'] . ' to ' . $campaign['dpd_filter_to'] . '.';
}

try {
    if (empty(SMTP_HOST) || empty(SMTP_FROM_EMAIL)) {
        throw new \RuntimeException('SMTP settings are missing. Update includes/variables.php or server environment variables first.');
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->Port = (int) SMTP_PORT;
    $mail->Timeout = 8;
    $mail->SMTPKeepAlive = false;
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function ($message, $level) use ($campaignId, $companyId, $campaign) {
        writeEmailNotificationLog([
            'event' => 'smtp_debug',
            'success' => null,
            'campaign_id' => $campaignId,
            'company_id' => $companyId,
            'campaign_name' => $campaign['name'] ?? '',
            'recipient' => $campaign['notify_email'] ?? '',
            'debug_level' => $level,
            'message' => trim((string) $message)
        ]);
    };

    if (!empty(SMTP_USERNAME)) {
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
    } else {
        $mail->SMTPAuth = false;
    }

    if (!empty(SMTP_SECURE) && in_array(strtolower((string) SMTP_SECURE), ['ssl', 'tls'], true)) {
        $mail->SMTPSecure = strtolower((string) SMTP_SECURE);
    }

    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($campaign['notify_email']);
    $mail->isHTML(true);
    $mail->Subject = 'Campaign completed dialing - ' . $campaign['name'];
    $mail->Body = '
        <h3>Outbound dialer notification</h3>
        <p><strong>Company:</strong> ' . htmlspecialchars((string) ($campaign['company_name'] ?? 'N/A')) . '</p>
        <p><strong>Campaign:</strong> ' . htmlspecialchars((string) $campaign['name']) . '</p>
        <p>' . htmlspecialchars($dpdSummary) . '</p>
        <p>This email is sent only once for each campaign run.</p>
        <p><strong>Sent at:</strong> ' . date('Y-m-d H:i:s') . '</p>
    ';
    $mail->AltBody = "Outbound dialer notification\n"
        . "Company: " . ($campaign['company_name'] ?? 'N/A') . "\n"
        . "Campaign: " . $campaign['name'] . "\n"
        . $dpdSummary . "\n"
        . "This email is sent only once for each campaign run.\n"
        . "Sent at: " . date('Y-m-d H:i:s');

    $mail->send();

    writeEmailNotificationLog([
        'event' => 'notification_sent',
        'success' => true,
        'campaign_id' => $campaignId,
        'company_id' => $companyId,
        'campaign_name' => $campaign['name'] ?? '',
        'recipient' => $campaign['notify_email'] ?? '',
        'message' => 'Notification sent successfully.'
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'Notification sent successfully.',
        'email' => $campaign['notify_email']
    ]);
} catch (Throwable $e) {
    $resetStmt = $conn->prepare("UPDATE campaign SET notify_email_sent_at = NULL WHERE id = ? AND company_id = ?");
    if ($resetStmt) {
        $resetStmt->bind_param('ii', $campaignId, $companyId);
        $resetStmt->execute();
        $resetStmt->close();
    }

    writeEmailNotificationLog([
        'event' => 'notification_failed',
        'success' => false,
        'campaign_id' => $campaignId,
        'company_id' => $companyId,
        'campaign_name' => $campaign['name'] ?? '',
        'recipient' => $campaign['notify_email'] ?? '',
        'message' => $e->getMessage()
    ]);

    jsonResponse([
        'success' => false,
        'message' => 'Email send failed: ' . $e->getMessage()
    ], 500);
}
