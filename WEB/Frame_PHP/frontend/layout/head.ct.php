<?php
$siteSettings = $siteSettings ?? [];
$csrf_token = $csrf_token ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Frame PHP') ?></title>
    <link rel="stylesheet" href="<?= APP_CCSS_URL ?>/app.css">
</head>
<body>
