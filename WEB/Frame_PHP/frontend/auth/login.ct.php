<?php
$error = $error ?? null;
$csrf_token = $csrf_token ?? '';
?>
<div class="login-form">
    <h1>Login</h1>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <div><label>Username</label><input type="text" name="username" required></div>
        <div><label>Password</label><input type="password" name="password" required></div>
        <button type="submit">Login</button>
    </form>
</div>
