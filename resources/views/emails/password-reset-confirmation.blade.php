<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Confirmation - HR Admin</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #059669;
            margin-bottom: 10px;
        }
        .title {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .content {
            margin-bottom: 30px;
        }
        .success-badge {
            display: inline-block;
            background-color: #d1fae5;
            color: #065f46;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-title {
            font-weight: 600;
            color: #0c4a6e;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .info-item {
            margin-bottom: 8px;
            color: #0c4a6e;
        }
        .security-notice {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .security-title {
            font-weight: 600;
            color: #92400e;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .security-content {
            color: #92400e;
            font-size: 14px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            font-size: 14px;
            color: #6b7280;
            text-align: center;
        }
        .contact-info {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        .icon {
            width: 20px;
            height: 20px;
            margin-right: 8px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">HR Admin System</div>
            <h1 class="title">Password Successfully Reset</h1>
            <div class="success-badge">✓ Password Changed Successfully</div>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $user->name ?? 'User' }}</strong>,</p>
            
            <p>This email confirms that your password has been successfully reset for your HR Admin account.</p>
            
            <div class="info-box">
                <div class="info-title">
                    <span class="icon">🕒</span>
                    Reset Details
                </div>
                <div class="info-item"><strong>Time:</strong> {{ $resetTime->format('F j, Y \a\t g:i A T') }}</div>
                <div class="info-item"><strong>Date:</strong> {{ $resetTime->format('l, F j, Y') }}</div>
                @if($ipAddress)
                <div class="info-item"><strong>IP Address:</strong> {{ $ipAddress }}</div>
                @endif
                @if($userAgent)
                <div class="info-item"><strong>Device:</strong> {{ $userAgent }}</div>
                @endif
            </div>
            
            <div class="security-notice">
                <div class="security-title">
                    <span class="icon">🔒</span>
                    Security Notice
                </div>
                <div class="security-content">
                    <p><strong>If you did not reset your password:</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>Your account may have been compromised</li>
                        <li>Please contact your system administrator immediately</li>
                        <li>Consider changing your password again as a precaution</li>
                    </ul>
                </div>
            </div>
            
            <p>Your new password is now active and you can log in to your account using your new credentials.</p>
            
            <div class="contact-info">
                <p><strong>Need Help?</strong></p>
                <p>If you have any questions or concerns about this password reset, please contact your system administrator or IT support team.</p>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated security notification from HR Admin System</p>
            <p>For your security, please do not reply to this email</p>
            <p style="margin-top: 15px; font-size: 12px; color: #9ca3af;">
                © {{ date('Y') }} HR Admin System. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
