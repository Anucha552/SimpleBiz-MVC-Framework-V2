<!DOCTYPE html>
<html lang="th">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<?php use App\Helpers\UrlHelper; ?>
	<link rel="icon" type="image/png" href="<?php echo UrlHelper::asset('assets/img/logo/logo.png'); ?>">
	<title>ยินดีต้อนรับ - SimpleBiz MVC Framework</title>
	<style>
		body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial; background:#f7fafc; color:#2d3748; margin:0; padding:0; background: #667eea; display: flex; justify-content: center; align-items: center; height: 100vh;}
		.container { max-width:900px; margin:48px auto; background:white; border-radius:8px; box-shadow:0 6px 24px rgba(15,23,42,0.06); padding:32px; }
		h1 { color:#0f172a; margin:0 0 8px; font-size:28px; }
		p.lead { color:#475569; margin:0 0 18px; }
		ul.features { list-style:none; padding:0; margin:18px 0; display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px; }
		ul.features li { background:#f1f5f9; padding:12px; border-radius:6px; }
		footer { margin-top:24px; color:#94a3b8; font-size:13px; }
		code { background:#0f172a10; padding:3px 6px; border-radius:4px; }
	</style>
</head>
<body>
	<div class="container">
		<h1>ยินดีต้อนรับสู่ SimpleBiz MVC Framework</h1>
		<p class="lead">เฟรมเวิร์กเบา ๆ สำหรับเริ่มโปรเจคเว็บ/อีคอมเมิร์ซ — อ่านเอกสารและรันคำสั่ง CLI ที่จำเป็นเพื่อเริ่มพัฒนาได้ทันที</p>

		<ul class="features">
			<li><strong>โครงสร้าง MVC</strong><br>แยกชัดเจนระหว่าง Controller, Model, View</li>
			<li><strong>Routing & Middleware</strong><br>รองรับ middleware, CORS, rate limit</li>
			<li><strong>Database Migrations</strong><br>ใช้ PDO + migration runner</li>
			<li><strong>Logging & Security</strong><br>ระบบล็อกและตรวจจับเหตุการณ์ความปลอดภัย</li>
		</ul>

		<footer>
			<div>อ่านเอกสารเพิ่มเติมที่โฟลเดอร์ <code>docs/</code></div>
		</footer>
	</div>
</body>
</html>