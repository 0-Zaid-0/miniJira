<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateProjectTeammates extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('project_teammates')) {
            return;
        }
        $this->table('project_teammates', [
            'id' => false,
            'primary_key' => ['project_id', 'user_id'],
        ])
            ->addColumn('project_id', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('user_id',    'string', ['limit' => 16, 'null' => false])
            ->addColumn('created_at', 'datetime')
            ->addIndex(['user_id'])
            ->addForeignKey('project_id', 'projects', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('user_id',    'users',    'id', ['delete' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('project_teammates')) {
            $this->table('project_teammates')->drop()->save();
        }
    }
}