<?php
require_once __DIR__ . '/includes/data.php';

$projectId = $_GET['id'] ?? '';
$project = find_project($projectId);

if (!$project) {
    $pageTitle = 'Not found';
    $pageSubtitle = 'Project missing';
    require __DIR__ . '/includes/header.php';
    echo '<section class="empty"><h2>Project not found</h2><p><a href="index.php">Back to projects</a></p></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$teammates = repo()->teammates();
$tasks = repo()->tasksForProject($projectId);

$statuses = [
    'todo' => 'Todo',
    'in_progress' => 'In Progress',
    'done' => 'Done',
];

$byStatus = [
    'todo' => [],
    'in_progress' => [],
    'done' => [],
];

foreach ($tasks as $task) {
    $status = $task['status'] ?? 'todo';
    if (!isset($byStatus[$status])) {
        $status = 'todo';
    }
    $byStatus[$status][] = $task;
}

$pageTitle = $project['name'];
$pageSubtitle = 'Board';
require __DIR__ . '/includes/header.php';
?>

<p class="note">Week 1: move tasks with the status dropdown. Drag-and-drop can come later.</p>

<div class="board">
    <?php foreach ($statuses as $statusKey => $statusLabel): ?>
        <section class="column column-<?= h($statusKey) ?>">
            <header class="column-head">
                <h2><?= h($statusLabel) ?></h2>
                <span class="count"><?= count($byStatus[$statusKey]) ?></span>
            </header>

            <?php if (count($byStatus[$statusKey]) === 0): ?>
                <p class="column-empty">No tasks here</p>
            <?php endif; ?>

            <?php foreach ($byStatus[$statusKey] as $task): ?>
                <article class="task">
                    <h3><?= h($task['title']) ?></h3>
                    <p class="muted"><?= h(find_teammate_name($task['assignee_id'] ?? null)) ?></p>

                    <form method="post" action="actions.php" class="stack-form">
                        <input type="hidden" name="action" value="move_task">
                        <input type="hidden" name="task_id" value="<?= h($task['id']) ?>">
                        <input type="hidden" name="project_id" value="<?= h($projectId) ?>">
                        <label>
                            Move to
                            <select name="status" onchange="this.form.submit()">
                                <?php foreach ($statuses as $key => $label): ?>
                                    <option value="<?= h($key) ?>" <?= $task['status'] === $key ? 'selected' : '' ?>>
                                        <?= h($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </form>

                    <details>
                        <summary>Edit</summary>
                        <form method="post" action="actions.php" class="stack-form">
                            <input type="hidden" name="action" value="update_task">
                            <input type="hidden" name="task_id" value="<?= h($task['id']) ?>">
                            <input type="hidden" name="project_id" value="<?= h($projectId) ?>">
                            <input type="text" name="title" value="<?= h($task['title']) ?>" required>
                            <select name="assignee_id">
                                <option value="">Unassigned</option>
                                <?php foreach ($teammates as $teammate): ?>
                                    <option value="<?= h($teammate['id']) ?>" <?= ($task['assignee_id'] ?? '') === $teammate['id'] ? 'selected' : '' ?>>
                                        <?= h($teammate['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn light">Save</button>
                        </form>
                    </details>

                    <form method="post" action="actions.php" onsubmit="return confirm('Delete this task?');">
                        <input type="hidden" name="action" value="delete_task">
                        <input type="hidden" name="task_id" value="<?= h($task['id']) ?>">
                        <input type="hidden" name="project_id" value="<?= h($projectId) ?>">
                        <button type="submit" class="link-danger">Delete</button>
                    </form>
                </article>
            <?php endforeach; ?>

            <details class="add-task">
                <summary>+ Add task</summary>
                <form method="post" action="actions.php" class="stack-form">
                    <input type="hidden" name="action" value="add_task">
                    <input type="hidden" name="project_id" value="<?= h($projectId) ?>">
                    <input type="hidden" name="status" value="<?= h($statusKey) ?>">
                    <input type="text" name="title" placeholder="Task title" required>
                    <select name="assignee_id">
                        <option value="">Unassigned</option>
                        <?php foreach ($teammates as $teammate): ?>
                            <option value="<?= h($teammate['id']) ?>"><?= h($teammate['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Add task</button>
                </form>
            </details>
        </section>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>