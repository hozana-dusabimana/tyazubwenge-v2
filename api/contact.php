<?php
// Start output buffering to prevent any accidental output
ob_start();

// Set error reporting but don't display errors (log them instead)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    ob_end_flush();
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    ob_end_flush();
    exit;
}

// Wrap main code in try-catch to ensure we always return a response
try {
    // Get form data
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validate input
if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    ob_end_flush();
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    ob_end_flush();
    exit;
}

// Sanitize input
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Subject mapping
$subjectMap = [
    'chemical-products' => 'Chemical Products Inquiry',
    'training' => 'Training Program Inquiry',
    'soap-making' => 'Soap Making Training',
    'wholesale' => 'Wholesale Orders',
    'general' => 'General Inquiry'
];

$subjectText = isset($subjectMap[$subject]) ? $subjectMap[$subject] : 'General Inquiry';

// Recipients - both emails will receive the message
$recipients = [
    'Infomitage@gmail.com',
    'lanari.rw@gmail.com'
];

// Email body (HTML)
$email_body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #e0e0e0; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #667eea; margin-bottom: 5px; }
        .value { padding: 8px; background: white; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; background: #f0f0f0; border-radius: 0 0 10px 10px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>New Contact Form Submission</h2>
            <p>Tyazubwenge Training Center</p>
        </div>
        <div class='content'>
            <div class='field'>
                <div class='label'>Name:</div>
                <div class='value'>{$name}</div>
            </div>
            <div class='field'>
                <div class='label'>Email:</div>
                <div class='value'>{$email}</div>
            </div>
            <div class='field'>
                <div class='label'>Subject:</div>
                <div class='value'>{$subjectText}</div>
            </div>
            <div class='field'>
                <div class='label'>Message:</div>
                <div class='value'>" . nl2br($message) . "</div>
            </div>
        </div>
        <div class='footer'>
            <p>This email was sent from the Tyazubwenge Training Center contact form.</p>
            <p><strong>Reply directly to:</strong> <a href='mailto:{$email}'>{$email}</a></p>
        </div>
    </div>
</body>
</html>
";

// Plain text version
$email_body_text = "New Contact Form Submission\n\n";
$email_body_text .= "Name: {$name}\n";
$email_body_text .= "Email: {$email}\n";
$email_body_text .= "Subject: {$subjectText}\n\n";
$email_body_text .= "Message:\n{$message}\n\n";
$email_body_text .= "---\n";
$email_body_text .= "This email was sent from the Tyazubwenge Training Center contact form.\n";
$email_body_text .= "Reply directly to: {$email}\n";

// Email configuration
$mail_subject = "Contact Form: {$subjectText} - {$name}";
$from_email = 'lanari.rw@gmail.com';
$from_name = 'Tyazubwenge Training Center';

// Gmail SMTP Configuration
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587;
$smtp_username = 'lanari.rw@gmail.com';
$smtp_password = 'vbug frba xnif cgxw'; // App password

// Try to use PHPMailer if available, otherwise use SMTP socket connection
$success_count = 0;
$error_messages = [];
$debug_info = [];

// Check if PHPMailer is available
$phpmailer_path = __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
$phpmailer_available = file_exists($phpmailer_path);
$autoload_path = __DIR__ . '/../vendor/autoload.php';
$autoload_available = file_exists($autoload_path);

$debug_info[] = "PHPMailer path exists: " . ($phpmailer_available ? 'Yes' : 'No');
$debug_info[] = "Autoload path exists: " . ($autoload_available ? 'Yes' : 'No');

if ($phpmailer_available || $autoload_available) {
    // Use PHPMailer
    try {
        if ($autoload_available) {
            require_once $autoload_path;
        } else {
            require_once $phpmailer_path;
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
        }
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtp_port;
        $mail->CharSet = 'UTF-8';
        
        // Disable SSL certificate verification for local development
        // WARNING: Only use this in development, not in production!
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        $mail->SMTPDebug = 0; // Disable debugging for production (was 2)
        $mail->Debugoutput = function($str, $level) use (&$debug_info) {
            $debug_info[] = "SMTP Debug: $str";
            error_log("SMTP: $str");
        };
        
        // Recipients
        $mail->setFrom($from_email, $from_name);
        $mail->addReplyTo($email, $name);
        
        // Add all recipients
        foreach ($recipients as $recipient) {
            $mail->addAddress($recipient);
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $mail_subject;
        $mail->Body = $email_body;
        $mail->AltBody = $email_body_text;
        
        if ($mail->send()) {
            $success_count = count($recipients);
            $debug_info[] = "Email sent successfully via PHPMailer";
        } else {
            $error_messages[] = "PHPMailer Error: " . $mail->ErrorInfo;
            $debug_info[] = "PHPMailer send failed: " . $mail->ErrorInfo;
        }
    } catch (Exception $e) {
        $error_messages[] = "PHPMailer Exception: " . $e->getMessage();
        $debug_info[] = "PHPMailer Exception: " . $e->getMessage();
        error_log("PHPMailer Exception: " . $e->getMessage());
    }
} else {
    // Use SMTP socket connection (fallback method)
    $debug_info[] = "Using SMTP helper fallback";
    require_once __DIR__ . '/smtp_helper.php';
    
    foreach ($recipients as $recipient) {
        $debug_info[] = "Attempting to send to: $recipient";
        if (sendSMTPEmail($smtp_host, $smtp_port, $smtp_username, $smtp_password, 
                         $from_email, $from_name, $recipient, $mail_subject, 
                         $email_body, "{$name} <{$email}>")) {
            $success_count++;
            $debug_info[] = "Successfully sent to: $recipient";
        } else {
            $error_messages[] = "Failed to send to {$recipient}";
            $debug_info[] = "Failed to send to: $recipient";
        }
    }
}

// Log all debug information
$log_message = "Contact Form Submission:\n";
$log_message .= "Name: $name\n";
$log_message .= "Email: $email\n";
$log_message .= "Subject: $subjectText\n";
$log_message .= "Success Count: $success_count\n";
$log_message .= "Errors: " . implode(", ", $error_messages) . "\n";
$log_message .= "Debug Info: " . implode("\n", $debug_info) . "\n";
error_log($log_message);

// Write to a log file for easier debugging
$log_file = __DIR__ . '/../logs/contact_form.log';
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}
@file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . $log_message . "\n\n", FILE_APPEND);

// Prepare response
$response_data = [];
if ($success_count > 0) {
    http_response_code(200);
    $response_data = [
        'success' => true,
        'message' => 'Thank you for your message! We will get back to you soon.',
        'sent_to' => $success_count . ' recipient(s)'
    ];
} else {
    // Return error details
    http_response_code(200);
    $response_data = [
        'success' => false,
        'message' => 'Sorry, there was an error sending your message. Please try again or contact us directly.',
        'debug' => $error_messages,
        'debug_info' => $debug_info,
        'note' => 'Check logs/contact_form.log for details'
    ];
}

// Clear any output buffer and send response
ob_clean();
$response = json_encode($response_data);

// Ensure we always output something
if (empty($response)) {
    $response = json_encode([
        'success' => false,
        'message' => 'Server error: Unable to process request',
        'error' => 'Empty response generated'
    ]);
}

echo $response;
ob_end_flush();
exit;

} catch (Exception $e) {
    // Catch any unexpected errors and return a response
    ob_clean();
    error_log("Contact form fatal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred. Please try again later.',
        'error' => $e->getMessage()
    ]);
    ob_end_flush();
    exit;
} catch (Error $e) {
    // Catch fatal errors (PHP 7+)
    ob_clean();
    error_log("Contact form fatal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred. Please try again later.',
        'error' => $e->getMessage()
    ]);
    ob_end_flush();
    exit;
}
?>
