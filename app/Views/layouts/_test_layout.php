<?php
/** Layout for tests: exposes markers used by ViewTest */
?>
<div>LAYOUT-BEGIN</div>
<div>TITLE:<?= $this->yieldSection('title') ?></div>
<div>CONTENT:<?= strip_tags($this->yieldSection('content')) ?></div>
<div>LAYOUT-END</div>
