<?php
require_once __DIR__ . '/includes/data.php';

require_login();
$me = current_user();

$projectId = $_GET['id'] ?? '';
$project = repo()->findProjectForUser($projectId, $me['id']);

if (!$project) {
    $pageTitle = 'Not found';
    $pageSubtitle = 'Project missing';
    require __DIR__ . '/includes/header.php';
    echo '<section class="empty"><h2>Project not found</h2><p><a href="index.php">Back to projects</a></p></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$teammates = repo()->teammates();
$tasks = repo()->tasksForProject($projectId, $me['id']);
$issues = repo()->issuesForProject($projectId, $me['id']);

$statuses = [
    'todo' => 'Todo',
    'in_progress' => 'In Progress',
    'done' => 'Done',
];

$byStatus = ['todo' => [], 'in_progress' => [], 'done' => []];
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

<p class="note">Move tasks with the status dropdown. Drag-and-drop can come later.</p>

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
                        <?= csrf_field() ?>
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
                            <?= csrf_field() ?>
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
                        <?= csrf_field() ?>
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
                    <?= csrf_field() ?>
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

<?php
$issueSeverities = issue_severities();
$issueStatuses   = issue_statuses();
$openIssues      = array_filter($issues, fn($i) => in_array($i['status'], ['open', 'in_progress'], true));
?>

<section class="panel issues-panel">
    <h2>
        Issues
        <span class="count"><?= count($openIssues) ?> open / <?= count($issues) ?> total</span>
    </h2>
    <p class="muted">Track bugs, blockers, and follow-ups for this project.</p>

    <details class="add-task" <?= count($issues) === 0 ? 'open' : '' ?>>
        <summary>+ Report issue</summary>
        <form method="post" action="actions.php" class="stack-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_issue">
            <input type="hidden" name="project_id" value="<?= h($projectId) ?>">
            <input type="text" name="title" placeholder="Issue title" required>
            <textarea name="body" placeholder="Details (optional)" rows="3"></textarea>
            <select name="severity">
                <?php foreach ($issueSeverities as $key => $label): ?>
                    <option value="<?= h($key) ?>" <?= $key === 'medium' ? 'selected' : '' ?>>
                        <?= h($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="task_id">
                <option value="">Not linked to a task</option>
                <?php foreach ($tasks as $t): ?>
                    <option value="<?= h($t['id']) ?>"><?= h($t['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="assignee_id">
                <option value="">Unassigned</option>
                <?php foreach ($teammates as $teammate): ?>
                    <option value="<?= h($teammate['id']) ?>"><?= h($teammate['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Report issue</button>
        </form>
    </details>

    <?php if (count($issues) === 0): ?>
        <p class="column-empty">No issues reported.</p>
    <?php else: ?>
        <div class="issues-list">
            <?php foreach ($issues as $issue): ?>
                <?php $isOpen = in_array($issue['status'], ['open', 'in_progress'], true); ?>
                <article class="task issue-card issue-sev-<?= h($issue['severity']) ?>">
                    <header class="issue-head">
                        <h3><?= h($issue['title']) ?></h3>
                        <span class="issue-sev-badge issue-sev-<?= h($issue['severity']) ?>">
                            <?= h($issueSeverities[$issue['severity']] ?? $issue['severity']) ?>
                        </span>
                    </header>

                    <p class="issue-meta">
                        Status:
                        <strong class="issue-status-<?= h($issue['status']) ?>">
                            <?= h($issueStatuses[$issue['status']] ?? $issue['status']) ?>
                        </strong>
                        · Assigned to <?= h(find_teammate_name($issue['assignee_id'])) ?>
                        · Reported <?= h(substr($issue['created_at'], 0, 10)) ?>
                    </p>

                    <?php if (!empty($issue['body'])): ?>
                        <p class="issue-body"><?= nl2br(h($issue['body'])) ?></p>
                    <?php endif; ?>

                    <div class="issue-actions">
                        <?php if ($isOpen): ?>
                            <form method="post" action="actions.php" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="resolve_issue">
                                <input type="hidden" name="issue_id" value="<?= h($issue['id']) ?>">
                                <input type="hidden" name="project_id" value="<?= h($projectId) ?>">
                                <button type="submit" class="btn light">Mark resolved</button>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="actions.php" class="inline-form" onsubmit="return confirm('Delete this issue?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete_issue">
                            <input type="hidden" name="issue_id" value="<?= h($issue['id']) ?>">
                            <input type="hidden" name="project_id" value="<?= h($projectId) ?>">
                            <button type="submit" class="link-danger">Delete</button>
                        </form>
                    </div>

                    <details>
                        <summary>Edit</summary>
                        <form method="post" action="actions.php" class="stack-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_issue">
                            <input type="hidden" name="issue_id" value="<?= h($issue['id']) ?>">
                            <input type="hidden" name="project_id" value="<?= h($projectId) ?>">
                            <input type="text" name="title" value="<?= h($issue['title']) ?>" required>
                            <textarea name="body" rows="3"><?= h($issue['body'] ?? '') ?></textarea>
                            <select name="severity">
                                <?php foreach ($issueSeverities as $key => $label): ?>
                                    <option value="<?= h($key) ?>" <?= $issue['severity'] === $key ? 'selected' : '' ?>>
                                        <?= h($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="status">
                                <?php foreach ($issueStatuses as $key => $label): ?>
                                    <option value="<?= h($key) ?>" <?= $issue['status'] === $key ? 'selected' : '' ?>>
                                        <?= h($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="assignee_id">
                                <option value="">Unassigned</option>
                                <?php foreach ($teammates as $teammate): ?>
                                    <option value="<?= h($teammate['id']) ?>" <?= ($issue['assignee_id'] ?? '') === $teammate['id'] ? 'selected' : '' ?>>
                                        <?= h($teammate['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn light">Save</button>
                        </form>
                    </details>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>