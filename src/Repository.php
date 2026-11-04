<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Repository
{
    public function __construct(private PDO $pdo) {}

    // --- Visibility predicate ----------------------------------------
    //
    // A user can see a project if they own it, the project is global,
    // or they are on its teammate roster. The same predicate is used
    // for reads and writes so an invitee can both view and edit.
    //
    // Placeholders, in order: userId (owner), userId (teammate).

    private function visibleProjectWhere(string $projectAlias = 'p'): string
    {
        return "({$projectAlias}.user_id = ? OR {$projectAlias}.visibility = 'global'
                 OR EXISTS (SELECT 1 FROM project_teammates pt
                            WHERE pt.project_id = {$projectAlias}.id AND pt.user_id = ?))";
    }

    // --- Projects ----------------------------------------------------

    public function projectsForUser(string $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.name, p.visibility, p.created_at, p.user_id,
                    (p.user_id = ?) AS is_owner
             FROM projects p
             WHERE ' . $this->visibleProjectWhere('p') . '
             ORDER BY p.created_at ASC'
        );
        // is_owner placeholder, then the two visibleProjectWhere placeholders.
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    public function findProjectForUser(string $id, string $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.name, p.visibility, p.created_at, p.user_id,
                    (p.user_id = ?) AS is_owner
             FROM projects p
             WHERE p.id = ? AND ' . $this->visibleProjectWhere('p')
        );
        // is_owner, project id, then the two visibleProjectWhere placeholders.
        $stmt->execute([$userId, $id, $userId, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function insertProject(string $id, string $name, string $visibility, string $userId): void
    {
        if (!in_array($visibility, ['private', 'global'], true)) {
            $visibility = 'private';
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO projects (id, user_id, name, visibility, created_at)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$id, $userId, $name, $visibility, gmdate('Y-m-d H:i:s')]);
    }

    public function renameProject(string $id, string $name, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE projects SET name = ? WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$name, $id, $userId]);
    }

    public function setProjectVisibility(string $id, string $visibility, string $userId): void
    {
        if (!in_array($visibility, ['private', 'global'], true)) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'UPDATE projects SET visibility = ? WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$visibility, $id, $userId]);
    }

    public function deleteProject(string $id, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM projects WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$id, $userId]);
    }

    // --- Users ------------------------------------------------------

    public function allUsers(): array
    {
        return $this->pdo
            ->query('SELECT id, username FROM users ORDER BY username ASC')
            ->fetchAll();
    }

    public function userName(?string $id): string
    {
        if ($id === null || $id === '') {
            return 'Unassigned';
        }
        $stmt = $this->pdo->prepare('SELECT username FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $name = $stmt->fetchColumn();
        return $name !== false ? (string)$name : 'Unassigned';
    }

    public function findUserByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findUserById(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // --- Project teammates ------------------------------------------

    public function teammatesForProject(string $projectId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.username
             FROM project_teammates pt
             JOIN users u ON u.id = pt.user_id
             WHERE pt.project_id = ?
             ORDER BY u.username ASC'
        );
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }

    public function isProjectTeammate(string $projectId, string $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM project_teammates WHERE project_id = ? AND user_id = ?'
        );
        $stmt->execute([$projectId, $userId]);
        return $stmt->fetchColumn() !== false;
    }

    public function addProjectTeammate(string $projectId, string $targetUserId, string $ownerId): void
    {
        $own = $this->pdo->prepare('SELECT 1 FROM projects WHERE id = ? AND user_id = ?');
        $own->execute([$projectId, $ownerId]);
        if ($own->fetchColumn() === false) {
            return;
        }
        $u = $this->pdo->prepare('SELECT 1 FROM users WHERE id = ?');
        $u->execute([$targetUserId]);
        if ($u->fetchColumn() === false) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO project_teammates (project_id, user_id, created_at)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([$projectId, $targetUserId, gmdate('Y-m-d H:i:s')]);
    }

    public function removeProjectTeammate(string $projectId, string $targetUserId, string $ownerId): void
    {
        $own = $this->pdo->prepare('SELECT 1 FROM projects WHERE id = ? AND user_id = ?');
        $own->execute([$projectId, $ownerId]);
        if ($own->fetchColumn() === false) {
            return;
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE tasks SET assignee_id = NULL
                 WHERE project_id = ? AND assignee_id = ?'
            );
            $stmt->execute([$projectId, $targetUserId]);

            $stmt = $this->pdo->prepare(
                'UPDATE issues SET assignee_id = NULL
                 WHERE project_id = ? AND assignee_id = ?'
            );
            $stmt->execute([$projectId, $targetUserId]);

            $stmt = $this->pdo->prepare(
                'DELETE FROM project_teammates WHERE project_id = ? AND user_id = ?'
            );
            $stmt->execute([$projectId, $targetUserId]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function assigneeAllowedForProject(string $projectId, ?string $assigneeId): bool
    {
        if ($assigneeId === null || $assigneeId === '') {
            return true;
        }
        return $this->isProjectTeammate($projectId, $assigneeId);
    }

    // --- Tasks ------------------------------------------------------

    public function tasksForProject(string $projectId, string $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.id, t.project_id, t.title, t.status, t.assignee_id
             FROM tasks t
             JOIN projects p ON p.id = t.project_id
             WHERE t.project_id = ? AND ' . $this->visibleProjectWhere('p') . '
             ORDER BY t.created_at ASC'
        );
        $stmt->execute([$projectId, $userId, $userId]);
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
        if (!$this->assigneeAllowedForProject($projectId, $assigneeId)) {
            $assigneeId = null;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO tasks (id, project_id, title, status, assignee_id, created_at)
             SELECT ?, ?, ?, ?, ?, ?
             FROM projects p
             WHERE p.id = ? AND ' . $this->visibleProjectWhere('p')
        );
        $stmt->execute([
            $id, $projectId, $title, $status, $assigneeId ?: null,
            gmdate('Y-m-d H:i:s'),
            $projectId,
            $userId, $userId,
        ]);
    }

    public function updateTask(string $taskId, string $title, ?string $assigneeId, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.project_id FROM tasks t
             JOIN projects p ON p.id = t.project_id
             WHERE t.id = ? AND ' . $this->visibleProjectWhere('p')
        );
        $stmt->execute([$taskId, $userId, $userId]);
        $projectId = $stmt->fetchColumn();
        if ($projectId === false) {
            return;
        }
        if (!$this->assigneeAllowedForProject($projectId, $assigneeId)) {
            $assigneeId = null;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE tasks t
             JOIN projects p ON p.id = t.project_id
             SET t.title = ?, t.assignee_id = ?
             WHERE t.id = ? AND ' . $this->visibleProjectWhere('p')
        );
        $stmt->execute([$title, $assigneeId ?: null, $taskId, $userId, $userId]);
    }

    public function moveTask(string $taskId, string $status, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tasks t
             JOIN projects p ON p.id = t.project_id
             SET t.status = ?
             WHERE t.id = ? AND ' . $this->visibleProjectWhere('p')
        );
        $stmt->execute([$status, $taskId, $userId, $userId]);
    }

    public function deleteTask(string $taskId, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE t FROM tasks t
             JOIN projects p ON p.id = t.project_id
             WHERE t.id = ? AND ' . $this->visibleProjectWhere('p')
        );
        $stmt->execute([$taskId, $userId, $userId]);
    }

    // --- Issues -----------------------------------------------------

    public function issuesForProject(string $projectId, string $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT i.id, i.project_id, i.task_id, i.title, i.body, i.severity, i.status,
                    i.assignee_id, i.author_id, i.created_at, i.updated_at,
                    u.username AS author_name
             FROM issues i
             JOIN projects p ON p.id = i.project_id
             LEFT JOIN users u ON u.id = i.author_id
             WHERE i.project_id = ? AND ' . $this->visibleProjectWhere('p') . '
             ORDER BY
                FIELD(i.severity, "critical", "high", "medium", "low"),
                i.created_at DESC'
        );
        $stmt->execute([$projectId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    public function findIssueForUser(string $id, string $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT i.*, u.username AS author_name
             FROM issues i
             JOIN projects p ON p.id = i.project_id
             LEFT JOIN users u ON u.id = i.author_id
             WHERE i.id = ? AND ' . $this->visibleProjectWhere('p')
        );
        $stmt->execute([$id, $userId, $userId]);
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
        string $authorId,
        string $userId
    ): void {
        if (!$this->assigneeAllowedForProject($projectId, $assigneeId)) {
            $assigneeId = null;
        }
        $now = gmdate('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO issues
                (id, project_id, task_id, title, body, severity, status,
                 assignee_id, author_id, created_at, updated_at)
             SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
             FROM projects p
             WHERE p.id = ? AND ' . $this->visibleProjectWhere('p')
        );
        $stmt->execute([
            $id, $projectId, $taskId ?: null, $title,
            $body !== null && $body !== '' ? $body : null,
            $severity, $status, $assigneeId ?: null, $authorId,
            $now, $now,
            $projectId,
            $userId, $userId,
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
            'SELECT i.project_id FROM issues i
             JOIN projects p ON p.id = i.project_id
             WHERE i.id = ? AND ' . $this->visibleProjectWhere('p')
        );
        $stmt->execute([$id, $userId, $userId]);
        $projectId = $stmt->fetchColumn();
        if ($projectId === false) {
            return;
        }
        if (!$this->assigneeAllowedForProject($projectId, $assigneeId)) {
            $assigneeId = null;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE issues i
             JOIN projects p ON p.id = i.project_id
             SET i.title = ?, i.body = ?, i.severity = ?, i.status = ?,
                 i.assignee_id = ?, i.updated_at = ?
             WHERE i.id = ? AND ' . $this->visibleProjectWhere('p')
        );
        $stmt->execute([
            $title,
            $body !== null && $body !== '' ? $body : null,
            $severity, $status, $assigneeId ?: null,
            gmdate('Y-m-d H:i:s'),
            $id,
            $userId, $userId,
        ]);
    }

    public function deleteIssue(string $id, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE i FROM issues i
             JOIN projects p ON p.id = i.project_id
             WHERE i.id = ? AND ' . $this->visibleProjectWhere('p')
        );
        $stmt->execute([$id, $userId, $userId]);
    }

    // --- Comments ---------------------------------------------------

    public function commentsForIssue(string $issueId, string $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.body, c.created_at, c.author_id, u.username AS author_name
             FROM issue_comments c
             JOIN issues i ON i.id = c.issue_id
             JOIN projects p ON p.id = i.project_id
             LEFT JOIN users u ON u.id = c.author_id
             WHERE c.issue_id = ? AND ' . $this->visibleProjectWhere('p') . '
             ORDER BY c.created_at ASC'
        );
        $stmt->execute([$issueId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    public function insertComment(string $issueId, string $authorId, string $body, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO issue_comments (id, issue_id, author_id, body, created_at)
             SELECT ?, i.id, ?, ?, ?
             FROM issues i
             JOIN projects p ON p.id = i.project_id
             WHERE i.id = ? AND ' . $this->visibleProjectWhere('p')
        );
        $stmt->execute([
            bin2hex(random_bytes(8)),
            $authorId,
            $body,
            gmdate('Y-m-d H:i:s'),
            $issueId,
            $userId, $userId,
        ]);
    }

    public function deleteComment(string $commentId, string $authorId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM issue_comments WHERE id = ? AND author_id = ?'
        );
        $stmt->execute([$commentId, $authorId]);
    }
}