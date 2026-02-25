<?php
// Test layout fixture (layouts/_test_layout.php)
echo 'LAYOUT-BEGIN';

echo $this->yield('title');

echo $this->yield('content');

echo 'LAYOUT-END';
