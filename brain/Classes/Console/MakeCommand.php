<?php
/**
 * Class MakeCommand
 *
 * Provides a simple way to register and execute custom CLI commands.
 */
class MakeCommand
{
    protected array $commands = [];

    /**
     * Register a new command.
     */
    public function register(string $name, callable $callback, string $description = ''): void
    {
        $this->commands[$name] = [
            'callback'     => $callback,
            'description'  => $description
        ];
    }

    /**
     * Execute a command by name with optional arguments.
     */
    public function run(string $name, array $arguments = []): void
    {
        if (!isset($this->commands[$name])) {
            echo "Command '{$name}' not found." . PHP_EOL;
            return;
        }

        call_user_func($this->commands[$name]['callback'], $arguments);
    }

    /**
     * List all registered commands.
     */
    public function list(): void
    {
        echo "Available commands:" . PHP_EOL;
        foreach ($this->commands as $name => $info) {
            echo "  {$name} - {$info['description']}" . PHP_EOL;
        }
    }
}
