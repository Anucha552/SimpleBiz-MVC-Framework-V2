<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .reset-code {
            background: white;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            border-radius: 5px;
            border: 2px solid #4facfe;
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 5px;
            color: #4facfe;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #4facfe;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>รีเซ็ตรหัสผ่าน</h1>
    </div>
    <div class="content">
        <h2>สวัสดี <?= htmlspecialchars($name ?? 'คุณลูกค้า') ?>,</h2>
        <p>เราได้รับคำขอรีเซ็ตรหัสผ่านสำหรับบัญชีของคุณ</p>
        
        <?php if (!empty($resetCode)): ?>
            <p>รหัสยืนยันของคุณคือ:</p>
            <div class="reset-code"><?= htmlspecialchars($resetCode) ?></div>
            <p style="text-align: center; color: #666;">รหัสนี้จะหมดอายุใน 30 นาที</p>
        <?php endif; ?>
        
        <?php if (!empty($resetUrl)): ?>
            <center>
                <a href="<?= htmlspecialchars($resetUrl) ?>" class="button">รีเซ็ตรหัสผ่าน</a>
            </center>
        <?php endif; ?>
        
        <div class="warning">
            <strong>⚠️ คำเตือน:</strong> หากคุณไม่ได้ร้องขอการรีเซ็ตรหัสผ่านนี้ กรุณาเพิกเฉยต่ออีเมลนี้และติดต่อทีมสนับสนุนของเราทันที
        </div>
    </div>
    <div class="footer">
        <p>&copy; <?= date('Y') ?> SimpleBiz MVC Framework. All rights reserved.</p>
    </div>
</body>
</html>
