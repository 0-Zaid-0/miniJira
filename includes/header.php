<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Mini Jira';
}
if (!isset($pageSubtitle)) {
    $pageSubtitle = '';
}
if (!isset($currentPage)) {
    $currentPage = '';
}
if (!isset($authLayout)) {
    $authLayout = false;
}
$me = current_user();
$bodyClass = $authLayout ? 'auth-page' : '';
$userInitial = $me ? strtoupper(substr($me['username'], 0, 1)) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> — Mini Jira</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= h($bodyClass) ?>">
<header class="topbar">
    <div class="wrap topbar-inner">
        <div class="brand-block">
            <a class="brand" href="<?= $me ? 'index.php' : 'login.php' ?>">
                <span class="brand-mark" aria-hidden="true"></span>
                Mini Jira
            </a>
            <?php if ($pageSubtitle !== ''): ?>
                <p class="subtitle"><?= h($pageSubtitle) ?></p>
            <?php endif; ?>
        </div>
        <nav class="nav" aria-label="Main">
            <?php if ($me): ?>
                <a href="index.php" class="<?= $currentPage === 'projects' ? 'is-active' : '' ?>">Projects</a>
                <a href="teammates.php" class="<?= $currentPage === 'teammates' ? 'is-active' : '' ?>">Teammates</a>
                <span class="user-chip" title="Signed in">
                    <span class="avatar" aria-hidden="true"><?= h($userInitial) ?></span>
                    <?= h($me['username']) ?>
                </span>
                <form method="post" action="logout.php" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn light">Log out</button>
                </form>
            <?php else: ?>
                <a href="login.php" class="<?= $currentPage === 'login' ? 'is-active' : '' ?>">Log in</a>
                <a href="register.php" class="<?= $currentPage === 'register' ? 'is-active' : '' ?>">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="wrap content">
<?php
$flash = flash_get();
if ($flash):
    $flashClass = match ($flash['type'] ?? '') {
        'success' => 'alert-success',
        'error' => 'alert-error',
        default => 'alert-info',
    };
?>
    <div class="alert <?= h($flashClass) ?> flash" role="status"><?= h($flash['message'] ?? '') ?></div>
<?php endif; ?>
