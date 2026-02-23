
<!DOCTYPE html>
<html lang="en-th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?= $this->asset('assets/img/logo/logo.png') ?>">
    <title><?= $this->yield('title', 'Default Title') ?></title>
</head>
<body>

    <?= $this->yield('content') ?>
    
</body>
</html>