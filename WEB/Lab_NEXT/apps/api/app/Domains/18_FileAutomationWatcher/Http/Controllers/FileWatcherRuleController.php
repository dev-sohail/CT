<?php

namespace App\Domains\FileAutomationWatcher\Http\Controllers;

use App\Domains\FileAutomationWatcher\Http\Resources\FileWatcherLogResource;
use App\Domains\FileAutomationWatcher\Http\Resources\FileWatcherRuleResource;
use App\Domains\FileAutomationWatcher\Models\FileWatcherRule;
use App\Domains\FileAutomationWatcher\Services\FileWatcherService;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class FileWatcherRuleController extends ApiController
{
    public function __construct(private FileWatcherService $watcher)
    {
    }

    public function index(Request $request)
    {
        $rules = FileWatcherRule::where('user_id', $request->user()->id)->orderByDesc('updated_at')->get();
        return $this->respondSuccess(FileWatcherRuleResource::collection($rules));
    }

    public function store(Request $request)
    {
        $validated = $this->validateRule($request, false);

        $rule = FileWatcherRule::create(array_merge(
            ['user_id' => $request->user()->id],
            $validated
        ));
        $rule->refresh();

        return $this->respondCreated(FileWatcherRuleResource::make($rule));
    }

    public function show(Request $request, FileWatcherRule $rule)
    {
        if ($rule->user_id !== $request->user()->id) {
            return $this->respondError('Rule not found.', 404);
        }
        return $this->respondSuccess(FileWatcherRuleResource::make($rule));
    }

    public function update(Request $request, FileWatcherRule $rule)
    {
        if ($rule->user_id !== $request->user()->id) {
            return $this->respondError('Rule not found.', 404);
        }

        $rule->update($this->validateRule($request, true));

        return $this->respondSuccess(FileWatcherRuleResource::make($rule));
    }

    public function destroy(Request $request, FileWatcherRule $rule)
    {
        if ($rule->user_id !== $request->user()->id) {
            return $this->respondError('Rule not found.', 404);
        }
        $rule->delete();
        return $this->respondNoContent();
    }

    public function scan(Request $request, FileWatcherRule $rule)
    {
        if ($rule->user_id !== $request->user()->id) {
            return $this->respondError('Rule not found.', 404);
        }

        return $this->respondSuccess(['files' => $this->watcher->scan($rule)]);
    }

    public function run(Request $request, FileWatcherRule $rule)
    {
        if ($rule->user_id !== $request->user()->id) {
            return $this->respondError('Rule not found.', 404);
        }

        $logs = $this->watcher->run($rule);

        return $this->respondSuccess(FileWatcherLogResource::collection($logs));
    }

    public function logs(Request $request, FileWatcherRule $rule)
    {
        if ($rule->user_id !== $request->user()->id) {
            return $this->respondError('Rule not found.', 404);
        }

        return $this->respondSuccess(
            FileWatcherLogResource::collection($rule->logs()->limit(50)->get())
        );
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function validateRule(Request $request, bool $partial = false): array
    {
        $rules = [
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'source_disk' => ['sometimes', 'string', 'max:32'],
            'source_path' => [$partial ? 'sometimes' : 'required', 'string', 'max:1024'],
            'pattern' => ['nullable', 'string', 'max:255'],
            'action' => ['sometimes', 'in:move,copy,delete'],
            'destination_path' => ['nullable', 'string', 'max:1024'],
            'tag_keyword' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];

        return $request->validate($rules);
    }
}