<?php

require __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

return [
    'paths' => [
        'migrations' => __DIR__ . '/db/migrations',
        'seeds'      => __DIR__ . '/db/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment'     => 'production',
        'production' => [
            'adapter'   => 'mysql',
            'host'      => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1',
            'name'      => $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'minijira',
            'user' => $_ENV['DB_MIGRATOR_USER'] ?? getenv('DB_MIGRATOR_USER')
					  ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'minijira_app',
			'pass' => (function () {
				$p = $_ENV['DB_MIGRATOR_PASS'] ?? getenv('DB_MIGRATOR_PASS')
					?? $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: null;
				if ($p === null || $p === '') {
					throw new RuntimeException(
						'Migrator password is not set. Define DB_MIGRATOR_PASS or DB_PASS.'
					);
				}
				return $p;
			})(),
            'port'      => 3306,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],
    'version_order' => 'creation',
];