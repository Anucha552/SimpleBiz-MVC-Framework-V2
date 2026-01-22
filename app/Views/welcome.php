<?php $this->layout('layouts/main'); ?>

<?php $this->start('title'); ?>
<?= $title ?? 'ยินดีต้อนรับ' ?>
<?php $this->end(); ?>

<?php $this->start('styles'); ?>
<style>
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 4rem 0;
        margin: -2rem -2rem 2rem -2rem;
        border-radius: 8px 8px 0 0;
        text-align: center;
    }
    
    .hero-section h1 {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 1rem;
    }
    
    .hero-section .version-badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        display: inline-block;
        margin-bottom: 1rem;
    }
    
    .feature-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
    }
    
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    }
    
    .feature-icon {
        font-size: 3rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .quick-link-card {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 1.5rem;
        text-decoration: none;
        color: inherit;
        display: block;
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .quick-link-card:hover {
        border-color: #667eea;
        background: #f8f9ff;
        transform: translateX(5px);
    }
    
    .quick-link-icon {
        font-size: 2rem;
        color: #667eea;
    }
    
    .stats-section {
        background: #f8f9fa;
        padding: 2rem;
        border-radius: 10px;
        margin: 2rem 0;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #667eea;
    }
    
    .getting-started {
        background: #fff;
        border-left: 4px solid #667eea;
        padding: 1.5rem;
        margin: 2rem 0;
    }
    
    .code-block {
        background: #282c34;
        color: #abb2bf;
        padding: 1rem;
        border-radius: 5px;
        overflow-x: auto;
        font-family: 'Courier New', monospace;
    }
    
    .code-block code {
        color: #98c379;
    }
</style>
<?php $this->end(); ?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="version-badge">
            <i class="bi bi-tag"></i> Version <?= $version ?>
        </div>
        <h1><i class="bi bi-rocket-takeoff"></i> <?= $title ?></h1>
        <p class="lead">PHP MVC Framework ที่ทรงพลัง เรียบง่าย และพร้อมใช้งานจริง</p>
        <div class="mt-4">
            <a href="#features" class="btn btn-light btn-lg me-2">
                <i class="bi bi-arrow-down-circle"></i> เรียนรู้เพิ่มเติม
            </a>
            <a href="/assets-demo" class="btn btn-outline-light btn-lg me-2">
                <i class="bi bi-palette"></i> ตัวอย่าง Assets
            </a>
            <a href="https://github.com" class="btn btn-outline-light btn-lg">
                <i class="bi bi-github"></i> GitHub
            </a>
        </div>
    </div>
</div>

<!-- Stats Section -->
<div class="stats-section">
    <div class="row">
        <div class="col-md-3 col-6 stat-item mb-3 mb-md-0">
            <div class="stat-number">10+</div>
            <div class="text-muted">Core Components</div>
        </div>
        <div class="col-md-3 col-6 stat-item mb-3 mb-md-0">
            <div class="stat-number">10+</div>
            <div class="text-muted">Middleware</div>
        </div>
        <div class="col-md-3 col-6 stat-item">
            <div class="stat-number">7+</div>
            <div class="text-muted">Helper Classes</div>
        </div>
        <div class="col-md-3 col-6 stat-item">
            <div class="stat-number">20+</div>
            <div class="text-muted">Documentation Pages</div>
        </div>
    </div>
</div>

<!-- Getting Started -->
<div class="getting-started">
    <h3><i class="bi bi-play-circle"></i> เริ่มต้นอย่างรวดเร็ว</h3>
    <p>สร้าง Controller แรกของคุณด้วยคำสั่ง CLI:</p>
    <div class="code-block">
        <code>php console make:controller ProductController</code>
    </div>
    <p class="mt-3">หรือสร้าง Model พร้อม Migration:</p>
    <div class="code-block">
        <code>php console make:model Product --migration</code>
    </div>
</div>

<!-- Features Section -->
<h2 id="features" class="text-center mb-4">
    <i class="bi bi-stars"></i> คุณสมบัติหลัก
</h2>

<div class="row g-4 mb-5">
    <?php foreach ($features as $feature): ?>
    <div class="col-md-4">
        <div class="card feature-card">
            <div class="card-body text-center">
                <i class="bi <?= $feature['icon'] ?> feature-icon"></i>
                <h5 class="card-title mt-3"><?= $feature['title'] ?></h5>
                <p class="card-text text-muted"><?= $feature['description'] ?></p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Quick Links Section -->
<h2 class="text-center mb-4">
    <i class="bi bi-compass"></i> เริ่มต้นที่นี่
</h2>

<div class="row g-4 mb-4">
    <?php foreach ($quickLinks as $link): ?>
    <div class="col-md-6">
        <a href="<?= $link['url'] ?>" class="quick-link-card" target="_blank">
            <div class="d-flex align-items-start">
                <i class="bi <?= $link['icon'] ?> quick-link-icon me-3"></i>
                <div>
                    <h5 class="mb-2"><?= $link['title'] ?></h5>
                    <p class="text-muted mb-0"><?= $link['description'] ?></p>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Additional Info -->
<div class="alert alert-info">
    <h5 class="alert-heading"><i class="bi bi-info-circle"></i> ต้องการความช่วยเหลือ?</h5>
    <p class="mb-0">
        อ่านเอกสารประกอบการใช้งานได้ที่โฟลเดอร์ <code>/docs</code> 
        หรือดูตัวอย่างโค้ดได้ที่โฟลเดอร์ <code>/examples</code>
    </p>
</div>

<!-- Technology Stack -->
<div class="card mt-4">
    <div class="card-body">
        <h5 class="card-title"><i class="bi bi-stack"></i> เทคโนโลยีที่ใช้</h5>
        <div class="row mt-3">
            <div class="col-md-4">
                <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle text-success"></i> PHP 8.0+</li>
                    <li><i class="bi bi-check-circle text-success"></i> PDO (MySQL/PostgreSQL)</li>
                    <li><i class="bi bi-check-circle text-success"></i> PSR-4 Autoloading</li>
                </ul>
            </div>
            <div class="col-md-4">
                <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle text-success"></i> MVC Architecture</li>
                    <li><i class="bi bi-check-circle text-success"></i> RESTful Routing</li>
                    <li><i class="bi bi-check-circle text-success"></i> Middleware Pipeline</li>
                </ul>
            </div>
            <div class="col-md-4">
                <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle text-success"></i> Database Migrations</li>
                    <li><i class="bi bi-check-circle text-success"></i> CLI Commands</li>
                    <li><i class="bi bi-check-circle text-success"></i> PHPUnit Testing</li>
                </ul>
            </div>
        </div>
    </div>
</div>
