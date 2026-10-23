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

function new_id(): string
{
    return bin2hex(random_bytes(8));
}

function h(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function find_project(string $id): ?array
{
    return repo()->findProject($id);
}

function find_teammate_name(?string $id): string
{
    return repo()->teammateName($id);
}

function find_issue(string $id): ?array
{
    return repo()->findIssue($id);
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