<?php
// db/migrations/20260201000001_create_invitations.php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvitations extends AbstractMigration
{
    public function up(): void
    {
        $this->table('invitations', ['id' => false, 'primary_key' => 'id'])
            ->addColumn('id', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('inviter_id', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('invitee_id', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('status', 'enum', [
                'values'  => ['pending', 'accepted', 'declined'],
                'default' => 'pending',
            ])
            ->addColumn('created_at', 'datetime')
            ->addColumn('responded_at', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['invitee_id', 'status'])
            ->addIndex(['inviter_id'])
            // Prevent duplicate pending invites between the same pair.
            ->addIndex(['inviter_id', 'invitee_id', 'status'], ['unique' => true])
            ->addForeignKey('inviter_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('invitee_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        $this->table('invitations')->drop()->save();
    }
}