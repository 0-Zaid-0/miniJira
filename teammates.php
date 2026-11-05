<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/data.php';

require_login();
$users = repo()->allUsers();
$me = current_user();

$pageTitle = 'People';
$pageSubtitle = 'Everyone with an account';
$currentPage = 'teammates';
require __DIR__ . '/includes/header.php';
?>

<section class="panel hero-panel">
    <div class="hero-copy">
        <h1>People</h1>
        <p class="page-lead">Project owners can add any of these users as a teammate from a board.</p>
    </div>
    <div class="stat-row" aria-label="People summary">
        <div class="stat-chip">
            <span class="stat-value"><?= count($users) ?></span>
            <span class="stat-label">Accounts</span>
        </div>
    </div>
</section>

<?php if (count($users) === 0): ?>
    <section class="empty">
        <div class="empty-icon" aria-hidden="true"></div>
        <h2>No users yet</h2>
        <p class="muted">Register an account to get started.</p>
    </section>
<?php else: ?>
    <section class="people-grid">
        <?php foreach ($users as $u): ?>
            <?php $isMe = ($u['id'] ?? '') === ($me['id'] ?? ''); ?>
            <article class="person-card<?= $isMe ? ' is-you' : '' ?>">
                <span class="avatar lg" aria-hidden="true"><?= h(strtoupper(substr($u['username'], 0, 1))) ?></span>
                <div>
                    <h2><?= h($u['username']) ?></h2>
                    <?php if ($isMe): ?>
                        <p class="muted">You</p>
                    <?php else: ?>
                        <p class="muted">Team member</p>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
