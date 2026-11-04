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
        $ok = auth()->attemptLogin(
            $_POST['username'] ?? '',
            $_POST['password'] ?? ''
        );
        if ($ok) {
            $next = $_POST['next'] ?? 'index.php';
            if (!preg_match('#^/?[A-Za-z0-9_./?=&%-]*$#', $next)) {
                $next = 'index.php';
            }
            header('Location: ' . $next);
            exit;
        }
        $error = 'Invalid username or password.';
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

$next = $_GET['next'] ?? '';
$pageTitle = 'Log in';
$pageSubtitle = '';
$currentPage = 'login';
$authLayout = true;
require __DIR__ . '/includes/header.php';
?>

<section class="panel auth-panel">
    <h1>Log in</h1>
    <p class="page-lead">Welcome back. Sign in to open your boards.</p>

    <?php if ($error): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="post" class="stack-form">
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= h($next) ?>">
        <label>
            Username
            <input type="text" name="username" required autocomplete="username"
                   value="<?= h($_POST['username'] ?? '') ?>">
        </label>
        <label>
            Password
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button type="submit">Log in</button>
    </form>

    <p class="muted auth-switch">
        No account? <a href="register.php">Register</a>
    </p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
