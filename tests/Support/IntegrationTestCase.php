<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Config;
use App\Core\Config\DatabaseConfig;
use App\Core\Database;
use App\Core\Migrator;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base for integration tests. Provides a real MySQL connection against a dedicated
 * test database. Each test runs inside a transaction that is rolled back on tearDown,
 * so tests are isolated without truncating tables.
 */
abstract class IntegrationTestCase extends TestCase
{
    private static ?Database $connection = null;
    private static ?string   $skipReason = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (self::$connection !== null) {
            return;
        }
        try {
            self::$connection = self::connect();
            self::migrate(self::$connection);
            self::$skipReason = null;
        } catch (\Throwable $e) {
            self::$connection = null;
            self::$skipReason = 'MySQL unavailable: ' . $e->getMessage();
        }
    }

    final protected static function db(): Database
    {
        return self::$connection ?? throw new \LogicException('No DB connection — test should have been skipped');
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (self::$skipReason !== null) {
            $this->markTestSkipped(self::$skipReason);
        }
        self::db()->pdo()->beginTransaction();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (self::$connection !== null && self::$connection->pdo()->inTransaction()) {
            self::$connection->pdo()->rollBack();
        }
    }

    private static function connect(): Database
    {
        $base   = dirname(__DIR__, 2);
        $config = Config::load($base);
        $dbName = (string) (getenv('TEST_DB_NAME') ?: $config->string('db.name') . '_test');

        $adminPdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%s;charset=utf8mb4',
                $config->string('db.host', '127.0.0.1'),
                $config->string('db.port', '3306'),
            ),
            $config->string('db.user', 'root'),
            $config->string('db.password'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $adminPdo->exec(
            "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        return new Database(new DatabaseConfig(
            host:     $config->string('db.host', '127.0.0.1'),
            port:     (int) $config->get('db.port', 3306),
            name:     $dbName,
            user:     $config->string('db.user', 'root'),
            password: $config->string('db.password'),
        ));
    }

    private static function migrate(Database $db): void
    {
        ob_start();
        (new Migrator($db))->migrate(dirname(__DIR__, 2) . '/database/migrations');
        ob_end_clean();
    }
}
