<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use PDO;

final class Migrator
{
    public function __construct(private readonly Database $db)
    {
    }

    public function migrate(string $path): void
    {
        $this->ensureMigrationsTable();
        $pending = $this->pending($path);

        if ($pending === []) {
            echo "Nothing to migrate.\n";
            return;
        }

        $batch = $this->nextBatch();
        foreach ($pending as $name => $file) {
            foreach ((array) (require $file)['up'] as $sql) {
                $this->db->pdo()->exec($sql);
            }
            $this->record($name, $batch);
            echo "✔ Migrated: {$name}\n";
        }
    }

    public function rollback(string $path): void
    {
        $this->ensureMigrationsTable();
        $batch = $this->lastBatch();

        if ($batch === 0) {
            echo "Nothing to rollback.\n";
            return;
        }

        $files = $this->files($path);
        foreach (array_reverse($this->ranInBatch($batch)) as $name) {
            if (!isset($files[$name])) {
                echo "✘ Migration file not found, skipping: {$name}\n";
                continue;
            }
            foreach ((array) ((require $files[$name])['down'] ?? []) as $sql) {
                $this->db->pdo()->exec($sql);
            }
            $this->db->pdo()
                ->prepare('DELETE FROM migrations WHERE migration = :name')
                ->execute(['name' => $name]);
            echo "✔ Rolled back: {$name}\n";
        }
    }

    public function status(string $path): void
    {
        $this->ensureMigrationsTable();
        $ran   = $this->ran();
        $files = $this->files($path);

        if ($files === []) {
            echo "No migration files found.\n";
            return;
        }

        printf("%-50s %-10s %s\n", 'Migration', 'Status', 'Batch');
        echo str_repeat('-', 70) . "\n";
        foreach ($files as $name => $_) {
            $status = isset($ran[$name]) ? 'Ran' : 'Pending';
            $batch  = $ran[$name] ?? '-';
            printf("%-50s %-10s %s\n", $name, $status, $batch);
        }
    }

    private function ensureMigrationsTable(): void
    {
        $this->db->pdo()->exec(
            "CREATE TABLE IF NOT EXISTS migrations (
                id        INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch     INT NOT NULL,
                ran_at    DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    /** @return array<string, string> name => filepath, sorted by filename */
    private function files(string $path): array
    {
        $found = glob(rtrim($path, '/') . '/*.php') ?: [];
        sort($found);
        $result = [];
        foreach ($found as $file) {
            $result[basename($file, '.php')] = $file;
        }
        return $result;
    }

    /** @return array<string, string> name => filepath, only not-yet-run */
    private function pending(string $path): array
    {
        $ran = $this->ran();
        return array_filter(
            $this->files($path),
            static fn (string $name): bool => !isset($ran[$name]),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /** @return array<string, int> name => batch */
    private function ran(): array
    {
        $rows = $this->db->pdo()
            ->query('SELECT migration, batch FROM migrations ORDER BY id')
            ->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['migration']] = (int) $row['batch'];
        }
        return $result;
    }

    /** @return list<string> */
    private function ranInBatch(int $batch): array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT migration FROM migrations WHERE batch = :batch ORDER BY id'
        );
        $stmt->execute(['batch' => $batch]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'migration');
    }

    private function nextBatch(): int
    {
        return $this->lastBatch() + 1;
    }

    private function lastBatch(): int
    {
        return (int) $this->db->pdo()
            ->query('SELECT COALESCE(MAX(batch), 0) FROM migrations')
            ->fetchColumn();
    }

    private function record(string $name, int $batch): void
    {
        $this->db->pdo()
            ->prepare('INSERT INTO migrations (migration, batch, ran_at) VALUES (:migration, :batch, :ran_at)')
            ->execute([
                'migration' => $name,
                'batch'     => $batch,
                'ran_at'    => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
    }
}
