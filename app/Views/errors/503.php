<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 - ปิดปรับปรุงระบบ</title>
    <?php use App\Helpers\UrlHelper; ?>
    <link rel="icon" type="image/png" href="<?php echo UrlHelper::assetVersioned('assets/img/logo/logo.png'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #4facfe;
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
        .maintenance-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="maintenance-icon">🔧</div>
        <div class="error-code">503</div>
        <div class="error-message">ปิดปรับปรุงระบบชั่วคราว</div>
        <div class="error-description">
            ขณะนี้ระบบอยู่ระหว่างการปรับปรุง<br>
            กรุณากลับมาใหม่ในภายหลัง ขอบคุณครับ
        </div>
    </div>
</body>
</html>
