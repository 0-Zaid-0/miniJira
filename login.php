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
            // only allow same-origin relative redirects
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
$pageSubtitle = 'Welcome back';
require __DIR__ . '/includes/header.php';
?>

<section class="panel" style="max-width: 28rem; margin: 0 auto;">
    <h1>Log in</h1>

    <?php if ($error): ?>
        <p class="note" style="background:#fee2e2;border-color:#fecaca;color:#991b1b;">
            <?= h($error) ?>
        </p>
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

    <p class="muted" style="margin-top:1rem;">
        No account? <a href="register.php">Register</a>
    </p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>