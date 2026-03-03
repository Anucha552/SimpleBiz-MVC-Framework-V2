<?php

use App\Helpers\UrlHelper;
?>

<!DOCTYPE html>
<html lang="th">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<link rel="icon" type="image/png" href="<?= UrlHelper::assetVersioned('assets/img/logo/logo.png'); ?>">
	<title>ยินดีต้อนรับ - SimpleBiz MVC Framework</title>
	<style>
		@import url("https://fonts.googleapis.com/css2?family=Fraunces:wght@500;700&family=Space+Grotesk:wght@400;500;600&display=swap");
		:root {
			--ink: #1f2937;
			--muted: #6b7280;
			--accent: #1f6f8b;
			--accent-2: #f1c40f;
			--paper: #fffaf0;
			--card: #fffdf7;
			--border: rgba(31, 41, 55, 0.1);
		}
		* { box-sizing: border-box; }
		body {
			font-family: "Space Grotesk", "Segoe UI", Tahoma, sans-serif;
			color: var(--ink);
			margin: 0;
			min-height: 100vh;
			background: #0066ff;
			display: flex;
			justify-content: center;
			align-items: stretch;
			padding: 48px 20px 64px;
		}
		.container {
			max-width: 1040px;
			width: 100%;
			background: white;
			border-radius: 20px;
			box-shadow: 0 15px 40px rgba(31, 41, 55, 0.12);
			padding: 36px 40px 44px;
			border: 1px solid var(--border);
			position: relative;
		}
		.badge {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			background: #a0ff9e;
			color: var(--ink);
			padding: 6px 12px;
			border-radius: 999px;
			font-size: 13px;
			font-weight: 600;
			letter-spacing: 0.2px;
		}
		h1 {
			font-family: "Fraunces", "Times New Roman", serif;
			color: #172554;
			margin: 14px 0 10px;
			font-size: clamp(30px, 4vw, 44px);
			line-height: 1.1;
		}
		p.lead {
			color: var(--muted);
			margin: 0 0 22px;
			font-size: 16px;
			max-width: 760px;
		}
		.grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
			gap: 16px;
			margin: 24px 0 8px;
		}
		.card {
			background: #e6e6e6;
			padding: 16px 18px;
			border-radius: 14px;
			border: 1px solid var(--border);
			box-shadow: 0 10px 20px rgba(31, 41, 55, 0.06);
		}
		.card h3 {
			margin: 0 0 8px;
			font-size: 16px;
			color: #0f172a;
		}
		.card p {
			margin: 0;
			color: var(--muted);
			font-size: 14px;
			line-height: 1.5;
		}
		.section {
			margin-top: 28px;
			padding-top: 20px;
			border-top: 1px dashed var(--border);
		}
		.section h2 {
			margin: 0 0 12px;
			font-size: 18px;
			color: #0b3a53;
		}
		.stack {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
			gap: 12px;
		}
		.stack div {
			background: rgba(255, 255, 255, 0.6);
			padding: 12px 14px;
			border-radius: 12px;
			border: 1px solid var(--border);
			font-size: 14px;
			color: var(--ink);
		}
		.footer {
			margin-top: 26px;
			display: flex;
			flex-wrap: wrap;
			gap: 10px 20px;
			color: var(--muted);
			font-size: 13px;
		}
		.footer strong { color: #0f172a; }
		code { background: rgba(31, 41, 55, 0.08); padding: 3px 6px; border-radius: 6px; }
		@media (max-width: 720px) {
			.container { padding: 28px 22px 32px; }
			.footer { flex-direction: column; }
		}
	</style>
</head>
<body>
	<div class="container">
		<span class="badge">SimpleBiz MVC Framework</span>
		<h1>ภาพรวมเฟรมเวิร์กสำหรับเว็บแอปที่โฟกัสความเร็วและความยืดหยุ่น</h1>
		<p class="lead">SimpleBiz MVC Framework ออกแบบมาเพื่อให้เริ่มโปรเจคได้เร็ว โครงสร้างชัดเจน และยืดหยุ่นสำหรับงานธุรกิจ ตั้งแต่เว็บทั่วไปไปจนถึงอีคอมเมิร์ซ มีทั้ง Core ที่ครบ และเครื่องมือเสริมให้ทีมพัฒนาทำงานได้ต่อเนื่อง</p>

		<div class="grid">
			<div class="card">
				<h3>สถาปัตยกรรม MVC</h3>
				<p>แยก Controller, Model, View ชัดเจน พร้อมโครงสร้างโฟลเดอร์เป็นมาตรฐาน ทำให้ทีมอ่านและขยายได้ง่าย</p>
			</div>
			<div class="card">
				<h3>Router & Middleware</h3>
				<p>กำหนดเส้นทางแบบยืดหยุ่น รองรับ Auth, CORS, Validation และกำหนด middleware เป็นชั้น ๆ ได้</p>
			</div>
			<div class="card">
				<h3>Database Toolkit</h3>
				<p>มี Query Builder, Migration, Seeder และ Schema ช่วยจัดการฐานข้อมูลอย่างเป็นระบบ</p>
			</div>
			<div class="card">
				<h3>Security & Observability</h3>
				<p>มี Auth, Authorization, CSRF, Logging และ Error Handling สำหรับระบบที่ต้องการความปลอดภัย</p>
			</div>
		</div>

		<div class="section">
			<h2>สิ่งที่พร้อมใช้งานทันที</h2>
			<div class="stack">
				<div><strong>Console & CLI:</strong> มี ConsoleRunner และคำสั่งสำเร็จรูปในโฟลเดอร์ <code>app/Console</code></div>
				<div><strong>Modules:</strong> รองรับการแยกโมดูลผ่าน <code>modules/</code> เพื่อขยายระบบแบบปลั๊กอิน</div>
				<div><strong>Services:</strong> รองรับบริการเสริม เช่น Notifications ใน <code>app/Services</code></div>
				<div><strong>Views & Layouts:</strong> มีโครงสร้างหน้าเว็บ, layout, component พร้อมใช้งานใน <code>app/Views</code></div>
				<div><strong>Config:</strong> ตั้งค่าแยกไฟล์ชัดเจนใน <code>config/</code> ทั้งระบบฐานข้อมูล, auth และ security</div>
				<div><strong>Testing:</strong> มีโครงสร้างทดสอบใน <code>tests/</code> พร้อม PHPUnit</div>
			</div>
		</div>

		<div class="section">
			<h2>เริ่มต้นอย่างรวดเร็ว</h2>
			<div class="stack">
				<div><strong>ดูเอกสาร:</strong> เริ่มจาก <code>docs/</code> เพื่อเข้าใจ Core และตัวอย่างการใช้งาน</div>
				<div><strong>กำหนดเส้นทาง:</strong> แก้ไขที่ <code>routes/web.php</code> หรือ <code>routes/api.php</code></div>
				<div><strong>เชื่อมฐานข้อมูล:</strong> ตั้งค่าใน <code>config/database.php</code> และรัน migration</div>
				<div><strong>สร้างหน้าแรก:</strong> ปรับแต่ง View ที่ <code>app/Views</code> ตามดีไซน์ของคุณ</div>
			</div>
		</div>

		<div class="footer">
			<div><strong>Docs:</strong> รายละเอียดทั้งหมดอยู่ใน <code>docs/</code></div>
			<div><strong>Starter:</strong> เริ่มจาก Controller ตัวอย่างใน <code>app/Controllers</code></div>
			<div><strong>Assets:</strong> จัดการไฟล์สื่อใน <code>public/assets</code></div>
		</div>
	</div>
</body>
</html>