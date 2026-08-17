<?php

namespace App\Domains\SecondBrainPersonalWiki\Services;

use App\Domains\SecondBrainPersonalWiki\Models\Workspace;
use Illuminate\Database\Eloquent\Model;

class WorkspaceScope
{
    public function workspaceForUser(int $userId, int $workspaceId): Workspace
    {
        $workspace = Workspace::where('user_id', $userId)->findOrFail($workspaceId);

        return $workspace;
    }

    public function assertOwns(Model $model, array $ancestry, int $userId): void
    {
        abort_unless($this->owns($model, $ancestry, $userId), 403, 'Not authorized.');
    }

    public function owns(Model $model, array $ancestry, int $userId): bool
    {
        $current = $model;
        foreach ($ancestry as $relation) {
            $current = $current->{$relation};
            if (! $current) {
                return false;
            }
        }

        if ($current instanceof Workspace) {
            return $current->user_id === $userId;
        }

        return $current->user_id === $userId;
    }
}
