<?php $pageTitle = 'Add Employee'; $employee = null; $departments = $departments ?? []; $designations = $designations ?? []; ?>
<?php ob_start(); ?>
<?php include __DIR__ . '/form.php'; ?>
<?php // form.php already includes layout ?>
