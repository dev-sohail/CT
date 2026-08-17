<?php

namespace App\Domains\FileAutomationWatcher\Services;

use App\Domains\FileAutomationWatcher\Models\FileWatcherLog;
use App\Domains\FileAutomationWatcher\Models\FileWatcherRule;
use Illuminate\Support\Facades\Storage;

class FileWatcherService
{
    /**
     * Find files on the configured disk matching the rule's pattern/keyword.
     *
     * @return array<string> absolute paths of matching files
     */
    public function scan(FileWatcherRule $rule): array
    {
        if (!$rule->is_active) {
            return [];
        }

        $disk = Storage::disk($rule->source_disk);
        if (!$disk->directoryExists($rule->source_path)) {
            return [];
        }

        $pattern = $rule->pattern ?? '*';
        $path = rtrim($rule->source_path, '/') . '/' . $pattern;

        $matches = [];
        foreach ($disk->files(rtrim($rule->source_path, '/'), false) as $file) {
            if (!fnmatch($pattern, basename($file))) {
                continue;
            }
            if ($rule->tag_keyword && !str_contains(basename($file), $rule->tag_keyword)) {
                continue;
            }
            $matches[] = $file;
        }

        return $matches;
    }

    /**
     * Execute the rule against all matching files, writing one log per file.
     *
     * @return array<FileWatcherLog>
     */
    public function run(FileWatcherRule $rule): array
    {
        $logs = [];
        $disk = Storage::disk($rule->source_disk);

        foreach ($this->scan($rule) as $file) {
            $filename = basename($file);
            try {
                switch ($rule->action) {
                    case 'move':
                    case 'copy':
                        if (!$rule->destination_path) {
                            throw new \RuntimeException('No destination path configured.');
                        }
                        $dest = rtrim($rule->destination_path, '/') . '/' . $filename;
                        if ($rule->action === 'move') {
                            $disk->move($file, $dest);
                        } else {
                            $disk->copy($file, $dest);
                        }
                        $logs[] = $this->log($rule, $filename, 'processed', "{$rule->action} to {$dest}");
                        break;

                    case 'delete':
                        $disk->delete($file);
                        $logs[] = $this->log($rule, $filename, 'processed', 'deleted');
                        break;

                    default:
                        $logs[] = $this->log($rule, $filename, 'skipped', "Unknown action: {$rule->action}");
                }
            } catch (\Throwable $e) {
                $logs[] = $this->log($rule, $filename, 'failed', $e->getMessage());
            }
        }

        return $logs;
    }

    private function log(FileWatcherRule $rule, string $filename, string $status, ?string $message): FileWatcherLog
    {
        return FileWatcherLog::create([
            'file_watcher_rule_id' => $rule->id,
            'filename' => $filename,
            'status' => $status,
            'message' => $message,
        ]);
    }
}