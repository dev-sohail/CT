<?php
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$basePath = $isAdmin ? APP_ADMIN_URL : APP_PORTALS_URL;
?>
<nav id="float-nav">
    <?php if ($isAdmin): ?>
        <a href="<?= $basePath ?>">Dashboard</a>
        <a href="<?= $basePath ?>/settings">Settings</a>
    <?php else: ?>
        <a href="<?= $basePath ?>">Portal</a>
    <?php endif; ?>
</nav>
