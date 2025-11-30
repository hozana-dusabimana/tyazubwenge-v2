<?php
/**
 * PHPMailer Installation Script
 * Run this file once to install PHPMailer via Composer
 */

echo "Installing PHPMailer...\n";

// Check if composer is available
$composer_path = __DIR__ . '/composer.phar';
if (!file_exists($composer_path)) {
    echo "Downloading Composer...\n";
    $composer_installer = file_get_contents('https://getcomposer.org/installer');
    file_put_contents('composer-setup.php', $composer_installer);
    
    echo "Installing Composer...\n";
    include 'composer-setup.php';
    unlink('composer-setup.php');
}

// Install PHPMailer
echo "Installing PHPMailer via Composer...\n";
$command = "php composer.phar require phpmailer/phpmailer";
exec($command, $output, $return_var);

if ($return_var === 0) {
    echo "PHPMailer installed successfully!\n";
    echo "You can now delete this file (install_phpmailer.php)\n";
} else {
    echo "Error installing PHPMailer. Please install manually:\n";
    echo "1. Download Composer: https://getcomposer.org/download/\n";
    echo "2. Run: composer require phpmailer/phpmailer\n";
}
?>

