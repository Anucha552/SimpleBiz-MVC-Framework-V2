<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>405 - Method Not Allowed</title>
    <?php use App\Helpers\UrlHelper; ?>
    <link rel="icon" type="image/png" href="<?php echo UrlHelper::assetVersioned('assets/img/logo/logo.png'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #43cea2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-container { text-align: center; color: white; }
        .error-code {
            font-size: 150px;
            font-weight: 900;
            line-height: 1;
            text-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .error-message { font-size: 24px; margin: 20px 0; }
        .error-description { font-size: 16px; opacity: 0.9; margin-bottom: 30px; }
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
            color: #43cea2;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">405</div>
        <div class="error-message">Method Not Allowed</div>
        <div class="error-description">
            เมธอด HTTP ที่ใช้ไม่รองรับกับเส้นทางนี้
        </div>
        <a href="/" class="btn-home">กลับหน้าแรก</a>
    </div>
</body>
</html>
