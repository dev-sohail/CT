<?php
/**
 * Class RoleManager
 *
 * Handles user roles and permissions management.
 */
class RoleManager
{
    protected array $roles = [];

    /**
     * Add a new role.
     */
    public function addRole(string $role): void
    {
        if (!isset($this->roles[$role])) {
            $this->roles[$role] = [];
        }
    }

    /**
     * Assign a permission to a role.
     */
    public function assignPermission(string $role, string $permission): void
    {
        $this->addRole($role);
        if (!in_array($permission, $this->roles[$role])) {
            $this->roles[$role][] = $permission;
        }
    }

    /**
     * Check if a role has a specific permission.
     */
    public function roleHasPermission(string $role, string $permission): bool
    {
        return in_array($permission, $this->roles[$role] ?? []);
    }

    /**
     * Get all permissions for a role.
     */
    public function getPermissions(string $role): array
    {
        return $this->roles[$role] ?? [];
    }

    /**
     * Remove a permission from a role.
     */
    public function removePermission(string $role, string $permission): void
    {
        if (isset($this->roles[$role])) {
            $this->roles[$role] = array_values(array_filter(
                $this->roles[$role],
                fn($perm) => $perm !== $permission
            ));
        }
    }
}
