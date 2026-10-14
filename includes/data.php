<?php
// Simple JSON file storage for Mini Jira (Week 1)

define('DATA_FILE', __DIR__ . '/../data/app.json');

function load_data(): array
{
    if (!file_exists(DATA_FILE)) {
        return [
            'projects' => [],
            'teammates' => [],
            'tasks' => [],
        ];
    }

    $raw = file_get_contents(DATA_FILE);
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        return [
            'projects' => [],
            'teammates' => [],
            'tasks' => [],
        ];
    }

    return [
        'projects' => $data['projects'] ?? [],
        'teammates' => $data['teammates'] ?? [],
        'tasks' => $data['tasks'] ?? [],
    ];
}

function save_data(array $data): void
{
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents(
        DATA_FILE,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

function new_id(): string
{
    return bin2hex(random_bytes(8));
}

function h(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function find_project(array $data, string $id): ?array
{
    foreach ($data['projects'] as $project) {
        if ($project['id'] === $id) {
            return $project;
        }
    }
    return null;
}

function find_teammate_name(array $data, ?string $id): string
{
    if (!$id) {
        return 'Unassigned';
    }
    foreach ($data['teammates'] as $teammate) {
        if ($teammate['id'] === $id) {
            return $teammate['name'];
        }
    }
    return 'Unassigned';
}
