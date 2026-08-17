<?php

namespace Tests\Unit;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\SecondBrainPersonalWiki\Models\Notebook;
use App\Domains\SecondBrainPersonalWiki\Models\Workspace;
use App\Domains\SecondBrainPersonalWiki\Services\WorkspaceScope;
use PHPUnit\Framework\TestCase;

class WorkspaceScopeTest extends TestCase
{
    public function test_owns_returns_true_for_same_user(): void
    {
        $user = new User(['id' => 1]);
        $workspace = new Workspace(['user_id' => 1]);

        $scope = new WorkspaceScope;
        $this->assertTrue($scope->owns($workspace, [], 1));
    }

    public function test_owns_returns_false_for_different_user(): void
    {
        $user = new User(['id' => 1]);
        $workspace = new Workspace(['user_id' => 2]);

        $scope = new WorkspaceScope;
        $this->assertFalse($scope->owns($workspace, [], 1));
    }

    public function test_owns_traverses_ancestry(): void
    {
        $workspace = new Workspace(['user_id' => 7]);
        $notebook = new Notebook(['workspace_id' => 1]);
        $notebook->setRelation('workspace', $workspace);

        $scope = new WorkspaceScope;
        $this->assertTrue($scope->owns($notebook, ['workspace'], 7));
        $this->assertFalse($scope->owns($notebook, ['workspace'], 8));
    }
}
