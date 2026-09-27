<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

/**
 * Nightly full backups (health:backup) and the removed one-click restore.
 */
class FullBackupTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/full-backup-test-'.uniqid();
        File::ensureDirectoryExists($this->directory.'/uploads/user');
        file_put_contents($this->directory.'/uploads/user/photo.jpg', 'photo');
        config([
            'filesystems.disks.backup_local.root' => $this->directory.'/disk',
            'backup.backup.source.files.include' => [$this->directory.'/uploads'],
            'backup.backup.source.files.exclude' => [],
            'backup.backup.source.files.relative_path' => $this->directory,
            'backup.backup.temporary_directory' => $this->directory.'/temp',
            'backup.backup.destination.disks' => ['backup_local'],
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_it_backs_up_the_database_and_uploads_encrypted(): void
    {
        config(['backup.backup.password' => 'test-secret']);

        $this->assertSame(0, Artisan::call('health:backup'), Artisan::output());

        $archives = glob($this->directory.'/disk/*/*.zip');
        $this->assertCount(1, $archives);

        $zip = new ZipArchive;
        $zip->open($archives[0]);
        $names = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i));
        $this->assertTrue($names->contains(fn ($name) => str_starts_with($name, 'db-dumps/') && str_ends_with($name, '.sql.gz')));
        $this->assertContains('uploads/user/photo.jpg', $names->all());

        // Nothing can be read without the password
        $this->assertFalse($zip->getFromName('uploads/user/photo.jpg'));
        $zip->setPassword('test-secret');
        $this->assertSame('photo', $zip->getFromName('uploads/user/photo.jpg'));
    }

    public function test_it_will_not_send_an_unencrypted_backup_off_site(): void
    {
        config(['backup.backup.destination.disks' => ['backup_local', 'backup_offsite'], 'backup.backup.password' => null]);

        $this->assertSame(1, Artisan::call('health:backup'));
        $this->assertStringContainsString('Set BACKUP_ARCHIVE_PASSWORD before backing up to backup_offsite', Artisan::output());
        $this->assertSame([], glob($this->directory.'/disk/*/*.zip'));
    }

    public function test_the_one_click_restore_is_gone(): void
    {
        $this->actingAs($this->makeUser(['group_id' => User::SUPER_GROUP]));

        $this->post('/backup/restore/1')->assertNotFound();
        $this->get('/backup/admin')->assertOk()->assertDontSee('Restore this backup');
    }
}
