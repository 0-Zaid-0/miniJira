<?php
require_once __DIR__ . '/includes/data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$action = $_POST['action'] ?? '';
$data = load_data();

switch ($action) {
    case 'add_project':
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            $data['projects'][] = [
                'id' => new_id(),
                'name' => $name,
                'created_at' => date('Y-m-d'),
            ];
            save_data($data);
        }
        header('Location: index.php');
        exit;

    case 'rename_project':
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        if ($id !== '' && $name !== '') {
            foreach ($data['projects'] as &$project) {
                if ($project['id'] === $id) {
                    $project['name'] = $name;
                    break;
                }
            }
            unset($project);
            save_data($data);
        }
        header('Location: index.php');
        exit;

    case 'delete_project':
        $id = $_POST['id'] ?? '';
        $data['projects'] = array_values(array_filter(
            $data['projects'],
            fn($p) => $p['id'] !== $id
        ));
        $data['tasks'] = array_values(array_filter(
            $data['tasks'],
            fn($t) => $t['project_id'] !== $id
        ));
        save_data($data);
        header('Location: index.php');
        exit;

    case 'add_teammate':
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            $data['teammates'][] = [
                'id' => new_id(),
                'name' => $name,
            ];
            save_data($data);
        }
        header('Location: teammates.php');
        exit;

    case 'rename_teammate':
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        if ($id !== '' && $name !== '') {
            foreach ($data['teammates'] as &$teammate) {
                if ($teammate['id'] === $id) {
                    $teammate['name'] = $name;
                    break;
                }
            }
            unset($teammate);
            save_data($data);
        }
        header('Location: teammates.php');
        exit;

    case 'delete_teammate':
        $id = $_POST['id'] ?? '';
        $data['teammates'] = array_values(array_filter(
            $data['teammates'],
            fn($t) => $t['id'] !== $id
        ));
        foreach ($data['tasks'] as &$task) {
            if (($task['assignee_id'] ?? null) === $id) {
                $task['assignee_id'] = null;
            }
        }
        unset($task);
        save_data($data);
        header('Location: teammates.php');
        exit;

    case 'add_task':
        $projectId = $_POST['project_id'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $status = $_POST['status'] ?? 'todo';
        $assigneeId = $_POST['assignee_id'] ?? '';
        if ($projectId !== '' && $title !== '') {
            $data['tasks'][] = [
                'id' => new_id(),
                'project_id' => $projectId,
                'title' => $title,
                'status' => in_array($status, ['todo', 'in_progress', 'done'], true) ? $status : 'todo',
                'assignee_id' => $assigneeId !== '' ? $assigneeId : null,
            ];
            save_data($data);
        }
        header('Location: board.php?id=' . urlencode($projectId));
        exit;

    case 'update_task':
        $taskId = $_POST['task_id'] ?? '';
        $projectId = $_POST['project_id'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $assigneeId = $_POST['assignee_id'] ?? '';
        if ($taskId !== '' && $title !== '') {
            foreach ($data['tasks'] as &$task) {
                if ($task['id'] === $taskId) {
                    $task['title'] = $title;
                    $task['assignee_id'] = $assigneeId !== '' ? $assigneeId : null;
                    break;
                }
            }
            unset($task);
            save_data($data);
        }
        header('Location: board.php?id=' . urlencode($projectId));
        exit;

    case 'move_task':
        $taskId = $_POST['task_id'] ?? '';
        $projectId = $_POST['project_id'] ?? '';
        $status = $_POST['status'] ?? 'todo';
        if ($taskId !== '' && in_array($status, ['todo', 'in_progress', 'done'], true)) {
            foreach ($data['tasks'] as &$task) {
                if ($task['id'] === $taskId) {
                    $task['status'] = $status;
                    break;
                }
            }
            unset($task);
            save_data($data);
        }
        header('Location: board.php?id=' . urlencode($projectId));
        exit;

    case 'delete_task':
        $taskId = $_POST['task_id'] ?? '';
        $projectId = $_POST['project_id'] ?? '';
        $data['tasks'] = array_values(array_filter(
            $data['tasks'],
            fn($t) => $t['id'] !== $taskId
        ));
        save_data($data);
        header('Location: board.php?id=' . urlencode($projectId));
        exit;
}

header('Location: index.php');
exit;
