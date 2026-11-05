<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/data.php';

require_login();
$me = current_user();
$projects = repo()->projectsForUser($me['id']);
$ownedCount = count(array_filter($projects, fn($p) => !empty($p['is_owner'])));
$sharedCount = count($projects) - $ownedCount;

$pageTitle = 'Projects';
$pageSubtitle = 'Your workspace';
$currentPage = 'projects';
require __DIR__ . '/includes/header.php';
?>

<section class="panel hero-panel">
    <div class="hero-copy">
        <h1>Projects</h1>
        <p class="page-lead">Create a project, then open its board to track tasks and issues.</p>
    </div>
    <?php if (count($projects) > 0): ?>
        <div class="stat-row" aria-label="Workspace summary">
            <div class="stat-chip">
                <span class="stat-value"><?= count($projects) ?></span>
                <span class="stat-label">Total</span>
            </div>
            <div class="stat-chip">
                <span class="stat-value"><?= $ownedCount ?></span>
                <span class="stat-label">Owned</span>
            </div>
            <div class="stat-chip">
                <span class="stat-value"><?= $sharedCount ?></span>
                <span class="stat-label">Shared</span>
            </div>
        </div>
    <?php endif; ?>

    <form class="row-form create-form" method="post" action="actions.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_project">
        <input type="text" name="name" placeholder="Project name" required>
        <select name="visibility" aria-label="Visibility">
            <option value="private">Private</option>
            <option value="global">Global</option>
        </select>
        <button type="submit">New project</button>
    </form>
</section>

<?php if (count($projects) === 0): ?>
    <section class="empty">
        <div class="empty-icon" aria-hidden="true"></div>
        <h2>No projects yet</h2>
        <p class="muted">Create your first project above to get started.</p>
    </section>
<?php else: ?>
    <section class="list project-list">
        <?php foreach (array_reverse($projects) as $project): ?>
            <article class="card project-card">
                <div class="card-main">
                    <h2><?= h($project['name']) ?></h2>
                    <div class="card-meta">
                        <?php if ($project['visibility'] === 'global'): ?>
                            <span class="badge-pill badge-global">Global</span>
                        <?php else: ?>
                            <span class="badge-pill badge-private">Private</span>
                        <?php endif; ?>
                        <?php if (empty($project['is_owner'])): ?>
                            <span class="badge-pill badge-shared">Shared</span>
                        <?php else: ?>
                            <span class="badge-pill badge-owner">Owner</span>
                        <?php endif; ?>
                        <span class="meta-pill">Created <?= h(substr($project['created_at'], 0, 10)) ?></span>
                    </div>
                </div>
                <div class="card-actions">
                    <a class="btn" href="board.php?id=<?= urlencode($project['id']) ?>">Open board</a>
                    <?php if (!empty($project['is_owner'])): ?>
                        <details class="card-menu">
                            <summary>Manage</summary>

                            <form method="post" action="actions.php" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="rename_project">
                                <input type="hidden" name="id" value="<?= h($project['id']) ?>">
                                <input type="text" name="name" value="<?= h($project['name']) ?>" required>
                                <button type="submit" class="btn light">Rename</button>
                            </form>

                            <form method="post" action="actions.php" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="set_project_visibility">
                                <input type="hidden" name="id" value="<?= h($project['id']) ?>">
                                <select name="visibility">
                                    <option value="private" <?= $project['visibility'] === 'private' ? 'selected' : '' ?>>Private</option>
                                    <option value="global"  <?= $project['visibility'] === 'global'  ? 'selected' : '' ?>>Global</option>
                                </select>
                                <button type="submit" class="btn light">Save visibility</button>
                            </form>

                            <form method="post" action="actions.php" class="inline-form"
                                  onsubmit="return confirm('Delete this project and its tasks?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_project">
                                <input type="hidden" name="id" value="<?= h($project['id']) ?>">
                                <button type="submit" class="btn danger">Delete</button>
                            </form>
                        </details>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
