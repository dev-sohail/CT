<?php

declare(strict_types=1);

/**
 * CyberTirah Framework - Front Controller
 * 
 * Main entry point for the application
 * Handles request routing and application initialization
*/

// Prevent direct access to this file
if (basename($_SERVER['PHP_SELF']) === 'index.php' && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(403);
    exit('Direct access forbidden');
}

// Define core constants
try {
    defineCoreConstants();
} catch (RuntimeException $e) {
    displayError('Framework initialization failed: ' . $e->getMessage());
}

/**
 * Define core framework constants
 */
function defineCoreConstants(): void
{
    // Determine ROOT path
    $candidates = [
        realpath(__DIR__),
        realpath(__DIR__ . '/..'),
        realpath(__DIR__ . '/../..'),
    ];

    $rootPath = null;
    foreach ($candidates as $path) {
        if ($path && is_dir($path) && file_exists($path . '/Brain/ct_brain.php')) {
            $rootPath = $path;
            break;
        }
    }

    if (!$rootPath) {
        throw new RuntimeException('Unable to determine framework root path');
    }

    // Define constants
    if (!defined('ROOT')) {
        define('ROOT', $rootPath);
    }
    
    if (!defined('DS')) {
        define('DS', DIRECTORY_SEPARATOR);
    }

    if (!defined('INSTALL_LOCK_FILE')) {
define('INSTALL_LOCK_FILE', ROOT . DS . 'installed.lock');
    }

    if (!defined('BRAIN_DIR')) {
        define('BRAIN_DIR', ROOT . DS . 'Brain');
    }

    if (!defined('BRAIN_FILE')) {
        define('BRAIN_FILE', BRAIN_DIR . DS . 'ct_brain.php');
    }

    if (!defined('BODY_DIR')) {
        define('BODY_DIR', ROOT . DS . 'Body');
    }

    if (!defined('STORAGE_DIR')) {
        define('STORAGE_DIR', ROOT . DS . 'Storage');
    }
}

/**
 * Check if application is installed
 */
if (!file_exists(INSTALL_LOCK_FILE)) {
    handleInstallation();
}

/**
 * Verify core brain file exists
 */
if (!file_exists(BRAIN_FILE)) {
    displayError('Core application file not found: ' . basename(BRAIN_FILE));
}

/**
 * Initialize and run the application
 */
try {
    initializeApplication();
    runApplication();
} catch (Throwable $e) {
    handleApplicationError($e);
}

/**
 * Handle installation process
 */
function handleInstallation(): void
{
    $installationFile = BRAIN_DIR . DS . 'ct_installation.php';
    
    if (file_exists($installationFile)) {
        require_once $installationFile;
    } else {
        displayError('Installation system not found. Please ensure the framework is properly installed.');
    }
    
    exit;
}

/**
 * Initialize the application
 */
function initializeApplication(): void
{
    // Load the core brain system
    require_once BRAIN_FILE;
    
    // Verify essential classes are loaded
    if (!class_exists('Bootstrap')) {
        throw new RuntimeException('Bootstrap class not found');
    }
    
    if (!class_exists('Registry')) {
        throw new RuntimeException('Registry class not found');
    }
    
    if (!class_exists('Router')) {
        throw new RuntimeException('Router class not found');
    }
}

/**
 * Run the application
 */
function runApplication(): void
{
    // Get registry instance
    $registry = $GLOBALS['registry'] ?? Registry::getInstance();
    
    if (!$registry) {
        throw new RuntimeException('Registry not available');
    }
    
    // Load module routes
    loadModuleRoutes($registry);
    
    // Run the router
    Router::run();
}

/**
 * Load routes from all modules
 */
function loadModuleRoutes(object $registry): void
{
    if (!defined('BODY_DIR') || !is_dir(BODY_DIR)) {
        return;
    }
    
    $roles = ['admin', 'public', 'api', 'ai'];
    
    foreach ($roles as $role) {
        $rolePath = BODY_DIR . DS . $role;
        
        if (!is_dir($rolePath)) {
            continue;
        }
        
        $modules = array_filter(scandir($rolePath), function($item) use ($rolePath) {
            return $item !== '.' && $item !== '..' && is_dir($rolePath . DS . $item);
        });
        
        foreach ($modules as $module) {
            $routesFile = $rolePath . DS . $module . DS . 'routes.json';
            
            if (file_exists($routesFile)) {
                Router::loadFromJson($routesFile);
            }
        }
    }
}

/**
 * Handle application errors
 */
function handleApplicationError(Throwable $e): void
{
    http_response_code(500);
    
    // Log the error
    error_log("Application Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Display error based on environment
    if (defined('APP_ENV') && APP_ENV === 'development') {
        displayDevelopmentError($e);
    } else {
        displayProductionError();
    }
}

/**
 * Display development error
 */
function displayDevelopmentError(Throwable $e): void
{
    echo '<div style="margin: 2rem auto; max-width: 800px; padding: 2rem; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.5rem; font-family: monospace;">';
    echo '<h2 style="color: #dc3545; margin-top: 0;">Application Error</h2>';
    echo '<p style="color: #6c757d; margin-bottom: 1rem;"><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p style="color: #6c757d; margin-bottom: 1rem;"><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
    echo '<p style="color: #6c757d; margin-bottom: 1rem;"><strong>Line:</strong> ' . $e->getLine() . '</p>';
    echo '<details style="margin-top: 1rem;">';
    echo '<summary style="cursor: pointer; color: #6c757d;">Stack Trace</summary>';
    echo '<pre style="background: #e9ecef; padding: 1rem; margin-top: 0.5rem; border-radius: 0.25rem; overflow-x: auto;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</details>';
    echo '</div>';
}

/**
 * Display production error
 */
function displayProductionError(): void
{
    echo '<div style="margin: 2rem auto; max-width: 600px; padding: 2rem; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.5rem; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">';
    echo '<h2 style="color: #dc3545; margin-top: 0;">Application Error</h2>';
    echo '<p style="color: #6c757d; margin-bottom: 0;">An error occurred while processing your request. Please try again later.</p>';
    echo '</div>';
}

/**
 * Display general error
 */
function displayError(string $message): void
{
    echo '<div style="margin: 2rem auto; max-width: 600px; padding: 2rem; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.5rem; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">';
    echo '<h2 style="color: #dc3545; margin-top: 0;">Framework Error</h2>';
    echo '<p style="color: #6c757d; margin-bottom: 0;">' . htmlspecialchars($message) . '</p>';
    echo '</div>';
    exit;
}

/**
 * Development/Debugging Section
 * Uncomment as needed for development purposes
 */

// Uncomment the following lines for debugging:

/*
// Display all user-defined classes
$allClasses = get_declared_classes();
$myClasses = [];

foreach ($allClasses as $class) {
    $reflect = new ReflectionClass($class);
    if ($reflect->isUserDefined()) {
        $myClasses[] = $class;
    }
}

echo '<pre>';
print_r($myClasses);
echo '</pre>';
*/

/*
// Generate password hash
echo password_hash('admin2025', PASSWORD_DEFAULT);
*/

/*
// Display framework information
echo "ROOT: " . ROOT . "\n";
echo "BRAIN_FILE: " . BRAIN_FILE . "\n";
echo "BODY_DIR: " . BODY_DIR . "\n";
*/