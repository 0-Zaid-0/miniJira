<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/data.php';

require_login();
$users = repo()->allUsers();

$pageTitle = 'People';
$pageSubtitle = 'Everyone with an account';
$currentPage = 'teammates';
require __DIR__ . '/includes/header.php';
?>

<section class="panel">
    <h1>People</h1>
    <p class="page-lead">Project owners can add any of these users as a project teammate from the board page.</p>
</section>

<?php if (count($users) === 0): ?>
    <section class="empty">
        <h2>No users yet</h2>
        <p class="muted">Register an account to get started.</p>
    </section>
<?php else: ?>
    <section class="list">
        <?php foreach ($users as $u): ?>
            <article class="card">
                <div class="card-main">
                    <h2>
                        <span class="avatar" aria-hidden="true"><?= h(strtoupper(substr($u['username'], 0, 1))) ?></span>
                        <?= h($u['username']) ?>
                    </h2>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>