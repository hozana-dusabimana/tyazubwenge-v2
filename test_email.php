<?php
/**
 * Email Test Script
 * Run this file to test if emails can be sent
 * Access via: http://localhost/tyazubwenge_v2/test_email.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Email Configuration Test</h2>";

// Check PHPMailer
$phpmailer_path = __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
$autoload_path = __DIR__ . '/vendor/autoload.php';

echo "<h3>1. PHPMailer Check</h3>";
if (file_exists($phpmailer_path)) {
    echo "✅ PHPMailer found at: $phpmailer_path<br>";
} else {
    echo "❌ PHPMailer NOT found at: $phpmailer_path<br>";
}

if (file_exists($autoload_path)) {
    echo "✅ Autoload found at: $autoload_path<br>";
} else {
    echo "❌ Autoload NOT found at: $autoload_path<br>";
}

// Check SMTP Helper
echo "<h3>2. SMTP Helper Check</h3>";
$smtp_helper = __DIR__ . '/api/smtp_helper.php';
if (file_exists($smtp_helper)) {
    echo "✅ SMTP Helper found<br>";
} else {
    echo "❌ SMTP Helper NOT found<br>";
}

// Test SMTP Connection
echo "<h3>3. SMTP Connection Test</h3>";
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587;

$socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
if ($socket) {
    echo "✅ Can connect to $smtp_host:$smtp_port<br>";
    fclose($socket);
} else {
    echo "❌ Cannot connect to $smtp_host:$smtp_port<br>";
    echo "Error: $errstr ($errno)<br>";
}

// Test Email Sending
echo "<h3>4. Test Email Sending</h3>";

if (file_exists($autoload_path) || file_exists($phpmailer_path)) {
    try {
        if (file_exists($autoload_path)) {
            require_once $autoload_path;
        } else {
            require_once $phpmailer_path;
            require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
            require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
        }
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'lanari.rw@gmail.com';
        $mail->Password = 'vbug frba xnif cgxw';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPDebug = 2;
        
        $mail->setFrom('lanari.rw@gmail.com', 'Test');
        $mail->addAddress('Infomitage@gmail.com');
        $mail->addAddress('lanari.rw@gmail.com');
        
        $mail->isHTML(true);
        $mail->Subject = 'Test Email from Tyazubwenge';
        $mail->Body = '<h1>Test Email</h1><p>This is a test email from the Tyazubwenge contact form system.</p>';
        
        echo "Attempting to send test email...<br>";
        echo "<pre>";
        if ($mail->send()) {
            echo "\n✅ Email sent successfully!";
        } else {
            echo "\n❌ Failed to send email: " . $mail->ErrorInfo;
        }
        echo "</pre>";
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ PHPMailer not installed. Please install it first.<br>";
    echo "<br><strong>To install PHPMailer:</strong><br>";
    echo "1. Download Composer: https://getcomposer.org/download/<br>";
    echo "2. Run: php composer.phar require phpmailer/phpmailer<br>";
}

// Check Logs
echo "<h3>5. Log Files</h3>";
$log_file = __DIR__ . '/logs/contact_form.log';
if (file_exists($log_file)) {
    echo "✅ Log file exists: $log_file<br>";
    echo "<h4>Last 10 log entries:</h4>";
    $lines = file($log_file);
    $last_lines = array_slice($lines, -10);
    echo "<pre>" . htmlspecialchars(implode('', $last_lines)) . "</pre>";
} else {
    echo "❌ Log file not found: $log_file<br>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>If PHPMailer is not installed, install it using Composer</li>";
echo "<li>Check the log file for detailed error messages</li>";
echo "<li>Verify Gmail app password is correct</li>";
echo "<li>Check if your server allows outbound SMTP connections</li>";
echo "</ul>";
?>

