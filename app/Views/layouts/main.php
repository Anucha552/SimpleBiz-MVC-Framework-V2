<?php

use App\Helpers\SeoHelper;
use App\Helpers\UrlHelper;

if (SeoHelper::canonical() === null) {
    SeoHelper::setCanonical(UrlHelper::current(false));
}

?>

<!DOCTYPE html>
<html lang="en-th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?= UrlHelper::assetVersioned('assets/img/logo/logo.png'); ?>">
    <link rel="stylesheet" href="<?= UrlHelper::assetVersioned('assets/css/bootstrap.min.css') ?>">
    <?= SeoHelper::renderMetaTags($this->yield('title', 'Employee Management')) ?>
    <?= SeoHelper::renderJsonLd() ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <header>
       <nav class="navbar navbar-expand-sm navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= UrlHelper::to('/employees') ?>">CRUD Employee</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mynavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mynavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= UrlHelper::to('/employees') ?>">หน้าแรก</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= UrlHelper::to('/employees/create') ?>">เพิ่มพนักงาน</a>
                </li>
            </ul>
            <form class="d-flex" method="GET" action="<?= UrlHelper::to('/employees/search') ?>">
                <input class="form-control me-2" type="text" placeholder="Search" name="query">
                <button class="btn btn-primary" type="submit">Search</button>
            </form>
            </div>
        </div>
        </nav>
    </header>

    <main class="flex-fill my-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8 col-xl-8">
                    <?= $this->yield('content') ?>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="text-center p-3 bg-dark text-white">
            &copy; <?= date('Y') ?> CRUD Employee Management. All rights reserved.
        </div>
    </footer>

    <script src="<?= UrlHelper::assetVersioned('assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>