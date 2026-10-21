<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitialSchema extends AbstractMigration
{
    public function up(): void
    {
        $this->table('projects', ['id' => false, 'primary_key' => 'id'])
            ->addColumn('id', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('created_at', 'datetime')
            ->create();

        $this->table('teammates', ['id' => false, 'primary_key' => 'id'])
            ->addColumn('id', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('created_at', 'datetime')
            ->create();

        $this->table('tasks', ['id' => false, 'primary_key' => 'id'])
            ->addColumn('id', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('project_id', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('title', 'string', ['limit' => 255])
            ->addColumn('status', 'enum', ['values' => ['todo', 'in_progress', 'done'], 'default' => 'todo'])
            ->addColumn('assignee_id', 'string', ['limit' => 16, 'null' => true, 'default' => null])
            ->addColumn('created_at', 'datetime')
            ->addIndex(['project_id'])
            ->addIndex(['assignee_id'])
            ->addForeignKey('project_id', 'projects', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('assignee_id', 'teammates', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }

    public function down(): void
    {
        $this->table('tasks')->drop()->save();
        $this->table('teammates')->drop()->save();
        $this->table('projects')->drop()->save();
    }
}