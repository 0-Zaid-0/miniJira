<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/data.php';

require_login();
$me = current_user();
$projects = repo()->projectsForUser($me['id']);

$pageTitle = 'Projects';
$pageSubtitle = 'Create projects and open a board';
require __DIR__ . '/includes/header.php';
?>

<section class="panel">
    <p class="note">If you can read this, you are running the latest code.</p>
    <h1>Projects</h1>
    <p class="muted">Add a project, then open its board to track tasks.</p>

    <form class="row-form" method="post" action="actions.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_project">
        <input type="text" name="name" placeholder="Project name" required>
        <button type="submit">New project</button>
    </form>
</section>

<?php if (count($projects) === 0): ?>
    <section class="empty">
        <h2>No projects yet</h2>
        <p>Make your first project above to get started.</p>
    </section>
<?php else: ?>
    <section class="list">
        <?php foreach (array_reverse($projects) as $project): ?>
            <article class="card">
                <div class="card-main">
                    <h2><?= h($project['name']) ?></h2>
                    <p class="muted">Created <?= h(substr($project['created_at'], 0, 10)) ?></p>
                </div>
                <div class="card-actions">
                    <a class="btn" href="board.php?id=<?= urlencode($project['id']) ?>">Open board</a>

                    <form method="post" action="actions.php" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="rename_project">
                        <input type="hidden" name="id" value="<?= h($project['id']) ?>">
                        <input type="text" name="name" value="<?= h($project['name']) ?>" required>
                        <button type="submit" class="btn light">Rename</button>
                    </form>

                    <form method="post" action="actions.php" class="inline-form" onsubmit="return confirm('Delete this project and its tasks?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_project">
                        <input type="hidden" name="id" value="<?= h($project['id']) ?>">
                        <button type="submit" class="btn danger">Delete</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>