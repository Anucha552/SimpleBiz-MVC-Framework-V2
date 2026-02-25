<?php
// Test layout fixture
echo 'LAYOUT-BEGIN';

echo $this->yield('title');

echo $this->yield('content');

echo 'LAYOUT-END';
