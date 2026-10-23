<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Repository
{
    public function __construct(private PDO $pdo) {}

    // --- Projects (scoped to user) -----------------------------------

    /** @return list<array{id:string,name:string,created_at:string}> */
    public function projectsForUser(string $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, created_at FROM projects
             WHERE user_id = ?
             ORDER BY created_at ASC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findProjectForUser(string $id, string $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, created_at FROM projects WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function insertProject(string $id, string $name, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO projects (id, user_id, name, created_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$id, $userId, $name, gmdate('Y-m-d H:i:s')]);
    }

    public function renameProject(string $id, string $name, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE projects SET name = ? WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$name, $id, $userId]);
    }

    public function deleteProject(string $id, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM projects WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$id, $userId]);
    }

    // --- Teammates ---------------------------------------------------
    // Teammates are currently global. If you want them per-user too,
    // add user_id to the teammates table the same way as projects.

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

    // --- Tasks (scoped via owned project) ----------------------------

    public function tasksForProject(string $projectId, string $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.id, t.project_id, t.title, t.status, t.assignee_id
             FROM tasks t
             JOIN projects p ON p.id = t.project_id
             WHERE t.project_id = ? AND p.user_id = ?
             ORDER BY t.created_at ASC'
        );
        $stmt->execute([$projectId, $userId]);
        return $stmt->fetchAll();
    }

    public function insertTask(
        string $id,
        string $projectId,
        string $title,
        string $status,
        ?string $assigneeId,
        string $userId
    ): void {
        // Only insert if the caller owns the project.
        $stmt = $this->pdo->prepare(
            'INSERT INTO tasks (id, project_id, title, status, assignee_id, created_at)
             SELECT ?, ?, ?, ?, ?, ?
             FROM projects WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([
            $id,
            $projectId,
            $title,
            $status,
            $assigneeId ?: null,
            gmdate('Y-m-d H:i:s'),
            $projectId,
            $userId,
        ]);
    }

    public function updateTask(string $taskId, string $title, ?string $assigneeId, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tasks t
             JOIN projects p ON p.id = t.project_id
             SET t.title = ?, t.assignee_id = ?
             WHERE t.id = ? AND p.user_id = ?'
        );
        $stmt->execute([$title, $assigneeId ?: null, $taskId, $userId]);
    }

    public function moveTask(string $taskId, string $status, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tasks t
             JOIN projects p ON p.id = t.project_id
             SET t.status = ?
             WHERE t.id = ? AND p.user_id = ?'
        );
        $stmt->execute([$status, $taskId, $userId]);
    }

    public function deleteTask(string $taskId, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE t FROM tasks t
             JOIN projects p ON p.id = t.project_id
             WHERE t.id = ? AND p.user_id = ?'
        );
        $stmt->execute([$taskId, $userId]);
    }

    // --- Issues (scoped via owned project) ---------------------------

    public function issuesForProject(string $projectId, string $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT i.id, i.project_id, i.task_id, i.title, i.body, i.severity, i.status,
                    i.assignee_id, i.created_at, i.updated_at
             FROM issues i
             JOIN projects p ON p.id = i.project_id
             WHERE i.project_id = ? AND p.user_id = ?
             ORDER BY
                FIELD(i.severity, "critical", "high", "medium", "low"),
                i.created_at DESC'
        );
        $stmt->execute([$projectId, $userId]);
        return $stmt->fetchAll();
    }

    public function findIssueForUser(string $id, string $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT i.* FROM issues i
             JOIN projects p ON p.id = i.project_id
             WHERE i.id = ? AND p.user_id = ?'
        );
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function insertIssue(
        string $id,
        string $projectId,
        ?string $taskId,
        string $title,
        ?string $body,
        string $severity,
        string $status,
        ?string $assigneeId,
        string $userId
    ): void {
        $now = gmdate('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO issues
                (id, project_id, task_id, title, body, severity, status,
                 assignee_id, created_at, updated_at)
             SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
             FROM projects WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([
            $id,
            $projectId,
            $taskId ?: null,
            $title,
            $body !== null && $body !== '' ? $body : null,
            $severity,
            $status,
            $assigneeId ?: null,
            $now,
            $now,
            $projectId,
            $userId,
        ]);
    }

    public function updateIssue(
        string $id,
        string $title,
        ?string $body,
        string $severity,
        string $status,
        ?string $assigneeId,
        string $userId
    ): void {
        $stmt = $this->pdo->prepare(
            'UPDATE issues i
             JOIN projects p ON p.id = i.project_id
             SET i.title = ?, i.body = ?, i.severity = ?, i.status = ?,
                 i.assignee_id = ?, i.updated_at = ?
             WHERE i.id = ? AND p.user_id = ?'
        );
        $stmt->execute([
            $title,
            $body !== null && $body !== '' ? $body : null,
            $severity,
            $status,
            $assigneeId ?: null,
            gmdate('Y-m-d H:i:s'),
            $id,
            $userId,
        ]);
    }

    public function deleteIssue(string $id, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE i FROM issues i
             JOIN projects p ON p.id = i.project_id
             WHERE i.id = ? AND p.user_id = ?'
        );
        $stmt->execute([$id, $userId]);
    }
}