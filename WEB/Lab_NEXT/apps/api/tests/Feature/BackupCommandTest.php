<?php

namespace Tests\Feature;

use App\Support\DatabaseDumper;
use Illuminate\Support\Facades\Storage;

class BackupCommandTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->app->instance(DatabaseDumper::class, new class extends DatabaseDumper
        {
            public function dump(?string $database = null): string
            {
                return "-- MySQL dump of {$database}\nCREATE TABLE demo (id int);\n";
            }
        });
    }

    public function test_backup_command_writes_dump_and_creates_file(): void
    {
        $this->artisan('ctlab:backup')
            ->assertSuccessful();

        $disk = Storage::disk('local');
        $files = $disk->files('backups');

        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.sql.gz', $files[0]);

        $raw = gzdecode($disk->get($files[0]));
        $this->assertStringContainsString('CREATE TABLE', $raw);
    }

    public function test_backup_prunes_old_dumps_to_keep_limit(): void
    {
        $disk = Storage::disk('local');
        foreach (['ctlab-2026-01-01-0100', 'ctlab-2026-01-02-0100', 'ctlab-2026-01-03-0100', 'ctlab-2026-01-04-0100'] as $stamp) {
            $disk->put("backups/{$stamp}.sql.gz", 'stale');
        }

        $this->artisan('ctlab:backup', ['--keep' => 3])
            ->assertSuccessful();

        $remaining = collect($disk->files('backups'))->filter(fn ($f) => str_contains($f, '2026'))->count();
        $this->assertLessThanOrEqual(3, $remaining);
        $this->assertFalse($disk->exists('backups/ctlab-2026-01-01-0100.sql.gz'));
    }
}
