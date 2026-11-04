<?php
// db/migrations/20260201000000_link_teammates_to_users.php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class LinkTeammatesToUsers extends AbstractMigration
{
    public function up(): void
    {
        // Teammates may now be tied to a real user account.
        $this->table('teammates')
            ->addColumn('user_id', 'string', [
                'limit' => 16, 'null' => true, 'default' => null, 'after' => 'id',
            ])
            ->addIndex(['user_id'], ['unique' => true]) // one teammate row per user
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'SET_NULL', 'update' => 'NO_ACTION',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('teammates')
            ->dropForeignKey('user_id')
            ->removeIndex(['user_id'])
            ->removeColumn('user_id')
            ->update();
    }
}