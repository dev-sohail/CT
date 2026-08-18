<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Http\Controllers;

use App\Domains\AuditTrailAndActivityTimeline\Services\AuditService;
use App\Domains\CoreIdentityAndAccessKernel\Actions\AssignRoleToUser;
use App\Domains\CoreIdentityAndAccessKernel\Actions\AuthenticateUser;
use App\Domains\CoreIdentityAndAccessKernel\Actions\LogoutUser;
use App\Domains\CoreIdentityAndAccessKernel\Actions\RegisterUser;
use App\Domains\CoreIdentityAndAccessKernel\Actions\RequestPasswordReset;
use App\Domains\CoreIdentityAndAccessKernel\Actions\ResetPassword;
use App\Domains\CoreIdentityAndAccessKernel\DataTransferObjects\LoginData;
use App\Domains\CoreIdentityAndAccessKernel\DataTransferObjects\RegisterUserData;
use App\Domains\CoreIdentityAndAccessKernel\Http\Requests\ForgotPasswordRequest;
use App\Domains\CoreIdentityAndAccessKernel\Http\Requests\LoginRequest;
use App\Domains\CoreIdentityAndAccessKernel\Http\Requests\RegisterRequest;
use App\Domains\CoreIdentityAndAccessKernel\Http\Requests\ResetPasswordRequest;
use App\Domains\CoreIdentityAndAccessKernel\Http\Resources\UserResource;
use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\CoreIdentityAndAccessKernel\Services\AuthServiceInterface;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthServiceInterface $auth,
        private readonly RegisterUser $registerUser,
        private readonly AuthenticateUser $authenticateUser,
        private readonly LogoutUser $logoutUser,
        private readonly RequestPasswordReset $requestPasswordReset,
        private readonly ResetPassword $resetPassword,
        private readonly AssignRoleToUser $assignRoleToUser,
        private readonly AuditService $audit,
    ) {}

    public function register(RegisterRequest $request)
    {
        $user = ($this->registerUser)(RegisterUserData::fromArray($request->validated()));
        $token = $this->auth->issueToken($user, $request->userAgent() ?? 'api');

        return $this->respondCreated(
            UserResource::make($user->load('roles'))->toArray($request),
            ['token' => $token]
        );
    }

    public function login(LoginRequest $request)
    {
        $data = LoginData::fromArray($request->validated());
        $user = ($this->authenticateUser)($data);

        if (! $user) {
            return $this->respondError('Invalid credentials', 401);
        }

        $token = $this->auth->issueToken($user, $request->userAgent() ?? 'api');

        $this->audit->recordFromRequest($request, 'auth.login', $user, actor: $user);

        return $this->respondSuccess(
            UserResource::make($user->load('roles'))->toArray($request),
            ['token' => $token]
        );
    }

    public function logout(Request $request)
    {
        ($this->logoutUser)($request->user());

        return $this->respondNoContent();
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('roles');

        return $this->respondSuccess(UserResource::make($user)->toArray($request));
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        ($this->requestPasswordReset)($request->validated('email'));

        return $this->respondSuccess(['message' => 'If the email exists, a reset link was sent.']);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $ok = ($this->resetPassword)(
            $request->validated('email'),
            $request->validated('token'),
            $request->validated('password')
        );

        if (! $ok) {
            return $this->respondError('Unable to reset password', 422);
        }

        return $this->respondSuccess(['message' => 'Password reset successful.']);
    }

    public function assignRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'exists:identity_roles,slug'],
        ]);

        $user = ($this->assignRoleToUser)($user, $validated['role']);

        return $this->respondSuccess(UserResource::make($user)->toArray($request));
    }
}
