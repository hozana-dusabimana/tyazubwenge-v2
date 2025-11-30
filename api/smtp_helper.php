<?php
/**
 * SMTP Helper Function for sending emails via Gmail SMTP
 * This is a fallback when PHPMailer is not available
 */

function sendSMTPEmail($host, $port, $username, $password, $from_email, $from_name, $to, $subject, $html_body, $reply_to = '') {
    try {
        // Create socket connection
        $socket = @fsockopen($host, $port, $errno, $errstr, 30);
        
        if (!$socket) {
            error_log("SMTP Connection failed: $errstr ($errno)");
            return false;
        }
        
        // Read initial response
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '220') {
            fclose($socket);
            return false;
        }
        
        // Send EHLO
        fputs($socket, "EHLO " . $host . "\r\n");
        $response = fgets($socket, 515);
        
        // Send STARTTLS
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 515);
        
        if (substr($response, 0, 3) != '220') {
            fclose($socket);
            return false;
        }
        
        // Enable crypto
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return false;
        }
        
        // Send EHLO again after TLS
        fputs($socket, "EHLO " . $host . "\r\n");
        $response = fgets($socket, 515);
        
        // AUTH LOGIN
        fputs($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 515);
        
        if (substr($response, 0, 3) != '334') {
            fclose($socket);
            return false;
        }
        
        // Send username
        fputs($socket, base64_encode($username) . "\r\n");
        $response = fgets($socket, 515);
        
        if (substr($response, 0, 3) != '334') {
            fclose($socket);
            return false;
        }
        
        // Send password
        fputs($socket, base64_encode($password) . "\r\n");
        $response = fgets($socket, 515);
        
        if (substr($response, 0, 3) != '235') {
            error_log("SMTP Authentication failed: $response");
            fclose($socket);
            return false;
        }
        
        // MAIL FROM
        fputs($socket, "MAIL FROM: <{$from_email}>\r\n");
        $response = fgets($socket, 515);
        
        if (substr($response, 0, 3) != '250') {
            fclose($socket);
            return false;
        }
        
        // RCPT TO
        fputs($socket, "RCPT TO: <{$to}>\r\n");
        $response = fgets($socket, 515);
        
        if (substr($response, 0, 3) != '250') {
            fclose($socket);
            return false;
        }
        
        // DATA
        fputs($socket, "DATA\r\n");
        $response = fgets($socket, 515);
        
        if (substr($response, 0, 3) != '354') {
            fclose($socket);
            return false;
        }
        
        // Email headers and body
        $email_content = "From: {$from_name} <{$from_email}>\r\n";
        if ($reply_to) {
            $email_content .= "Reply-To: {$reply_to}\r\n";
        }
        $email_content .= "To: <{$to}>\r\n";
        $email_content .= "Subject: {$subject}\r\n";
        $email_content .= "MIME-Version: 1.0\r\n";
        $email_content .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email_content .= "\r\n";
        $email_content .= $html_body . "\r\n";
        $email_content .= ".\r\n";
        
        fputs($socket, $email_content);
        $response = fgets($socket, 515);
        
        // QUIT
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        return substr($response, 0, 3) == '250';
        
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        return false;
    }
}
?>

