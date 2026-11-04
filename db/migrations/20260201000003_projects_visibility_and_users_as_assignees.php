<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ProjectsVisibilityAndUsersAsAssignees extends AbstractMigration
{
    public function up(): void
    {
        // 1. Project visibility: private (default) or global.
        $projects = $this->table('projects');
        if (!$projects->hasColumn('visibility')) {
            $projects
                ->addColumn('visibility', 'enum', [
                    'values'  => ['private', 'global'],
                    'default' => 'private',
                    'after'   => 'name',
                ])
                ->addIndex(['visibility'])
                ->update();
        }

        // 2. Repoint assignee FKs from teammates -> users, then drop teammates.
        if ($this->hasTable('teammates')) {
            foreach (['tasks', 'issues'] as $table) {
                // Clear name-only assignees; they can't map to a user.
                $this->dropFkIfExists($table, 'assignee_id');
                $this->execute("UPDATE $table SET assignee_id = NULL");

                // Re-add FK pointing at users.
                $this->table($table)
                    ->addForeignKey('assignee_id', 'users', 'id', [
                        'delete' => 'SET_NULL', 'update' => 'NO_ACTION',
                    ])
                    ->update();
            }

            $this->table('teammates')->drop()->save();
        }
    }

    public function down(): void
    {
        // Recreate minimal teammates table so down() is symmetric.
        if (!$this->hasTable('teammates')) {
            $this->table('teammates', ['id' => false, 'primary_key' => 'id'])
                ->addColumn('id', 'string', ['limit' => 16, 'null' => false])
                ->addColumn('name', 'string', ['limit' => 255])
                ->addColumn('created_at', 'datetime')
                ->create();
        }

        foreach (['tasks', 'issues'] as $table) {
            $this->dropFkIfExists($table, 'assignee_id');
            $this->execute("UPDATE $table SET assignee_id = NULL");
            $this->table($table)
                ->addForeignKey('assignee_id', 'teammates', 'id', [
                    'delete' => 'SET_NULL', 'update' => 'NO_ACTION',
                ])
                ->update();
        }

        $projects = $this->table('projects');
        if ($projects->hasColumn('visibility')) {
            $projects->removeIndex(['visibility'])->removeColumn('visibility')->update();
        }
    }

    private function dropFkIfExists(string $table, string $column): void
    {
        // Look up the actual FK constraint name (MySQL auto-generates names
        // like tasks_ibfk_2, and Phinx's dropForeignKey() expects the column,
        // not the constraint name — so we drop via raw SQL using the name).
        $rows = $this->fetchAll(sprintf(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = '%s'
               AND COLUMN_NAME = '%s'
               AND REFERENCED_TABLE_NAME IS NOT NULL",
            $table,
            $column
        ));
        if (!empty($rows)) {
            $this->execute(sprintf(
                'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                $table,
                $rows[0]['CONSTRAINT_NAME']
            ));
        }
    }
}