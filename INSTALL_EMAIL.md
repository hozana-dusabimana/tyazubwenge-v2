# Email Setup - Quick Installation Guide

## Problem
PHP's `mail()` function doesn't work with Gmail SMTP authentication. You need PHPMailer to send emails through Gmail.

## Solution: Install PHPMailer

### Option 1: Using Composer (Recommended)

1. **Download Composer** (if not installed):
   - Visit: https://getcomposer.org/download/
   - Download `composer-setup.php`
   - Run: `php composer-setup.php`
   - This creates `composer.phar` in your project

2. **Install PHPMailer**:
   ```bash
   php composer.phar require phpmailer/phpmailer
   ```

3. **Test the contact form** - emails should now be sent to:
   - Infomitage@gmail.com
   - lanari.rw@gmail.com

### Option 2: Manual Installation

1. **Download PHPMailer**:
   - Visit: https://github.com/PHPMailer/PHPMailer
   - Click "Code" → "Download ZIP"
   - Extract to `vendor/phpmailer/phpmailer/` in your project

2. **Update autoload**:
   - The code will automatically detect PHPMailer if it's in the correct location

### Option 3: Use the Installation Script

1. **Run the installer**:
   ```bash
   php install_phpmailer.php
   ```

2. **Delete the installer** after successful installation:
   ```bash
   del install_phpmailer.php
   ```

## Gmail Configuration

The system is already configured with:
- **SMTP Host**: smtp.gmail.com
- **Port**: 587
- **Username**: lanari.rw@gmail.com
- **App Password**: vbug frba xnif cgxw

## Testing

1. Fill out the contact form on the home page
2. Submit the form
3. Check both email inboxes:
   - Infomitage@gmail.com
   - lanari.rw@gmail.com

## Troubleshooting

### If emails still don't arrive:

1. **Check PHP error logs**:
   - Location: `C:\xampp\php\logs\php_error_log` (or your XAMPP error log location)
   - Look for SMTP connection errors

2. **Verify Gmail App Password**:
   - Go to: https://myaccount.google.com/apppasswords
   - Make sure the app password is correct: `vbug frba xnif cgxw`

3. **Check Gmail Settings**:
   - Ensure "Less secure app access" is enabled (if using regular password)
   - Or use App Password (recommended)

4. **Test SMTP Connection**:
   - The code includes error logging
   - Check `api/contact.php` for any error messages

5. **Enable Debug Mode** (temporarily):
   - In `api/contact.php`, change: `$mail->SMTPDebug = 2;`
   - This will show detailed SMTP communication

## Current Status

The contact form will:
- ✅ Work with PHPMailer (if installed)
- ✅ Fall back to SMTP socket connection (if PHPMailer not available)
- ✅ Send to both email addresses
- ✅ Include all form data in HTML format

## Next Steps

1. Install PHPMailer using one of the methods above
2. Test the contact form
3. Verify emails are received in both inboxes

