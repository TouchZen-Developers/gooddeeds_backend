<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP - GoodDeeds</title>
    <style>
        body {
            font-family: Arial, sans-serif;
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
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #8B5CF6;
            margin-bottom: 10px;
        }
        .otp-code {
            background-color: #f8f9fa;
            border: 2px dashed #8B5CF6;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            border-radius: 8px;
        }
        .otp-number {
            font-size: 32px;
            font-weight: bold;
            color: #8B5CF6;
            letter-spacing: 5px;
            margin: 10px 0;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            background-color: #8B5CF6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">GoodDeeds</div>
            <h2>Password Reset Verification</h2>
        </div>

        <p>Hello,</p>
        
        <p>You have requested to reset your password for your GoodDeeds admin account. Please use the following One-Time Password (OTP) to verify your identity:</p>

        <div class="otp-code">
            <p><strong>Your OTP Code:</strong></p>
            <div class="otp-number">{{ $otp }}</div>
            <p><small>This code will expire in 10 minutes</small></p>
        </div>

        <div class="warning">
            <strong>⚠️ Security Notice:</strong>
            <ul>
                <li>This OTP is valid for 10 minutes only</li>
                <li>Do not share this code with anyone</li>
                <li>If you didn't request this password reset, please ignore this email</li>
                <li>For security reasons, this OTP can only be used once</li>
            </ul>
        </div>

        <p>If you have any questions or concerns, please contact our support team.</p>

        <div class="footer">
            <p>This email was sent from GoodDeeds Admin Panel</p>
            <p>© {{ date('Y') }} GoodDeeds. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
