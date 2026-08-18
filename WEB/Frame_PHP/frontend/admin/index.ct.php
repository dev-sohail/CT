<?php
$admin_info = $admin_info ?? [];
?>
<div class="admin-dashboard">
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION['user'] ?? 'Admin') ?></p>
</div>
