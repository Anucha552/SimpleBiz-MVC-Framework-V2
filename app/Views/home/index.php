<?php $this->section('title'); ?>
หน้าแรก - SimpleBiz E-Commerce
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div style="text-align: center; padding: 2rem 0;">
    <h1 style="color: #667eea; font-size: 2.5rem; margin-bottom: 1rem;">
        ยินดีต้อนรับสู่ SimpleBiz E-Commerce
    </h1>
    <p style="font-size: 1.2rem; color: #666; margin-bottom: 2rem;">
        แพลตฟอร์มอีคอมเมิร์ซที่สร้างด้วย MVC Framework
    </p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
    <!-- Card: Products -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2rem; border-radius: 8px; color: white; text-align: center;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
        <h3 style="margin-bottom: 1rem;">สินค้าทั้งหมด</h3>
        <p style="margin-bottom: 1.5rem; opacity: 0.9;">เรียกดูสินค้าคุณภาพมากมาย</p>
        <a href="/products" class="btn" style="background: white; color: #667eea;">
            ดูสินค้า
        </a>
    </div>

    <!-- Card: Cart -->
    <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 2rem; border-radius: 8px; color: white; text-align: center;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">🛒</div>
        <h3 style="margin-bottom: 1rem;">ตะกร้าสินค้า</h3>
        <p style="margin-bottom: 1.5rem; opacity: 0.9;">จัดการสินค้าในตะกร้าของคุณ</p>
        <a href="/cart" class="btn" style="background: white; color: #f5576c;">
            ดูตะกร้า
        </a>
    </div>

    <!-- Card: Orders -->
    <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 2rem; border-radius: 8px; color: white; text-align: center;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">📋</div>
        <h3 style="margin-bottom: 1rem;">คำสั่งซื้อของฉัน</h3>
        <p style="margin-bottom: 1.5rem; opacity: 0.9;">ตรวจสอบประวัติการสั่งซื้อ</p>
        <a href="/orders" class="btn" style="background: white; color: #00f2fe;">
            ดูคำสั่งซื้อ
        </a>
    </div>

    <!-- Card: Account -->
    <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); padding: 2rem; border-radius: 8px; color: white; text-align: center;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">👤</div>
        <h3 style="margin-bottom: 1rem;">บัญชีผู้ใช้</h3>
        <p style="margin-bottom: 1.5rem; opacity: 0.9;">เข้าสู่ระบบหรือสมัครสมาชิก</p>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/profile" class="btn" style="background: white; color: #38f9d7;">
                โปรไฟล์
            </a>
        <?php else: ?>
            <a href="/login" class="btn" style="background: white; color: #38f9d7;">
                เข้าสู่ระบบ
            </a>
        <?php endif; ?>
    </div>
</div>

<div style="margin-top: 3rem; padding: 2rem; background: #f8f9fa; border-radius: 8px;">
    <h2 style="text-align: center; margin-bottom: 1.5rem; color: #333;">ฟีเจอร์ของระบบ</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <div style="text-align: center; padding: 1rem;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🔒</div>
            <h4>ระบบความปลอดภัย</h4>
            <p style="font-size: 0.9rem; color: #666;">Authentication & Authorization</p>
        </div>
        <div style="text-align: center; padding: 1rem;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">💳</div>
            <h4>ระบบชำระเงิน</h4>
            <p style="font-size: 0.9rem; color: #666;">Payment Integration</p>
        </div>
        <div style="text-align: center; padding: 1rem;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🚀</div>
            <h4>Performance</h4>
            <p style="font-size: 0.9rem; color: #666;">Fast & Efficient</p>
        </div>
        <div style="text-align: center; padding: 1rem;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📱</div>
            <h4>Responsive Design</h4>
            <p style="font-size: 0.9rem; color: #666;">Mobile Friendly</p>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>
