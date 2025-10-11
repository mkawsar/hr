<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request Received - HR Admin</title>
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
            color: #2563eb;
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
        .next-steps {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .next-steps-title {
            font-weight: 600;
            color: #92400e;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .next-steps-content {
            color: #92400e;
            font-size: 14px;
        }
        .security-notice {
            background-color: #fef2f2;
            border: 1px solid #f87171;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .security-title {
            font-weight: 600;
            color: #dc2626;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .security-content {
            color: #dc2626;
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
        .highlight {
            background-color: #fef3c7;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">HR Admin System</div>
            <h1 class="title">Password Reset Request Received</h1>
            <div class="success-badge">✓ Request Successfully Submitted</div>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $user->name ?? 'User' }}</strong>,</p>
            
            <p>We have received your request to reset the password for your HR Admin account. Your request has been processed successfully.</p>
            
            <div class="info-box">
                <div class="info-title">
                    <span class="icon">📧</span>
                    Request Details
                </div>
                <div class="info-item"><strong>Time:</strong> {{ $requestTime->format('F j, Y \a\t g:i A T') }}</div>
                <div class="info-item"><strong>Date:</strong> {{ $requestTime->format('l, F j, Y') }}</div>
                <div class="info-item"><strong>Email:</strong> {{ $user->email ?? 'N/A' }}</div>
                @if($ipAddress)
                <div class="info-item"><strong>IP Address:</strong> {{ $ipAddress }}</div>
                @endif
                @if($userAgent)
                <div class="info-item"><strong>Device:</strong> {{ $userAgent }}</div>
                @endif
            </div>
            
            <div class="next-steps">
                <div class="next-steps-title">
                    <span class="icon">📋</span>
                    What Happens Next?
                </div>
                <div class="next-steps-content">
                    <ol style="margin: 10px 0; padding-left: 20px;">
                        <li><strong>Check Your Email:</strong> You will receive a separate email with a password reset link</li>
                        <li><strong>Click the Link:</strong> The reset link will be valid for <span class="highlight">60 minutes</span></li>
                        <li><strong>Set New Password:</strong> Follow the instructions to create a new secure password</li>
                        <li><strong>Login:</strong> Use your new password to access your account</li>
                    </ol>
                </div>
            </div>
            
            <div class="security-notice">
                <div class="security-title">
                    <span class="icon">🔒</span>
                    Important Security Information
                </div>
                <div class="security-content">
                    <p><strong>If you did not request this password reset:</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>Your account may be at risk</li>
                        <li>Please contact your system administrator immediately</li>
                        <li>Consider changing your password as a precaution</li>
                        <li>Do not click any reset links in suspicious emails</li>
                    </ul>
                </div>
            </div>
            
            <p><strong>Note:</strong> If you don't receive the password reset email within a few minutes, please check your spam folder or contact your system administrator.</p>
            
            <div class="contact-info">
                <p><strong>Need Help?</strong></p>
                <p>If you have any questions or concerns about this password reset request, please contact your system administrator or IT support team.</p>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated notification from HR Admin System</p>
            <p>For your security, please do not reply to this email</p>
            <p style="margin-top: 15px; font-size: 12px; color: #9ca3af;">
                © {{ date('Y') }} HR Admin System. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
