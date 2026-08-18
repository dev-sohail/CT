<?php

use App\Domains\CoreIdentityAndAccessKernel\Http\Controllers\AuthController;
use App\Domains\CoreIdentityAndAccessKernel\Http\Controllers\UserController;
use App\Domains\CoreIdentityAndAccessKernel\Http\Controllers\RoleController;
use App\Domains\CoreIdentityAndAccessKernel\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me'])->name('identity.me');
    Route::post('logout', [AuthController::class, 'logout'])->name('identity.logout');

    Route::middleware('can:assignRole,App\Domains\CoreIdentityAndAccessKernel\Models\User')
        ->post('users/{user}/role', [AuthController::class, 'assignRole'])
        ->name('identity.users.role');

    // Users CRUD
    Route::apiResource('users', UserController::class)->parameters(['users' => 'user']);

    // Roles CRUD
    Route::apiResource('roles', RoleController::class)->parameters(['roles' => 'role']);

    // Permissions CRUD
    Route::apiResource('permissions', PermissionController::class)->parameters(['permissions' => 'permission']);
});

Route::middleware('throttle:10,1')->post('register', [AuthController::class, 'register'])->name('identity.register');
Route::middleware('throttle:10,1')->post('login', [AuthController::class, 'login'])->name('identity.login');
Route::middleware('throttle:5,1')->post('forgot-password', [AuthController::class, 'forgotPassword'])->name('identity.forgot-password');
Route::middleware('throttle:5,1')->post('reset-password', [AuthController::class, 'resetPassword'])->name('identity.reset-password');
