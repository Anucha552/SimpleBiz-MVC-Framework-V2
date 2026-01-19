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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
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
        <h1>ยินดีต้อนรับ!</h1>
    </div>
    <div class="content">
        <h2>สวัสดี <?= htmlspecialchars($name ?? 'คุณลูกค้า') ?>,</h2>
        <p>ขอบคุณที่สมัครสมาชิกกับ SimpleBiz MVC Framework</p>
        <p>บัญชีของคุณถูกสร้างเรียบร้อยแล้ว คุณสามารถเริ่มใช้งานได้ทันที</p>
        
        <center>
            <a href="<?= $loginUrl ?? '#' ?>" class="button">เข้าสู่ระบบ</a>
        </center>
        
        <p>หากคุณมีคำถามใดๆ กรุณาติดต่อทีมสนับสนุนของเรา</p>
    </div>
    <div class="footer">
        <p>&copy; <?= date('Y') ?> SimpleBiz MVC Framework. All rights reserved.</p>
    </div>
</body>
</html>
