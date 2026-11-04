<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

auth()->verifyCsrf($_POST['_csrf'] ?? null);
require_login();
$me = current_user();
$userId = $me['id'];

$action = $_POST['action'] ?? '';
$r = repo();

switch ($action) {
    case 'add_project':
        $name = trim($_POST['name'] ?? '');
        $visibility = $_POST['visibility'] ?? 'private';
        if ($name !== '') {
            $r->insertProject(new_id(), $name, $visibility, $userId);
        }
        header('Location: index.php');
        exit;

    case 'rename_project':
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        if ($id !== '' && $name !== '') {
            $r->renameProject($id, $name, $userId);
        }
        header('Location: index.php');
        exit;

    case 'set_project_visibility':
        $id = $_POST['id'] ?? '';
        $visibility = $_POST['visibility'] ?? 'private';
        if ($id !== '') {
            $r->setProjectVisibility($id, $visibility, $userId);
        }
        header('Location: index.php');
        exit;

    case 'delete_project':
        $id = $_POST['id'] ?? '';
        if ($id !== '') {
            $r->deleteProject($id, $userId);
        }
        header('Location: index.php');
        exit;

    case 'add_project_teammate':
        $projectId = $_POST['project_id'] ?? '';
        $username  = trim($_POST['username'] ?? '');
        $target    = $username !== '' ? $r->findUserByUsername($username) : null;
        if ($projectId !== '' && $target) {
            $r->addProjectTeammate($projectId, $target['id'], $userId);
        }
        header('Location: board.php?id=' . urlencode($projectId));
        exit;

    case 'remove_project_teammate':
        $projectId = $_POST['project_id'] ?? '';
        $targetId  = $_POST['user_id'] ?? '';
        if ($projectId !== '' && $targetId !== '') {
            $r->removeProjectTeammate($projectId, $targetId, $userId);
        }
        header('Location: board.php?id=' . urlencode($projectId));
        exit;

    case 'add_task':
        $projectId = $_POST['project_id'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $status = $_POST['status'] ?? 'todo';
        $assigneeId = $_POST['assignee_id'] ?? '';
        if ($projectId !== '' && $title !== '') {
            $status = in_array($status, ['todo', 'in_progress', 'done'], true) ? $status : 'todo';
            $r->insertTask(new_id(), $projectId, $title, $status, $assigneeId ?: null, $userId);
        }
        header('Location: board.php?id=' . urlencode($projectId));
        exit;

    case 'update_task':
        $taskId = $_POST['task_id'] ?? '';
        $projectId = $_POST['project_id'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $assigneeId = $_POST['assignee_id'] ?? '';
        if ($taskId !== '' && $title !== '') {
            $r->updateTask($taskId, $title, $assigneeId ?: null, $userId);
        }
        header('Location: board.php?id=' . urlencode($projectId));
        exit;

    case 'move_task':
        $taskId = $_POST['task_id'] ?? '';
        $projectId = $_POST['project_id'] ?? '';
        $status = $_POST['status'] ?? 'todo';
        if ($taskId !== '' && in_array($status, ['todo', 'in_progress', 'done'], true)) {
            $r->moveTask($taskId, $status, $userId);
        }
        header('Location: board.php?id=' . urlencode($projectId));
        exit;

    case 'delete_task':
        $taskId = $_POST['task_id'] ?? '';
        $projectId = $_POST['project_id'] ?? '';
        if ($taskId !== '') {
            $r->deleteTask($taskId, $userId);
        }
        header('Location: board.php?id=' . urlencode($projectId));
        exit;

    case 'add_issue':
        $projectId  = $_POST['project_id'] ?? '';
        $taskId     = $_POST['task_id'] ?? '';
        $title      = trim($_POST['title'] ?? '');
        $body       = trim($_POST['body'] ?? '');
        $severity   = $_POST['severity'] ?? 'medium';
        $status     = $_POST['status'] ?? 'open';
        $assigneeId = $_POST['assignee_id'] ?? '';

        $validSeverities = ['low', 'medium', 'high', 'critical'];
        $validStatuses   = ['open', 'in_progress', 'resolved', 'closed'];

        if ($projectId !== '' && $title !== '') {
            $severity = in_array($severity, $validSeverities, true) ? $severity : 'medium';
            $status   = in_array($status, $validStatuses, true) ? $status : 'open';
            $r->insertIssue(
                new_id(),
                $projectId,
                $taskId !== '' ? $taskId : null,
                $title,
                $body,
                $severity,
                $status,
                $assigneeId ?: null,
                $userId,
                $userId
            );
        }
        header('Location: board.php?id=' . urlencode($projectId));
        exit;

    case 'update_issue':
        $issueId    = $_POST['issue_id'] ?? '';
        $projectId  = $_POST['project_id'] ?? '';
        $title      = trim($_POST['title'] ?? '');
        $body       = trim($_POST['body'] ?? '');
        $severity   = $_POST['severity'] ?? 'medium';
        $status     = $_POST['status'] ?? 'open';
        $assigneeId = $_POST['assignee_id'] ?? '';

        $validSeverities = ['low', 'medium', 'high', 'critical'];
        $validStatuses   = ['open', 'in_progress', 'resolved', 'closed'];

        if ($issueId !== '' && $title !== '') {
            $severity = in_array($severity, $validSeverities, true) ? $severity : 'medium';
            $status   = in_array($status, $validStatuses, true) ? $status : 'open';
            $r->updateIssue($issueId, $title, $body, $severity, $status, $assigneeId ?: null, $userId);
        }
        header('Location: board.php?id=' . urlencode($projectId));
        exit;

    case 'resolve_issue':
        $issueId   = $_POST['issue_id'] ?? '';
        $projectId = $_POST['project_id'] ?? '';
        $issue     = $issueId !== '' ? $r->findIssueForUser($issueId, $userId) : null;
        if ($issue) {
            $r->updateIssue(
                $issueId,
                $issue['title'],
                $issue['body'],
                $issue['severity'],
                'resolved',
                $issue['assignee_id'],
                $userId
            );
        }
        header('Location: board.php?id=' . urlencode($projectId));
        exit;

    case 'delete_issue':
        $issueId   = $_POST['issue_id'] ?? '';
        $projectId = $_POST['project_id'] ?? '';
        if ($issueId !== '') {
            $r->deleteIssue($issueId, $userId);
        }
        header('Location: board.php?id=' . urlencode($projectId));
        exit;

    case 'add_comment':
        $issueId   = $_POST['issue_id'] ?? '';
        $projectId = $_POST['project_id'] ?? '';
        $body      = trim($_POST['body'] ?? '');
        if ($issueId !== '' && $body !== '') {
            $r->insertComment($issueId, $userId, $body, $userId);
        }
        header('Location: board.php?id=' . urlencode($projectId));
        exit;

    case 'delete_comment':
        $commentId = $_POST['comment_id'] ?? '';
        $projectId = $_POST['project_id'] ?? '';
        if ($commentId !== '') {
            $r->deleteComment($commentId, $userId);
        }
        header('Location: board.php?id=' . urlencode($projectId));
        exit;
}

header('Location: index.php');
exit;