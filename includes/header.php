<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Mini Jira';
}
if (!isset($pageSubtitle)) {
    $pageSubtitle = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> — Mini Jira</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="wrap topbar-inner">
        <div>
            <a class="brand" href="index.php">Mini Jira <span class="badge badge-new">NEW UPDATE</span></a>
            <?php if ($pageSubtitle !== ''): ?>
                <p class="subtitle"><?= h($pageSubtitle) ?></p>
            <?php endif; ?>
        </div>
        <nav class="nav">
            <a href="index.php">Projects</a>
            <a href="teammates.php">Teammates</a>
        </nav>
    </div>
</header>
<main class="wrap content">
