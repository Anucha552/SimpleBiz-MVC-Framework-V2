<?php $this->section('title'); ?>
เข้าสู่ระบบ - SimpleBiz
<?php $this->endSection(); ?>

<?php $this->section('styles'); ?>
.login-container {
    max-width: 450px;
    margin: 2rem auto;
}

.login-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    padding: 2.5rem;
}

.login-header {
    text-align: center;
    margin-bottom: 2rem;
}

.login-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="login-icon">🔐</div>
            <h1 style="margin-bottom: 0.5rem; color: #333;">เข้าสู่ระบบ</h1>
            <p style="color: #666;">ยินดีต้อนรับกลับมา!</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="/login">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            
            <div class="form-group">
                <label for="email">อีเมล</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       placeholder="your@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required 
                       autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">รหัสผ่าน</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       placeholder="••••••••"
                       required>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" name="remember" style="width: auto; margin-right: 0.5rem;">
                    <span style="font-size: 0.9rem;">จดจำฉัน</span>
                </label>
                
                <a href="/forgot-password" style="color: #667eea; text-decoration: none; font-size: 0.9rem;">
                    ลืมรหัสผ่าน?
                </a>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; font-size: 1.1rem; padding: 0.75rem;">
                เข้าสู่ระบบ
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #eee;">
            <p style="color: #666;">
                ยังไม่มีบัญชี? 
                <a href="/register" style="color: #667eea; text-decoration: none; font-weight: bold;">
                    สมัครสมาชิก
                </a>
            </p>
        </div>
        
        <div style="margin-top: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; text-align: center;">
            <p style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">หรือเข้าสู่ระบบด้วย</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button class="btn btn-secondary" style="flex: 1;">
                    <img src="https://cdn-icons-png.flaticon.com/512/2991/2991148.png" 
                         alt="Google" 
                         style="width: 20px; height: 20px; vertical-align: middle; margin-right: 0.5rem;">
                    Google
                </button>
                <button class="btn btn-secondary" style="flex: 1;">
                    <img src="https://cdn-icons-png.flaticon.com/512/124/124010.png" 
                         alt="Facebook" 
                         style="width: 20px; height: 20px; vertical-align: middle; margin-right: 0.5rem;">
                    Facebook
                </button>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>
