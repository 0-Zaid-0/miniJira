<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/data.php';

require_login();
$teammates = repo()->teammates();
$pageTitle = 'Teammates';
$pageSubtitle = 'People you can assign';
$currentPage = 'teammates';
require __DIR__ . '/includes/header.php';
?>

<section class="panel">
    <h1>Teammates</h1>
    <p class="page-lead">Add people here, then assign them on a project board.</p>

    <form class="row-form" method="post" action="actions.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_teammate">
        <input type="text" name="name" placeholder="Teammate name" required>
        <button type="submit">Add teammate</button>
    </form>
</section>

<?php if (count($teammates) === 0): ?>
    <section class="empty">
        <h2>No teammates yet</h2>
        <p class="muted">Add someone so you can assign work on the board.</p>
    </section>
<?php else: ?>
    <section class="list">
        <?php foreach ($teammates as $teammate): ?>
            <?php $initial = strtoupper(substr($teammate['name'], 0, 1)); ?>
            <article class="card">
                <div class="card-main">
                    <h2>
                        <span class="avatar" aria-hidden="true"><?= h($initial) ?></span>
                        <?= h($teammate['name']) ?>
                    </h2>
                </div>
                <div class="card-actions">
                    <details class="card-menu">
                        <summary>Manage</summary>
                        <form method="post" action="actions.php" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="rename_teammate">
                            <input type="hidden" name="id" value="<?= h($teammate['id']) ?>">
                            <input type="text" name="name" value="<?= h($teammate['name']) ?>" required>
                            <button type="submit" class="btn light">Rename</button>
                        </form>
                        <form method="post" action="actions.php" class="inline-form" onsubmit="return confirm('Remove this teammate?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete_teammate">
                            <input type="hidden" name="id" value="<?= h($teammate['id']) ?>">
                            <button type="submit" class="btn danger">Remove</button>
                        </form>
                    </details>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
