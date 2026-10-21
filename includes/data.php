<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

use App\Database;
use App\Repository;

function repo(): Repository
{
    static $repo = null;
    return $repo ??= new Repository(Database::pdo());
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