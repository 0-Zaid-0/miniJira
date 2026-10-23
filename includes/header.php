<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Mini Jira';
}
if (!isset($pageSubtitle)) {
    $pageSubtitle = '';
}
$me = current_user();
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
            <?php if ($me): ?>
                <a href="index.php">Projects</a>
                <a href="teammates.php">Teammates</a>
                <span class="muted" style="align-self:center;">
                    Signed in as <strong><?= h($me['username']) ?></strong>
                </span>
                <form method="post" action="logout.php" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn light">Log out</button>
                </form>
            <?php else: ?>
                <a href="login.php">Log in</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="wrap content">