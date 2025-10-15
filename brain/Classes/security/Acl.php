<?php

declare(strict_types=1);

/**
 * Class Acl
 *
 * Provides a comprehensive Access Control List (ACL) system to manage permissions for roles and users.
 */
class Acl
{
    protected array $roles = [];
    protected array $permissions = [];
    protected array $rolePermissions = [];
    protected array $resources = [];
    protected array $userRoles = [];

    /**
     * Add a role to the ACL.
     */
    public function addRole(string $role, ?string $parent = null): void
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
            $this->rolePermissions[$role] = [];
        }

        if ($parent !== null && in_array($parent, $this->roles, true)) {
            $this->rolePermissions[$role] = array_merge(
                $this->rolePermissions[$role],
                $this->rolePermissions[$parent] ?? []
            );
        }
    }

    /**
     * Remove a role from the ACL.
     */
    public function removeRole(string $role): void
    {
        $this->roles = array_filter($this->roles, fn($r) => $r !== $role);
        unset($this->rolePermissions[$role]);
    }

    /**
     * Add a permission to the ACL.
     */
    public function addPermission(string $permission, ?string $resource = null): void
    {
        if (!in_array($permission, $this->permissions, true)) {
            $this->permissions[] = $permission;
        }

        if ($resource !== null) {
            $this->addResource($resource);
        }
    }

    /**
     * Add a resource to the ACL.
     */
    public function addResource(string $resource): void
    {
        if (!in_array($resource, $this->resources, true)) {
            $this->resources[] = $resource;
        }
    }

    /**
     * Assign a permission to a role.
     */
    public function allow(string $role, string $permission, ?string $resource = null): void
    {
        if (!isset($this->rolePermissions[$role])) {
            throw new RuntimeException("Role '{$role}' does not exist.");
        }

        if (!in_array($permission, $this->permissions, true)) {
            throw new RuntimeException("Permission '{$permission}' does not exist.");
        }

        $key = $resource !== null ? "{$resource}:{$permission}" : $permission;

        if (!in_array($key, $this->rolePermissions[$role], true)) {
            $this->rolePermissions[$role][] = $key;
        }
    }

    /**
     * Deny a permission for a role.
     */
    public function deny(string $role, string $permission, ?string $resource = null): void
    {
        if (!isset($this->rolePermissions[$role])) {
            return;
        }

        $key = $resource !== null ? "{$resource}:{$permission}" : $permission;

        $this->rolePermissions[$role] = array_filter(
            $this->rolePermissions[$role],
            fn($p) => $p !== $key
        );
    }

    /**
     * Check if a role has a given permission.
     */
    public function can(string $role, string $permission, ?string $resource = null): bool
    {
        $key = $resource !== null ? "{$resource}:{$permission}" : $permission;
        return in_array($key, $this->rolePermissions[$role] ?? [], true);
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(int|string $userId, string $role): void
    {
        if (!in_array($role, $this->roles, true)) {
            throw new RuntimeException("Role '{$role}' does not exist.");
        }

        if (!isset($this->userRoles[$userId])) {
            $this->userRoles[$userId] = [];
        }

        if (!in_array($role, $this->userRoles[$userId], true)) {
            $this->userRoles[$userId][] = $role;
        }
    }

    /**
     * Check if a user has a permission.
     */
    public function userCan(int|string $userId, string $permission, ?string $resource = null): bool
    {
        $roles = $this->userRoles[$userId] ?? [];

        foreach ($roles as $role) {
            if ($this->can($role, $permission, $resource)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all roles for a user.
     */
    public function getUserRoles(int|string $userId): array
    {
        return $this->userRoles[$userId] ?? [];
    }

    /**
     * Get all permissions for a role.
     */
    public function getRolePermissions(string $role): array
    {
        return $this->rolePermissions[$role] ?? [];
    }

    /**
     * Check if a role exists.
     */
    public function roleExists(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }
}
