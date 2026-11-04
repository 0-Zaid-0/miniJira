<?php
// db/migrations/20260201000002_add_issue_author_and_comments.php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddIssueAuthorAndComments extends AbstractMigration
{
    public function up(): void
    {
        $this->table('issues')
            ->addColumn('author_id', 'string', [
                'limit' => 16, 'null' => true, 'default' => null, 'after' => 'title',
            ])
            ->addIndex(['author_id'])
            ->addForeignKey('author_id', 'users', 'id', [
                'delete' => 'SET_NULL', 'update' => 'NO_ACTION',
            ])
            ->update();

        $this->table('issue_comments', ['id' => false, 'primary_key' => 'id'])
            ->addColumn('id', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('issue_id', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('author_id', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('body', 'text', ['null' => false])
            ->addColumn('created_at', 'datetime')
            ->addIndex(['issue_id', 'created_at'])
            ->addForeignKey('issue_id', 'issues', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('author_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        $this->table('issue_comments')->drop()->save();
        $this->table('issues')
            ->dropForeignKey('author_id')
            ->removeIndex(['author_id'])
            ->removeColumn('author_id')
            ->update();
    }
}