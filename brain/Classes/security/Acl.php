<?php
/**
 * Class Acl
 *
 * Provides a simple Access Control List (ACL) system to manage permissions for roles and users.
 */
class Acl
{
    protected array $roles = [];
    protected array $permissions = [];
    protected array $rolePermissions = [];

    /**
     * Add a role to the ACL.
     */
    public function addRole(string $role): void
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
            $this->rolePermissions[$role] = [];
        }
    }

    /**
     * Add a permission to the ACL.
     */
    public function addPermission(string $permission): void
    {
        if (!in_array($permission, $this->permissions)) {
            $this->permissions[] = $permission;
        }
    }

    /**
     * Assign a permission to a role.
     */
    public function allow(string $role, string $permission): void
    {
        if (!isset($this->rolePermissions[$role])) {
            throw new Exception("Role '{$role}' does not exist.");
        }

        if (!in_array($permission, $this->permissions)) {
            throw new Exception("Permission '{$permission}' does not exist.");
        }

        if (!in_array($permission, $this->rolePermissions[$role])) {
            $this->rolePermissions[$role][] = $permission;
        }
    }

    /**
     * Check if a role has a given permission.
     */
    public function can(string $role, string $permission): bool
    {
        return in_array($permission, $this->rolePermissions[$role] ?? []);
    }
}
