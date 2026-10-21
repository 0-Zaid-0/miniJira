<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/data.php';

$data = load_data();
$pageTitle = 'Teammates';
$pageSubtitle = 'People you can assign to tasks';
require __DIR__ . '/includes/header.php';
?>

<section class="panel">
    <h1>Teammates</h1>
    <p class="muted">Add people here, then assign them on a project board.</p>

    <form class="row-form" method="post" action="actions.php">
        <input type="hidden" name="action" value="add_teammate">
        <input type="text" name="name" placeholder="Teammate name" required>
        <button type="submit">Add teammate</button>
    </form>
</section>

<?php if (count($data['teammates']) === 0): ?>
    <section class="empty">
        <h2>No teammates yet</h2>
        <p>Add someone so you can assign work on the board.</p>
    </section>
<?php else: ?>
    <section class="list">
        <?php foreach ($data['teammates'] as $teammate): ?>
            <article class="card">
                <div class="card-main">
                    <h2><?= h($teammate['name']) ?></h2>
                </div>
                <div class="card-actions">
                    <form method="post" action="actions.php" class="inline-form">
                        <input type="hidden" name="action" value="rename_teammate">
                        <input type="hidden" name="id" value="<?= h($teammate['id']) ?>">
                        <input type="text" name="name" value="<?= h($teammate['name']) ?>" required>
                        <button type="submit" class="btn light">Rename</button>
                    </form>
                    <form method="post" action="actions.php" class="inline-form" onsubmit="return confirm('Remove this teammate?');">
                        <input type="hidden" name="action" value="delete_teammate">
                        <input type="hidden" name="id" value="<?= h($teammate['id']) ?>">
                        <button type="submit" class="btn danger">Remove</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
