<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsers extends AbstractMigration
{
    public function up(): void
    {
        $this->table('users', ['id' => false, 'primary_key' => 'id'])
            ->addColumn('id', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('username', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('created_at', 'datetime')
            ->addIndex(['username'], ['unique' => true])
            ->create();

        // Ownership: every project belongs to a user.
        // Existing rows (if any) get NULL and become invisible until you
        // claim them with a one-off UPDATE — see note after the migration.
        $this->table('projects')
            ->addColumn('user_id', 'string', ['limit' => 16, 'null' => true, 'default' => null, 'after' => 'id'])
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->update();

        // Login rate-limit ledger
        $this->table('login_attempts', ['id' => false, 'primary_key' => 'id'])
            ->addColumn('id', 'biginteger', ['identity' => true])
            ->addColumn('username', 'string', ['limit' => 64])
            ->addColumn('ip', 'string', ['limit' => 45])
            ->addColumn('attempted_at', 'datetime')
            ->addIndex(['username', 'ip', 'attempted_at'])
            ->create();
    }

    public function down(): void
    {
        $this->table('login_attempts')->drop()->save();
        $this->table('projects')->removeColumn('user_id')->update();
        $this->table('users')->drop()->save();
    }
}
