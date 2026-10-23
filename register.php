<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/data.php';

if (current_user() !== null) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    auth()->verifyCsrf($_POST['_csrf'] ?? null);

    try {
        $id = auth()->register(
            $_POST['username'] ?? '',
            $_POST['password'] ?? ''
        );
        // Log them in immediately
        auth()->attemptLogin($_POST['username'], $_POST['password']);
        header('Location: index.php');
        exit;
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = 'Register';
$pageSubtitle = 'Create an account';
require __DIR__ . '/includes/header.php';
?>

<section class="panel" style="max-width: 28rem; margin: 0 auto;">
    <h1>Register</h1>

    <?php if ($error): ?>
        <p class="note" style="background:#fee2e2;border-color:#fecaca;color:#991b1b;">
            <?= h($error) ?>
        </p>
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

    <p class="muted" style="margin-top:1rem;">
        Already have an account? <a href="login.php">Log in</a>
    </p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>