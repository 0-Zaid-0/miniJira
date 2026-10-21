<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Repository
{
    public function __construct(private PDO $pdo) {}

    /** @return list<array{id:string,name:string,created_at:string}> */
    public function projects(): array
    {
        return $this->pdo
            ->query('SELECT id, name, created_at FROM projects ORDER BY created_at ASC')
            ->fetchAll();
    }

    public function findProject(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, created_at FROM projects WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function insertProject(string $id, string $name): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO projects (id, name, created_at) VALUES (?, ?, ?)'
        );
        $stmt->execute([$id, $name, gmdate('Y-m-d H:i:s')]);
    }

    public function renameProject(string $id, string $name): void
    {
        $stmt = $this->pdo->prepare('UPDATE projects SET name = ? WHERE id = ?');
        $stmt->execute([$name, $id]);
    }

    public function deleteProject(string $id): void
    {
        // tasks cascade via FK
        $stmt = $this->pdo->prepare('DELETE FROM projects WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** @return list<array{id:string,name:string}> */
    public function teammates(): array
    {
        return $this->pdo
            ->query('SELECT id, name FROM teammates ORDER BY name ASC')
            ->fetchAll();
    }

    public function insertTeammate(string $id, string $name): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO teammates (id, name, created_at) VALUES (?, ?, ?)'
        );
        $stmt->execute([$id, $name, gmdate('Y-m-d H:i:s')]);
    }

    public function renameTeammate(string $id, string $name): void
    {
        $stmt = $this->pdo->prepare('UPDATE teammates SET name = ? WHERE id = ?');
        $stmt->execute([$name, $id]);
    }

    public function deleteTeammate(string $id): void
    {
        // assignee_id is ON DELETE SET NULL, no manual cleanup needed
        $stmt = $this->pdo->prepare('DELETE FROM teammates WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function teammateName(?string $id): string
    {
        if ($id === null || $id === '') {
            return 'Unassigned';
        }
        $stmt = $this->pdo->prepare('SELECT name FROM teammates WHERE id = ?');
        $stmt->execute([$id]);
        $name = $stmt->fetchColumn();
        return $name !== false ? (string)$name : 'Unassigned';
    }

    /** @return list<array{id:string,project_id:string,title:string,status:string,assignee_id:?string}> */
    public function tasksForProject(string $projectId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, project_id, title, status, assignee_id
             FROM tasks
             WHERE project_id = ?
             ORDER BY created_at ASC'
        );
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }

    public function insertTask(
        string $id,
        string $projectId,
        string $title,
        string $status,
        ?string $assigneeId
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tasks (id, project_id, title, status, assignee_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $projectId,
            $title,
            $status,
            $assigneeId ?: null,
            gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function updateTask(string $taskId, string $title, ?string $assigneeId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tasks SET title = ?, assignee_id = ? WHERE id = ?'
        );
        $stmt->execute([$title, $assigneeId ?: null, $taskId]);
    }

    public function moveTask(string $taskId, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE tasks SET status = ? WHERE id = ?');
        $stmt->execute([$status, $taskId]);
    }

    public function deleteTask(string $taskId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = ?');
        $stmt->execute([$taskId]);
    }
}