<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

use App\Auth;
use App\Database;
use App\Repository;

\App\Auth::startSession();

function repo(): Repository
{
    static $repo = null;
    return $repo ??= new Repository(Database::pdo());
}

function auth(): Auth
{
    static $auth = null;
    return $auth ??= new Auth(Database::pdo());
}

function current_user(): ?array
{
    return auth()->currentUser();
}

function require_login(): void
{
    if (current_user() === null) {
        $next = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: login.php?next=' . urlencode($next));
        exit;
    }
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(auth()->csrfToken()) . '">';
}

function flash_set(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

/** @return array{type:string,message:string}|null */
function flash_get(): ?array
{
    if (empty($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
        return null;
    }
    $flash = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $flash;
}

function redirect(string $url, ?string $flashType = null, ?string $flashMessage = null): never
{
    if ($flashType !== null && $flashMessage !== null) {
        flash_set($flashType, $flashMessage);
    }
    header('Location: ' . $url);
    exit;
}

function new_id(): string
{
    return bin2hex(random_bytes(8));
}

function h(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function task_priorities(): array
{
    return ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];
}

function normalize_priority(?string $priority): string
{
    $priority = $priority ?? 'medium';
    return in_array($priority, ['low', 'medium', 'high'], true) ? $priority : 'medium';
}

function normalize_due_date(?string $dueDate): ?string
{
    $dueDate = trim((string) $dueDate);
    if ($dueDate === '') {
        return null;
    }
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $dueDate);
    if (!$dt || $dt->format('Y-m-d') !== $dueDate) {
        return null;
    }
    return $dueDate;
}

function task_is_overdue(array $task): bool
{
    $due = $task['due_date'] ?? null;
    if ($due === null || $due === '') {
        return false;
    }
    if (($task['status'] ?? '') === 'done') {
        return false;
    }
    return $due < gmdate('Y-m-d');
}

function find_project(string $id): ?array
{
    return repo()->findProjectForUser($id, current_user()['id'] ?? '');
}

function user_name(?string $id): string
{
    return repo()->userName($id);
}

function find_issue(string $id): ?array
{
    return repo()->findIssueForUser($id, current_user()['id'] ?? '');
}

function issue_severities(): array
{
    return ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'];
}

function issue_statuses(): array
{
    return [
        'open'        => 'Open',
        'in_progress' => 'In Progress',
        'resolved'    => 'Resolved',
        'closed'      => 'Closed',
    ];
}