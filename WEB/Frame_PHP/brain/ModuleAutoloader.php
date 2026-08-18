<?php

spl_autoload_register(function ($class) {
    $baseDir = dirname(__DIR__) . '/backend/';
    $fallbackDir = dirname(__DIR__) . '/../backend/';

    if (strpos($class, 'Controllers\\') === 0) {
        $relativeClass = substr($class, 12);
        $parts = explode('\\', $relativeClass);
        if (count($parts) >= 2) {
            $module = strtolower($parts[0]);
            $className = implode('/', array_slice($parts, 1));
            $file = $baseDir . $module . '/controllers/' . $className . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
            $file = $fallbackDir . $module . '/controllers/' . $className . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }

    if (strpos($class, 'Models\\') === 0) {
        $relativeClass = substr($class, 7);
        $parts = explode('\\', $relativeClass);
        if (count($parts) >= 2) {
            $module = strtolower($parts[0]);
            $className = implode('/', array_slice($parts, 1));
            $file = $baseDir . $module . '/models/' . $className . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
            $file = $fallbackDir . $module . '/models/' . $className . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
});
