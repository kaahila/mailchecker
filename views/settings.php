<?php defined('APPLICATION') or die; ?>

<h1><?= $this->data('Title') ?></h1>
<div class="padded alert alert-info"><?= $this->data('Description') ?></div>
<?= $this->Form->open() ?>
<?= $this->Form->close('Save') ?>
