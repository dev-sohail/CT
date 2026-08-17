<?php

namespace App\Domains\ImportExportAndDataPortability\Http\Controllers;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\ImportExportAndDataPortability\Http\Resources\ExportResource;
use App\Domains\ImportExportAndDataPortability\Http\Resources\ImportResource;
use App\Domains\ImportExportAndDataPortability\Models\Export;
use App\Domains\ImportExportAndDataPortability\Models\Import;
use App\Domains\ImportExportAndDataPortability\Services\DataTransferService;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportExportController extends ApiController
{
    public function __construct(private DataTransferService $transfer)
    {
    }

    public function domains()
    {
        return $this->respondSuccess($this->transfer->supportedDomains());
    }

    public function startExport(Request $request)
    {
        $validated = $request->validate([
            'domains' => ['required', 'array'],
            'domains.*' => ['required', 'string', 'in:' . implode(',', $this->transfer->supportedDomains())],
            'format' => ['sometimes', 'in:json,csv'],
        ]);

        $export = $this->transfer->export(
            $request->user(),
            array_values(array_unique($validated['domains'])),
            $validated['format'] ?? 'json'
        );

        return $this->respondCreated(ExportResource::make($export));
    }

    public function showExport(Request $request, Export $export)
    {
        if ($export->user_id !== $request->user()->id) {
            return $this->respondError('Export not found.', 404);
        }
        return $this->respondSuccess(ExportResource::make($export));
    }

    public function downloadExport(Request $request, Export $export)
    {
        if ($export->user_id !== $request->user()->id) {
            return $this->respondError('Export not found.', 404);
        }
        if ($export->status !== 'completed' || !$export->file_path) {
            return $this->respondError('Export is not ready.', 422);
        }

        $stream = $export->downloadStream();
        if (!$stream) {
            return $this->respondError('Export file is missing.', 404);
        }

        $mime = $export->format === 'csv' ? 'text/csv' : 'application/json';

        return response()->streamDownload(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, $export->downloadName(), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="' . $export->downloadName() . '"',
        ]);
    }

    public function startImport(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['required', 'string', 'in:' . implode(',', $this->transfer->supportedDomains())],
            'data' => ['required', 'array'],
            'format' => ['sometimes', 'in:json,csv'],
        ]);

        $import = $this->transfer->import(
            $request->user(),
            $validated['domain'],
            $validated['data'],
            $validated['format'] ?? 'json'
        );

        $status = $import->status === 'failed' ? $this->respondError(
            ImportResource::make($import),
            422
        ) : $this->respondCreated(ImportResource::make($import));

        return $status;
    }

    public function indexImports(Request $request)
    {
        $imports = Import::where('user_id', $request->user()->id)->orderByDesc('created_at')->get();
        return $this->respondSuccess(ImportResource::collection($imports));
    }

    public function indexExports(Request $request)
    {
        $exports = Export::where('user_id', $request->user()->id)->orderByDesc('created_at')->get();
        return $this->respondSuccess(ExportResource::collection($exports));
    }
}