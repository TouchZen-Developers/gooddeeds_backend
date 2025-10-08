<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Approved</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #e0e0e0;
            border-top: none;
        }
        .success-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #666666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="success-icon">✓</div>
        <h1 style="margin: 0;">Application Approved!</h1>
    </div>
    
    <div class="content">
        <p>Dear {{ $userName }},</p>
        
        <p>We're excited to inform you that your application to join GoodDeeds as a beneficiary has been <strong>approved</strong>!</p>
        
        <p>Your family is now part of the GoodDeeds community, and you can begin receiving support from our generous donors.</p>
        
        <h3>What's Next?</h3>
        <ul>
            <li>Log in to your account to complete your profile</li>
            <li>Browse available donation opportunities</li>
            <li>Connect with donors who want to help</li>
            <li>Share your story with the community</li>
        </ul>
        
        <p>If you have any questions or need assistance, please don't hesitate to reach out to our support team.</p>
        
        <p>Welcome to the GoodDeeds family!</p>
        
        <p>Warm regards,<br>
        <strong>The GoodDeeds Team</strong></p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} GoodDeeds. All rights reserved.</p>
        <p>This is an automated message, please do not reply directly to this email.</p>
    </div>
</body>
</html>

