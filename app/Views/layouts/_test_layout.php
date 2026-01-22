<?php
// Minimal layout for unit testing
?>
LAYOUT-BEGIN
TITLE:<?= $this->yieldSection('title') ?>
CONTENT:<?= trim(strip_tags($this->yieldSection('content'))) ?>
LAYOUT-END
