<?php
/** @var string $name */
/** @var string $title */

$this->section('title');
echo $title;
$this->endSection();
?>
<p>Hello, <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>!</p>
