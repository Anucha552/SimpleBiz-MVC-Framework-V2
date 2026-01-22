<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->yieldSection('title', 'SimpleBiz MVC Framework') ?></title>
    
    <!-- Bootstrap 5 CSS (Local) -->
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        /* Custom Header Styling */
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        /* Main Content */
        main {
            min-height: calc(100vh - 160px);
            padding: 2rem 0;
        }
        
        .content-wrapper {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Footer */
        footer {
            background: #212529;
            color: white;
            text-align: center;
            padding: 1.5rem 0;
            margin-top: 2rem;
        }
        
        /* Custom Alert for 'error' type (Bootstrap uses 'danger') */
        .alert-error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        <?= $this->yieldSection('styles', '') ?>
    </style>
    <?= $this->yieldSection('head', '') ?>
</head>
<body>
    <!-- Bootstrap Navbar -->
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="/">
                    <i class="bi bi-code-slash"></i> SimpleBiz MVC Framework
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/"><i class="bi bi-house"></i> หน้าแรก</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/docs" target="_blank"><i class="bi bi-book"></i> เอกสาร</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="https://github.com" target="_blank"><i class="bi bi-github"></i> GitHub</a>
                        </li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/logout"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/login"><i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบ</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['flash_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php 
                    unset($_SESSION['flash_message']); 
                    unset($_SESSION['flash_type']);
                ?>
            <?php endif; ?>
            
            <div class="content-wrapper">
                <?= $this->yieldSection('content') ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> SimpleBiz MVC Framework V2. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle with Popper (Local) -->
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    
    <?= $this->yieldSection('scripts', '') ?>
</body>
</html>
