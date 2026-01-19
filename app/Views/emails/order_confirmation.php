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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
        .order-info {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .order-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .total {
            font-size: 18px;
            font-weight: bold;
            color: #f5576c;
            text-align: right;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #f5576c;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #f5576c;
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
        <h1>ยืนยันคำสั่งซื้อ</h1>
    </div>
    <div class="content">
        <h2>สวัสดี <?= htmlspecialchars($customerName ?? 'คุณลูกค้า') ?>,</h2>
        <p>ขอบคุณที่สั่งซื้อสินค้ากับเรา</p>
        <p>เลขที่คำสั่งซื้อของคุณคือ: <strong><?= htmlspecialchars($orderNumber ?? 'N/A') ?></strong></p>
        
        <div class="order-info">
            <h3>รายการสินค้า</h3>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $item): ?>
                    <div class="order-item">
                        <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                        จำนวน: <?= $item['quantity'] ?> × ฿<?= number_format($item['price'], 2) ?>
                        = ฿<?= number_format($item['quantity'] * $item['price'], 2) ?>
                    </div>
                <?php endforeach; ?>
                <div class="total">
                    ยอดรวมทั้งหมด: ฿<?= number_format($total ?? 0, 2) ?>
                </div>
            <?php endif; ?>
        </div>
        
        <center>
            <a href="<?= $orderUrl ?? '#' ?>" class="button">ดูรายละเอียดคำสั่งซื้อ</a>
        </center>
        
        <p>เราจะดำเนินการจัดส่งสินค้าในเร็วๆ นี้</p>
    </div>
    <div class="footer">
        <p>&copy; <?= date('Y') ?> SimpleBiz MVC Framework. All rights reserved.</p>
    </div>
</body>
</html>
