<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/data.php';

if (current_user() !== null) {
    redirect('index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    auth()->verifyCsrf($_POST['_csrf'] ?? null);

    try {
        auth()->register(
            $_POST['username'] ?? '',
            $_POST['password'] ?? ''
        );
        auth()->attemptLogin($_POST['username'], $_POST['password']);
        redirect('index.php');
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = 'Register';
$pageSubtitle = '';
$currentPage = 'register';
$authLayout = true;
require __DIR__ . '/includes/header.php';
?>

<section class="panel auth-panel">
    <h1>Create account</h1>
    <p class="page-lead">Pick a username and password to start tracking work.</p>

    <?php if ($error): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="post" class="stack-form">
        <?= csrf_field() ?>
        <label>
            Username
            <input type="text" name="username" required minlength="3" maxlength="64"
                   value="<?= h($_POST['username'] ?? '') ?>" autocomplete="username">
        </label>
        <label>
            Password
            <input type="password" name="password" required minlength="8" autocomplete="new-password">
        </label>
        <button type="submit">Create account</button>
    </form>

    <p class="muted auth-switch">
        Already have an account? <a href="login.php">Log in</a>
    </p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>