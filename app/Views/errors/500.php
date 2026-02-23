<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - เกิดข้อผิดพลาด</title>
    <?php use App\Helpers\UrlHelper; ?>
	<link rel="icon" type="image/png" href="<?php echo UrlHelper::asset('assets/img/logo/logo.png'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5576c;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-container {
            text-align: center;
            color: white;
        }
        .error-code {
            font-size: 150px;
            font-weight: 900;
            line-height: 1;
            text-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .error-message {
            font-size: 24px;
            margin: 20px 0;
        }
        .error-description {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        .btn-home {
            background: white;
            color: black;
            padding: 12px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            color: #f5576c;
        }
        .error-details {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 10px;
            margin: 20px auto;
            max-width: 600px;
            text-align: left;
            font-size: 14px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">500</div>
        <div class="error-message">เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์</div>
        <div class="error-description">
            ขอโทษครับ เกิดข้อผิดพลาดในระบบ<br>
            ทีมงานได้รับแจ้งและกำลังแก้ไขปัญหา
        </div>
        
        <?php if (isset($error) && \App\Core\Config::get('app.env', 'development') !== 'production'): ?>
        <div class="error-details">
            <strong>Error Details (Development Mode):</strong><br>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <a href="/" class="btn-home">กลับหน้าแรก</a>
    </div>
</body>
</html>
