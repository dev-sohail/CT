# 01. CoreIdentityAndAccessKernel

**Purpose:** Auth, SSO, RBAC for the whole platform

## Implemented
- Models: `User`, `Role`, `Permission`, `Device` (tables `identity_*`)
- Hand-rolled RBAC: `HasRoles` trait with cached permission resolution
- `AuthServiceInterface` / `EloquentAuthService` (register, login, token issue, logout, password reset, role assignment)
- Invokable Actions: `RegisterUser`, `AuthenticateUser`, `LogoutUser`, `RequestPasswordReset`, `ResetPassword`, `AssignRoleToUser`
- `AuthController` (`register`, `login`, `me`, `logout`, `forgot-password`, `reset-password`, `assign-role`)
- `UserPolicy` (per-domain policy registration)
- Migrations: users, RBAC, devices/sessions/tokens, infra (cache/jobs)
- `RolePermissionSeeder` (owner/admin/member roles + permissions)
- DTOs: `RegisterUserData`, `LoginData`

Routes are mounted under `/api/v1` by `routes/api.php`.
