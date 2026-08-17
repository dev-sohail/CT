<?php

namespace App\Support;

use Illuminate\Support\Facades\Config;
use Symfony\Component\Process\Process;

class DatabaseDumper
{
    public function dump(?string $database = null): string
    {
        $connection = Config::get('database.default');
        $db = $database ?? Config::get("database.connections.{$connection}.database");
        $user = Config::get("database.connections.{$connection}.username", 'root');
        $password = Config::get("database.connections.{$connection}.password", '');
        $host = Config::get("database.connections.{$connection}.host", '127.0.0.1');
        $port = Config::get("database.connections.{$connection}.port", 3306);

        $command = array_values(array_filter([
            Config::get('backup.mysqldump', 'mysqldump'),
            "--host={$host}",
            "--port={$port}",
            "--user={$user}",
            $password !== '' ? "--password={$password}" : null,
            '--single-transaction',
            '--no-tablespaces',
            Config::get('backup.include_routines', false) ? '--routines' : null,
            Config::get('backup.include_triggers', true) ? '--triggers' : null,
            $db,
        ]));

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('mysqldump failed: '.$process->getErrorOutput());
        }

        return $process->getOutput();
    }
}
