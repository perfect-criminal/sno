<?php /** @var string $pageTitle */ ?>
    <p>This is the homepage of the ShineO Application.</p>
    <p>Environment variables are loaded and the system is operational.</p>
<?php if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
    <p>Please <a href="/login">login</a> to continue.</p>
<?php endif; ?>