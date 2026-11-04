<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTaskPriorityAndDueDate extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('tasks');
        $table
            ->addColumn('priority', 'enum', [
                'values'  => ['low', 'medium', 'high'],
                'default' => 'medium',
                'null'    => false,
                'after'   => 'status',
            ])
            ->addColumn('due_date', 'date', [
                'null'    => true,
                'default' => null,
                'after'   => 'priority',
            ])
            ->update();
    }

    public function down(): void
    {
        $table = $this->table('tasks');
        $table
            ->removeColumn('due_date')
            ->removeColumn('priority')
            ->update();
    }
}
