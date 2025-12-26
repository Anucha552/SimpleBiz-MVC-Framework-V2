<?php $this->section('title'); ?>
สมัครสมาชิก - SimpleBiz
<?php $this->endSection(); ?>

<?php $this->section('styles'); ?>
.register-container {
    max-width: 500px;
    margin: 2rem auto;
}

.register-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    padding: 2.5rem;
}

.register-header {
    text-align: center;
    margin-bottom: 2rem;
}

.register-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="register-container">
    <div class="register-card">
        <div class="register-header">
            <div class="register-icon">✨</div>
            <h1 style="margin-bottom: 0.5rem; color: #333;">สมัครสมาชิก</h1>
            <p style="color: #666;">สร้างบัญชีใหม่เพื่อเริ่มช้อปปิ้ง</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="/register" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            
            <div class="form-group">
                <label for="name">ชื่อ-นามสกุล</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       placeholder="ระบุชื่อ-นามสกุล"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                       required 
                       autofocus>
            </div>
            
            <div class="form-group">
                <label for="email">อีเมล</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       placeholder="your@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required>
                <small style="color: #666; font-size: 0.85rem;">จะใช้สำหรับเข้าสู่ระบบ</small>
            </div>
            
            <div class="form-group">
                <label for="password">รหัสผ่าน</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       placeholder="••••••••"
                       required
                       minlength="8">
                <small style="color: #666; font-size: 0.85rem;">อย่างน้อย 8 ตัวอักษร</small>
            </div>
            
            <div class="form-group">
                <label for="password_confirmation">ยืนยันรหัสผ่าน</label>
                <input type="password" 
                       id="password_confirmation" 
                       name="password_confirmation" 
                       placeholder="••••••••"
                       required
                       minlength="8">
            </div>
            
            <div class="form-group">
                <label for="phone">เบอร์โทรศัพท์ (ไม่บังคับ)</label>
                <input type="tel" 
                       id="phone" 
                       name="phone" 
                       placeholder="08X-XXX-XXXX"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: start; cursor: pointer;">
                    <input type="checkbox" 
                           name="terms" 
                           required 
                           style="width: auto; margin-right: 0.5rem; margin-top: 0.25rem;">
                    <span style="font-size: 0.9rem; color: #666;">
                        ฉันยอมรับ 
                        <a href="/terms" style="color: #667eea; text-decoration: none;">เงื่อนไขการให้บริการ</a> 
                        และ 
                        <a href="/privacy" style="color: #667eea; text-decoration: none;">นโยบายความเป็นส่วนตัว</a>
                    </span>
                </label>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; font-size: 1.1rem; padding: 0.75rem;">
                สมัครสมาชิก
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #eee;">
            <p style="color: #666;">
                มีบัญชีอยู่แล้ว? 
                <a href="/login" style="color: #667eea; text-decoration: none; font-weight: bold;">
                    เข้าสู่ระบบ
                </a>
            </p>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
document.getElementById('registerForm')?.addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmation = document.getElementById('password_confirmation').value;
    
    if (password !== confirmation) {
        e.preventDefault();
        alert('รหัสผ่านไม่ตรงกัน กรุณาลองใหม่อีกครั้ง');
        return false;
    }
    
    if (password.length < 8) {
        e.preventDefault();
        alert('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
        return false;
    }
});

// Real-time password match validation
document.getElementById('password_confirmation')?.addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmation = this.value;
    
    if (confirmation && password !== confirmation) {
        this.style.borderColor = '#dc3545';
    } else {
        this.style.borderColor = '#28a745';
    }
});
</script>
<?php $this->endSection(); ?>
