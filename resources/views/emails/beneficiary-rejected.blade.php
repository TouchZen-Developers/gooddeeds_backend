<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Update</title>
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
            background-color: #FF6B6B;
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
        .info-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #007bff;
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
        <h1 style="margin: 0;">Application Status Update</h1>
    </div>
    
    <div class="content">
        <p>Dear {{ $userName }},</p>
        
        <p>Thank you for your interest in joining GoodDeeds as a beneficiary.</p>
        
        <p>After careful review of your application, we regret to inform you that we are unable to approve your registration at this time.</p>
        
        @if(!empty($reason))
        <div class="info-box">
            <strong>Reason:</strong><br>
            {{ $reason }}
        </div>
        @endif
        
        <h3>What Can You Do?</h3>
        <ul>
            <li>Review your application details and ensure all information is accurate</li>
            <li>Contact our support team if you have questions about this decision</li>
            <li>You may reapply in the future if your circumstances change</li>
        </ul>
        
        <p>We understand this may be disappointing news. If you believe there has been an error or you have additional information to share, please contact our support team at <a href="mailto:support@gooddeeds.com">support@gooddeeds.com</a>.</p>
        
        <p>Thank you for your understanding.</p>
        
        <p>Sincerely,<br>
        <strong>The GoodDeeds Team</strong></p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} GoodDeeds. All rights reserved.</p>
        <p>This is an automated message, please do not reply directly to this email.</p>
    </div>
</body>
</html>

