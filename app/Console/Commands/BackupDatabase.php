<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Portable SQL backup (pure PHP, no mysqldump needed — shared hosting friendly).
 * Writes storage/app/backups/acadexa-YYYYmmdd-His.sql.gz and keeps the latest N files.
 * Restore: gunzip then import the .sql file with phpMyAdmin or `mysql`.
 */
class BackupDatabase extends Command
{
    protected $signature = 'acadexa:backup {--keep=14 : Number of backups to keep}';
    protected $description = 'Back up the database to storage/app/backups';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);
        $file = $dir . '/acadexa-' . now()->format('Ymd-His') . '.sql.gz';
        $gz = gzopen($file, 'wb6');

        $driver = DB::connection()->getDriverName();
        gzwrite($gz, "-- ACADEXA backup " . now()->toDateTimeString() . " ({$driver})\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($this->tables() as $table) {
            if ($driver === 'mysql') {
                $create = (array) DB::selectOne("SHOW CREATE TABLE `{$table}`");
                gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n" . end($create) . ";\n\n");
            }

            $columns = Schema::getColumnListing($table);
            $orderBy = in_array('id', $columns, true) ? 'id' : $columns[0];
            DB::table($table)->orderBy($orderBy)->chunk(500, function ($rows) use ($gz, $table) {
                $values = [];
                foreach ($rows as $row) {
                    $values[] = '(' . implode(',', array_map(fn ($v) => $this->quote($v), (array) $row)) . ')';
                }
                if ($values) {
                    $cols = '`' . implode('`,`', array_keys((array) $rows->first())) . '`';
                    gzwrite($gz, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $values) . ";\n");
                }
            });
            gzwrite($gz, "\n");
        }

        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        gzclose($gz);

        $this->prune($dir, (int) $this->option('keep'));
        $this->info('Backup written: ' . $file . ' (' . \App\Support\Format::bytes(filesize($file)) . ')');

        return self::SUCCESS;
    }

    private function tables(): array
    {
        // Laravel 12 lists the tables of every schema on the server: keep the current database only.
        $database = DB::connection()->getDriverName() === 'mysql' ? DB::getDatabaseName() : null;

        return collect(Schema::getTables())
            ->filter(fn ($t) => $database === null || ($t['schema'] ?? $database) === $database)
            ->pluck('name')
            ->reject(fn ($t) => in_array($t, ['sessions', 'cache', 'cache_locks', 'jobs', 'failed_jobs', 'sqlite_sequence'], true))
            ->values()->all();
    }

    private function quote($value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        return DB::getPdo()->quote((string) $value);
    }

    private function prune(string $dir, int $keep): void
    {
        $files = glob($dir . '/acadexa-*.sql.gz') ?: [];
        rsort($files);
        foreach (array_slice($files, max(1, $keep)) as $old) {
            @unlink($old);
        }
    }
}
