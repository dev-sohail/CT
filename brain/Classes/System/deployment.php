<?php
/**
 * Class Deployment
 *
 * Handles application deployment tasks such as pulling code, clearing caches,
 * running migrations, and restarting services.
 */
class Deployment
{
    protected string $deployPath;

    public function __construct(string $deployPath)
    {
        $this->deployPath = rtrim($deployPath, '/');
    }

    /**
     * Pull latest changes from version control.
     */
    public function pullCode(string $branch = 'main'): bool
    {
        return $this->runCommand("cd {$this->deployPath} && git fetch --all && git reset --hard origin/{$branch}");
    }

    /**
     * Run database migrations.
     */
    public function runMigrations(): bool
    {
        return $this->runCommand("cd {$this->deployPath} && php artisan migrate --force");
    }

    /**
     * Clear caches.
     */
    public function clearCaches(): bool
    {
        return $this->runCommand("cd {$this->deployPath} && php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear");
    }

    /**
     * Restart services (example for queue workers).
     */
    public function restartServices(): bool
    {
        return $this->runCommand("cd {$this->deployPath} && php artisan queue:restart");
    }

    /**
     * Run a shell command and return success.
     */
    protected function runCommand(string $command): bool
    {
        exec($command, $output, $status);
        return $status === 0;
    }
}
