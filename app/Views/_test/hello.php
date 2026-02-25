<?php
// Test view fixture
$this->section('title');
echo isset($title) ? 'TITLE:' . $title : '';
$this->endSection();

$this->section('content');
echo 'CONTENT:Hello, ' . (isset($name) ? $name : '') . '!';
$this->endSection();
