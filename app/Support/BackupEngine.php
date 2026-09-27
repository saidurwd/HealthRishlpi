<?php

namespace App\Support;

use App\Models\Backup;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use ZipArchive;

/**
 * Port of the Yii app's BackupEngine: a plain SQL dump of every table
 * (DROP + CREATE + one INSERT per row), saved as .gz, .zip or .sql. The dump
 * is streamed to the file rather than built in memory. Restore splits a
 * dump into statements and runs them.
 */
class BackupEngine
{
    public function create(string $type, ?int $userId): Backup
    {
        $tables = array_map(fn ($row) => array_values((array) $row)[0], DB::select('SHOW TABLES'));

        if ($tables === []) {
            throw new RuntimeException('No tables found to backup.');
        }

        if (! is_dir(Backup::directory())) {
            mkdir(Backup::directory(), 0755, true);
        }

        $started = microtime(true);
        $base = DB::getDatabaseName().'_backup_'.date('Y-m-d_H-i-s');
        [$extension, $type] = match ($type) {
            'gzip' => ['.gz', Backup::TYPE_GZIP],
            'zip' => ['.zip', Backup::TYPE_ZIP],
            default => ['.sql', Backup::TYPE_SQL],
        };
        $filename = $base.$extension;
        $path = Backup::directory().'/'.$filename;

        if ($type === Backup::TYPE_GZIP) {
            $handle = gzopen($path, 'wb6');
            $this->writeSql($tables, fn (string $chunk) => gzwrite($handle, $chunk));
            gzclose($handle);
        } elseif ($type === Backup::TYPE_ZIP) {
            $sqlFile = tempnam(sys_get_temp_dir(), 'sql');
            $handle = fopen($sqlFile, 'wb');
            $this->writeSql($tables, fn (string $chunk) => fwrite($handle, $chunk));
            fclose($handle);

            $zip = new ZipArchive;
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Cannot create ZIP archive.');
            }
            $zip->addFile($sqlFile, $base.'.sql');
            $zip->close();
            @unlink($sqlFile);
        } else {
            $handle = fopen($path, 'wb');
            $this->writeSql($tables, fn (string $chunk) => fwrite($handle, $chunk));
            fclose($handle);
        }

        if (! is_file($path)) {
            throw new RuntimeException('Failed to write backup file to: '.$path);
        }

        $backup = new Backup;
        $backup->forceFill([
            'attachment' => $filename,
            'file_path' => $path,
            'file_size' => filesize($path),
            'checksum' => md5_file($path),
            'type' => $type,
            'status' => Backup::STATUS_SUCCESS,
            'duration' => round(microtime(true) - $started, 2).'s',
            'tables_count' => count($tables),
            'created_by' => $userId,
            'created_on' => now(),
        ])->save();

        return $backup;
    }

    /**
     * Run every statement of a backup file; returns how many ran.
     */
    public function restore(Backup $backup): int
    {
        $content = match ($backup->type) {
            Backup::TYPE_GZIP => gzdecode((string) file_get_contents($backup->path())),
            Backup::TYPE_ZIP => $this->sqlFromZip($backup->path()),
            default => file_get_contents($backup->path()),
        };

        if (empty($content)) {
            throw new RuntimeException('Backup file is empty or corrupted.');
        }

        $statements = self::statements($content);

        DB::unprepared('SET FOREIGN_KEY_CHECKS = 0;');
        foreach ($statements as $statement) {
            DB::unprepared($statement);
        }
        DB::unprepared('SET FOREIGN_KEY_CHECKS = 1;');

        return count($statements);
    }

    /**
     * Split SQL on semicolons outside quoted strings (BackupController::parseSqlStatements()).
     *
     * @return array<int, string>
     */
    public static function statements(string $sql): array
    {
        $statements = [];
        $current = '';
        $inString = false;
        $quote = '';
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];

            if ($inString) {
                $current .= $char;
                if ($char === $quote && ($i === 0 || $sql[$i - 1] !== '\\')) {
                    $inString = false;
                }
            } elseif ($char === "'" || $char === '"') {
                $inString = true;
                $quote = $char;
                $current .= $char;
            } elseif ($char === ';') {
                if (trim($current) !== '') {
                    $statements[] = trim($current);
                }
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (trim($current) !== '') {
            $statements[] = trim($current);
        }

        return $statements;
    }

    /**
     * @param  array<int, string>  $tables
     * @param  callable(string): mixed  $write
     */
    private function writeSql(array $tables, callable $write): void
    {
        $pdo = DB::connection()->getPdo();

        $write("-- ========================================\n"
            .'-- Database Backup: '.DB::getDatabaseName()."\n"
            .'-- Generated: '.date('Y-m-d H:i:s')."\n"
            .'-- Server: '.config('database.connections.'.config('database.default').'.host')."\n"
            ."-- ========================================\n\n"
            ."SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\nSET AUTOCOMMIT = 0;\nSTART TRANSACTION;\nSET time_zone = \"+00:00\";\n\n");

        foreach ($tables as $table) {
            $create = (array) DB::selectOne('SHOW CREATE TABLE `'.$table.'`');
            $write("-- --------------------------------------------------------\n-- Table structure for `$table`\n-- --------------------------------------------------------\n\n"
                ."DROP TABLE IF EXISTS `$table`;\n".$create['Create Table'].";\n\n"
                ."-- --------------------------------------------------------\n-- Data for table `$table`\n-- --------------------------------------------------------\n\n");

            $rows = 0;
            foreach (DB::connection()->cursor('SELECT * FROM `'.$table.'`') as $row) {
                $values = array_map(fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value), (array) $row);
                $write('INSERT INTO `'.$table.'` VALUES('.implode(',', $values).");\n");
                $rows++;
            }

            $write("\n-- $rows row(s) exported\n\n");
        }

        $write("SET foreign_key_checks = 1;\nCOMMIT;\n");
    }

    private function sqlFromZip(string $path): string
    {
        $zip = new ZipArchive;
        $zip->open($path);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (preg_match('/\.sql$/i', (string) $zip->getNameIndex($i)) === 1) {
                $content = (string) $zip->getFromIndex($i);
                $zip->close();

                return $content;
            }
        }

        $zip->close();

        throw new RuntimeException('No SQL file found inside ZIP archive.');
    }
}
