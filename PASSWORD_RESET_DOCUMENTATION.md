# Password Reset & Change Password Documentation

## Overview

The HR Admin system now includes comprehensive password reset and change password functionality with email notifications and secure token-based reset links.

## Features

### 1. Password Reset via Email
- **URL**: `/password/reset`
- **Functionality**: Users can request a password reset by entering their email address
- **Email**: Custom HTML email template with secure reset link
- **Security**: Tokens expire after 60 minutes (configurable)

### 2. Password Reset Form
- **URL**: `/password/reset/{token}`
- **Functionality**: Users can set a new password using the token from their email
- **Validation**: Strong password requirements (8+ chars, mixed case, numbers, symbols)
- **Confirmation**: Automatic confirmation email sent after successful reset

### 3. Change Password (Authenticated Users)
- **URL**: `/password/change`
- **Functionality**: Logged-in users can change their password
- **Validation**: Current password verification + strong new password requirements
- **Confirmation**: Automatic confirmation email sent after successful change

### 4. Filament Integration
- **Page**: Change Password page in Filament admin panel
- **Navigation**: Available in Account group for all authenticated users
- **UI**: Modern, responsive form with validation
- **Confirmation**: Automatic confirmation email sent after successful change

## Technical Implementation

### Files Created/Modified

#### Controllers
- `app/Http/Controllers/PasswordResetController.php` - Main controller for all password operations

#### Models
- `app/Models/User.php` - Added password reset functionality and custom notification

#### Notifications
- `app/Notifications/ResetPasswordNotification.php` - Custom email template
- `app/Notifications/PasswordResetConfirmation.php` - Confirmation email notification

#### Views
- `resources/views/auth/passwords/email.blade.php` - Password reset request form
- `resources/views/auth/passwords/reset.blade.php` - Password reset form
- `resources/views/auth/change-password.blade.php` - Change password form
- `resources/views/emails/password-reset.blade.php` - Password reset email template
- `resources/views/emails/password-reset-confirmation.blade.php` - Confirmation email template
- `resources/views/filament/pages/change-password.blade.php` - Filament page

#### Pages
- `app/Filament/Pages/ChangePassword.php` - Filament page for change password

#### Routes
- `routes/web.php` - Added password reset routes

### Database
- `password_reset_tokens` table - Stores reset tokens (already exists)

## Usage

### For Users (Password Reset)

1. **Request Reset**:
   - Go to `/password/reset`
   - Enter email address
   - Click "Send Reset Link"

2. **Reset Password**:
   - Check email for reset link
   - Click link or copy URL to browser
   - Enter new password (twice)
   - Click "Reset Password"
   - Receive confirmation email

### For Authenticated Users (Change Password)

1. **Via Web Form**:
   - Go to `/password/change`
   - Enter current password
   - Enter new password (twice)
   - Click "Change Password"
   - Receive confirmation email

2. **Via Filament Admin**:
   - Login to admin panel
   - Go to "Account" → "Change Password"
   - Fill form and submit
   - Receive confirmation email

## Security Features

### Password Requirements
- Minimum 8 characters
- Must contain uppercase letters
- Must contain lowercase letters
- Must contain numbers
- Must contain special characters

### Token Security
- Tokens are hashed before storage
- Tokens expire after 60 minutes
- One-time use tokens
- Email verification required

### Rate Limiting
- Built-in Laravel rate limiting
- Prevents spam/abuse
- Configurable limits

### Confirmation Email Security
- **Automatic Notifications**: Users receive confirmation emails for all password changes
- **Security Details**: Includes timestamp, IP address, and device information
- **Fraud Detection**: Users are alerted if they didn't initiate the password change
- **Audit Trail**: Provides record of password change events
- **Immediate Alert**: Users are notified instantly when password is changed

## Email Configuration

### Required Environment Variables
```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="HR Admin System"
```

### Testing Email (Development)
```env
MAIL_MAILER=log
```
This will log emails to `storage/logs/laravel.log` instead of sending them.

## Customization

### Email Templates
Edit email templates to customize:

**Password Reset Email** (`resources/views/emails/password-reset.blade.php`):
- Company branding
- Colors and styling
- Content and messaging
- Additional security information

**Confirmation Email** (`resources/views/emails/password-reset-confirmation.blade.php`):
- Company branding
- Security information display
- Reset details (time, IP, device)
- Security warnings

### Password Requirements
Modify validation rules in:
- `app/Http/Controllers/PasswordResetController.php`
- `app/Filament/Pages/ChangePassword.php`

### Token Expiration
Configure in `config/auth.php`:
```php
'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60, // minutes
        'throttle' => 60, // seconds
    ],
],
```

## Testing

### Manual Testing
1. Create a test user
2. Request password reset
3. Check email/logs for reset link
4. Use reset link to change password
5. Test login with new password

### Automated Testing
```bash
# Test password reset functionality
php artisan tinker
```

## Troubleshooting

### Common Issues

1. **Email not sending**:
   - Check mail configuration
   - Verify SMTP credentials
   - Check firewall/network settings

2. **Token not working**:
   - Check token expiration
   - Verify database connection
   - Check URL encoding

3. **Password validation failing**:
   - Review password requirements
   - Check client-side validation
   - Verify server-side rules

### Debug Mode
Enable debug mode in `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

## Security Considerations

1. **HTTPS Required**: Always use HTTPS in production
2. **Rate Limiting**: Implement additional rate limiting if needed
3. **Email Verification**: Consider requiring email verification for new accounts
4. **Audit Logging**: Log password change events for security auditing
5. **Session Management**: Invalidate sessions after password change

## Future Enhancements

1. **Two-Factor Authentication**: Add 2FA for additional security
2. **Password History**: Prevent reuse of recent passwords
3. **Account Lockout**: Lock accounts after failed attempts
4. **Security Questions**: Alternative reset method
5. **SMS Reset**: SMS-based password reset option
