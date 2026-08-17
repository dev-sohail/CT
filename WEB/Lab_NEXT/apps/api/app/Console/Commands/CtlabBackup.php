<?php

namespace App\Console\Commands;

use App\Support\DatabaseDumper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CtlabBackup extends Command
{
    protected $signature = 'ctlab:backup
                            {--keep= : Number of backups to retain (overrides config)}
                            {--database= : Database name to dump (defaults to DB_DATABASE)}';

    protected $description = 'CTLabs database backup database to storage and prune old backups';

    public function handle(DatabaseDumper $dumper): int
    {
        $disk = Storage::disk(Config::get('backup.disk', 'local'));
        $directory = trim(Config::get('backup.directory', 'backups'), '/');
        $keep = (int) ($this->option('keep') ?? Config::get('backup.keep', 7));

        $filename = sprintf('%s/ctlab-%s.sql.gz', $directory, now()->format('Y-m-d-Hi'));
        if ($disk->exists($filename)) {
            $this->warn("Backup already exists for this run: {$filename}");

            return self::SUCCESS;
        }

        try {
            $dump = $dumper->dump($this->option('database'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $disk->put($filename, gzencode($dump));
        $this->info("Backup written: {$filename} (".number_format(strlen($dump) / 1024, 1).' KB raw)');

        $this->prune($disk, $directory, $keep);

        return self::SUCCESS;
    }

    private function prune($disk, string $directory, int $keep): void
    {
        $files = collect($disk->files($directory))
            ->filter(fn (string $file) => Str::endsWith($file, '.sql.gz'))
            ->sortDesc()
            ->values();

        if ($files->count() <= $keep) {
            return;
        }

        $files->slice($keep)->each(function (string $file) use ($disk) {
            $disk->delete($file);
            $this->line("Pruned: {$file}");
        });
    }
}
