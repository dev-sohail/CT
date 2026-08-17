<?php

namespace App\Domains\ImportExportAndDataPortability\Services;

use App\Domains\ContactsAndRelationshipGraph\Models\Person;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\ImportExportAndDataPortability\Contracts\DataTransferProvider;
use App\Domains\ImportExportAndDataPortability\Models\Export;
use App\Domains\ImportExportAndDataPortability\Models\Import;
use Illuminate\Support\Facades\Storage;

class DataTransferService
{
    /** @var array<string, DataTransferProvider> */
    private array $providers = [];

    public function __construct(array $providers = [])
    {
        foreach ($providers as $provider) {
            $this->register($provider);
        }
    }

    public function register(DataTransferProvider $provider): void
    {
        $this->providers[$provider->domain()] = $provider;
    }

    public function supportedDomains(): array
    {
        return array_keys($this->providers);
    }

    public function export(User $user, array $domains, string $format = 'json'): Export
    {
        $export = Export::create([
            'user_id' => $user->id,
            'status' => 'queued',
            'format' => $format === 'csv' ? 'csv' : 'json',
            'domains' => $domains,
        ]);

        try {
            $payload = [];
            $counts = [];

            foreach ($domains as $domain) {
                if (!isset($this->providers[$domain])) {
                    continue;
                }
                $payload[$domain] = $this->providers[$domain]->export($user);
                $counts[$domain] = $this->countTopLevel($payload[$domain]);
            }

            $contents = $format === 'csv'
                ? $this->flattenToCsv($payload)
                : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $dir = 'exports/' . $user->id;
            Storage::disk('local')->makeDirectory($dir);
            $path = $dir . '/' . now()->format('Ymd-His') . '.' . $format;
            Storage::disk('local')->put($path, $contents);

            $export->update([
                'status' => 'completed',
                'file_path' => $path,
                'item_counts' => $counts,
            ]);
        } catch (\Throwable $e) {
            $export->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }

        return $export;
    }

    public function import(User $user, string $domain, array $data, string $format = 'json'): Import
    {
        $import = Import::create([
            'user_id' => $user->id,
            'domain' => $domain,
            'format' => $format === 'csv' ? 'csv' : 'json',
            'status' => 'completed',
        ]);

        try {
            if (!isset($this->providers[$domain])) {
                throw new \InvalidArgumentException("Unknown domain: {$domain}");
            }
            $counts = $this->providers[$domain]->import($user, $data);
            $import->update(['item_counts' => $counts]);
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'errors' => [['message' => $e->getMessage()]],
            ]);
        }

        return $import;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function countTopLevel(array $payload): array
    {
        $counts = [];
        foreach ($payload as $key => $value) {
            $counts[$key] = count($value);
        }
        return $counts;
    }

    private function flattenToCsv(array $payload): string
    {
        $rows = [];
        foreach ($payload as $domain => $collections) {
            foreach ($collections as $collection => $items) {
                foreach ($items as $item) {
                    $rows[] = array_merge(['domain' => $domain, 'collection' => $collection], $item);
                }
            }
        }
        if (empty($rows)) {
            return "domain,collection\n";
        }

        $headers = array_keys($rows[0]);
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $h) {
                $value = $row[$h] ?? null;
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $line[] = is_scalar($value) ? (string) $value : '';
            }
            fputcsv($handle, $line);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return $csv;
    }
}