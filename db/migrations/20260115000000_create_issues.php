<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateIssues extends AbstractMigration
{
    public function up(): void
    {
        $this->table('issues', ['id' => false, 'primary_key' => 'id'])
            ->addColumn('id', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('project_id', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('task_id', 'string', ['limit' => 16, 'null' => true, 'default' => null])
            ->addColumn('title', 'string', ['limit' => 255])
            ->addColumn('body', 'text', ['null' => true, 'default' => null])
            ->addColumn('severity', 'enum', [
                'values'  => ['low', 'medium', 'high', 'critical'],
                'default' => 'medium',
            ])
            ->addColumn('status', 'enum', [
                'values'  => ['open', 'in_progress', 'resolved', 'closed'],
                'default' => 'open',
            ])
            ->addColumn('assignee_id', 'string', ['limit' => 16, 'null' => true, 'default' => null])
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->addIndex(['project_id'])
            ->addIndex(['task_id'])
            ->addIndex(['assignee_id'])
            ->addForeignKey('project_id', 'projects', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('task_id', 'tasks', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->addForeignKey('assignee_id', 'teammates', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }

    public function down(): void
    {
        $this->table('issues')->drop()->save();
    }
}
