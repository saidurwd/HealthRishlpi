<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * db:snapshot, the backup the deploy script takes before migrating.
 */
class DatabaseSnapshotTest extends TestCase
{
    public function test_it_writes_a_complete_gzipped_dump_and_keeps_the_newest(): void
    {
        $directory = storage_path('framework/testing/snapshots');
        File::deleteDirectory($directory);
        File::ensureDirectoryExists($directory);
        foreach (['a-20200101-000000' => '2020-01-01', 'a-20200102-000000' => '2020-01-02'] as $old => $date) {
            file_put_contents("$directory/$old.sql.gz", gzencode('old'));
            touch("$directory/$old.sql.gz", strtotime($date));
        }

        try {
            $this->artisan('db:snapshot', ['directory' => $directory, '--keep' => 2])->assertSuccessful();

            $files = glob("$directory/*.sql.gz");
            $this->assertCount(2, $files);
            $this->assertFileDoesNotExist("$directory/a-20200101-000000.sql.gz");

            $this->assertFileExists("$directory/a-20200102-000000.sql.gz");
            $snapshot = collect($files)->first(fn (string $file) => ! str_starts_with(basename($file), 'a-'));
            $dump = (string) gzdecode((string) file_get_contents($snapshot));
            $this->assertStringContainsString('CREATE TABLE `'.config('database.connections.mariadb.prefix').'patient`', $dump);
            $this->assertStringContainsString('Dump completed', $dump);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_it_fails_without_leaving_a_file_when_the_dump_fails(): void
    {
        $directory = storage_path('framework/testing/snapshots');
        config(['database.connections.mariadb.password' => 'wrong-password']);

        try {
            $this->artisan('db:snapshot', ['directory' => $directory])->assertFailed();
            $this->assertSame([], glob("$directory/*.sql.gz"));
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
