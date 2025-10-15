<?php

declare(strict_types=1);

// CyberTirah Framework - Project Reorganization Utility
// Usage (Windows PowerShell): php Storage/automate/reorg.php        # dry run
//                              php Storage/automate/reorg.php --yes # apply

function printLine(string $msg): void {
    echo $msg . PHP_EOL;
}

function normalizeCaseInsensitiveRename(string $path, string $targetName, bool $apply, array &$actions): void {
    $dir = dirname($path);
    $base = basename($path);
    if ($base === $targetName) {
        return; // already correct
    }

    $temp = $dir . DIRECTORY_SEPARATOR . $base . '.tmp_ct_rename';
    $target = $dir . DIRECTORY_SEPARATOR . $targetName;

    $actions[] = [
        'type' => 'rename',
        'from' => $path,
        'to' => $target,
    ];

    if (!$apply) return;

    // Windows case-only rename requires a temp hop
    if (!rename($path, $temp)) {
        throw new RuntimeException("Failed temp rename: $path → $temp");
    }
    if (!rename($temp, $target)) {
        // attempt rollback
        @rename($temp, $path);
        throw new RuntimeException("Failed final rename: $temp → $target");
    }
}

function ensureDir(string $dir, bool $apply, array &$actions): void {
    if (is_dir($dir)) return;
    $actions[] = [ 'type' => 'mkdir', 'path' => $dir ];
    if ($apply) {
        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Failed to create directory: $dir");
        }
    }
}

function scanModules(string $bodyRoot): array {
    $roles = ['admin', 'public', 'api', 'ai', 'automate', 'user'];
    $modules = [];
    foreach ($roles as $role) {
        $rolePath = $bodyRoot . DIRECTORY_SEPARATOR . $role;
        if (!is_dir($rolePath)) continue;
        $items = @scandir($rolePath) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $modulePath = $rolePath . DIRECTORY_SEPARATOR . $item;
            if (is_dir($modulePath)) {
                $modules[] = [$role, $modulePath, $item];
            }
        }
    }
    return $modules;
}

function normalizeModule(string $role, string $modulePath, bool $apply, array &$actions): void {
    // Normalize Controllers/Models/Views directory names (case-insensitive)
    $candidates = [
        'Controllers' => ['Controllers', 'controllers', 'Controller', 'controller'],
        'Models'      => ['Models', 'models', 'Model', 'model'],
        'Views'       => ['Views', 'views', 'View', 'view'],
    ];

    foreach ($candidates as $proper => $variants) {
        $found = null;
        foreach ($variants as $name) {
            $p = $modulePath . DIRECTORY_SEPARATOR . $name;
            if (is_dir($p)) { $found = $p; break; }
        }
        if ($found === null) continue;
        normalizeCaseInsensitiveRename($found, $proper, $apply, $actions);
    }

    // Normalize Common folder casing inside role where present
    $commonCandidates = [
        $modulePath . DIRECTORY_SEPARATOR . 'Common',
        $modulePath . DIRECTORY_SEPARATOR . 'common',
    ];
    foreach ($commonCandidates as $p) {
        if (is_dir($p)) {
            normalizeCaseInsensitiveRename($p, 'Common', $apply, $actions);
            break;
        }
    }

    // Ensure routes.json is in module root if a routes file exists with wrong casing
    $routeNames = ['routes.json', 'Routes.json', 'ROUTES.JSON', 'routes.JSON'];
    $routeFound = null;
    foreach ($routeNames as $rn) {
        $rp = $modulePath . DIRECTORY_SEPARATOR . $rn;
        if (is_file($rp)) { $routeFound = $rp; break; }
    }
    if ($routeFound && basename($routeFound) !== 'routes.json') {
        $target = $modulePath . DIRECTORY_SEPARATOR . 'routes.json';
        $actions[] = ['type' => 'copy_overwrite', 'from' => $routeFound, 'to' => $target];
        if ($apply) {
            if (!copy($routeFound, $target)) {
                throw new RuntimeException("Failed to copy routes file to standard name: $routeFound → $target");
            }
            @unlink($routeFound);
        }
    }
}

function clearRouteCache(string $root, bool $apply, array &$actions): void {
    $cache = $root . DIRECTORY_SEPARATOR . 'Storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'routes.php';
    if (is_file($cache)) {
        $actions[] = ['type' => 'delete', 'path' => $cache];
        if ($apply) { @unlink($cache); }
    }
}

function main(): void {
    $apply = in_array('--yes', $GLOBALS['argv'] ?? [], true) || in_array('-y', $GLOBALS['argv'] ?? [], true);
    $root = realpath(__DIR__ . '/../../') ?: getcwd();
    $body = $root . DIRECTORY_SEPARATOR . 'Body';

    if (!is_dir($body)) {
        printLine('Body directory not found. Aborting.');
        exit(1);
    }

    $actions = [];
    $modules = scanModules($body);

    foreach ($modules as [$role, $modulePath, $moduleName]) {
        normalizeModule($role, $modulePath, $apply, $actions);
    }

    clearRouteCache($root, $apply, $actions);

    // Report
    $mode = $apply ? 'APPLY' : 'DRY-RUN';
    printLine("=== CyberTirah Reorg ($mode) ===");
    if (empty($actions)) {
        printLine('No changes needed.');
        return;
    }

    foreach ($actions as $a) {
        switch ($a['type']) {
            case 'rename':
                printLine("RENAME: {$a['from']} → {$a['to']}");
                break;
            case 'mkdir':
                printLine("MKDIR: {$a['path']}");
                break;
            case 'copy_overwrite':
                printLine("COPY: {$a['from']} → {$a['to']} (overwrite)");
                break;
            case 'delete':
                printLine("DELETE: {$a['path']}");
                break;
        }
    }

    if (!$apply) {
        printLine('Dry-run complete. Re-run with --yes to apply changes.');
    } else {
        printLine('Apply complete. Route cache cleared if present.');
    }
}

main();
