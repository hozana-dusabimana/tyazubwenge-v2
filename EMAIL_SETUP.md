# Email Setup Instructions

## Current Setup
The contact form is configured to send emails to:
- Infomitage@gmail.com
- lanari.rw@gmail.com

## Gmail SMTP Configuration

The current implementation uses PHP's `mail()` function, which may not work with Gmail SMTP authentication. For proper Gmail SMTP support, you need to install PHPMailer.

### Option 1: Install PHPMailer (Recommended)

1. Install Composer (if not already installed):
   ```bash
   php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
   php composer-setup.php
   php -r "unlink('composer-setup.php');"
   ```

2. Install PHPMailer:
   ```bash
   composer require phpmailer/phpmailer
   ```

3. The `api/contact.php` file will automatically detect and use PHPMailer if it's installed.

### Option 2: Configure Server SMTP

If you can't install PHPMailer, configure your server's `php.ini`:

```ini
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = lanari.rw@gmail.com
```

**Note:** This method may not work with Gmail's authentication requirements.

### Gmail App Password

The system uses the Gmail app password: `vbug frba xnif cgxw`

**Important:** 
- Make sure "Less secure app access" is enabled in Gmail settings, OR
- Use an App Password (recommended) from Google Account settings
- The app password should be: `vbug frba xnif cgxw`

### Testing

To test the contact form:
1. Fill out the contact form on the home page
2. Submit the form
3. Check both email inboxes:
   - Infomitage@gmail.com
   - lanari.rw@gmail.com

### Troubleshooting

If emails are not being sent:

1. **Check PHP error logs** for any email-related errors
2. **Verify Gmail credentials** are correct
3. **Check server configuration** allows outbound SMTP connections
4. **Install PHPMailer** for better Gmail SMTP support
5. **Check spam folders** - emails might be filtered

### Security Note

The Gmail app password is stored in `api/contact.php`. For production:
- Consider moving credentials to a config file outside the web root
- Use environment variables for sensitive data
- Restrict access to the `api/contact.php` file

