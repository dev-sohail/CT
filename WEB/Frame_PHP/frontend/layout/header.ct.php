<?php
$currentUser = $_SESSION['user'] ?? null;
$loggedIn = $_SESSION['logged_in'] ?? false;
?>
<header>
    <nav>
        <a href="<?= APP_ROOT_URL ?>/">Home</a>
        <?php if ($loggedIn): ?>
            <a href="<?= APP_ROOT_URL ?>/portal">Portal</a>
            <a href="<?= APP_ROOT_URL ?>/logout">Logout</a>
        <?php else: ?>
            <a href="<?= APP_ROOT_URL ?>/login">Login</a>
        <?php endif; ?>
    </nav>
</header>
