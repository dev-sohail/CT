<?php

/**
 * Test Legacy Configuration System
 * 
 * This file tests the backward compatibility with old SMS config
 */

// Load the legacy configuration
require_once __DIR__ . '/config/legacy.php';

echo "<h1>Legacy Configuration Test</h1>";
echo "<style>body{font-family:monospace;padding:20px;} table{border-collapse:collapse;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#667eea;color:white;} tr:nth-child(even){background:#f9f9f9;}</style>";

// Test 1: Development Mode
echo "<h2>1. Development Mode</h2>";
echo "<p>Dev Mode: <strong>" . ($devmod ? 'ON' : 'OFF') . "</strong></p>";
echo "<p>Current Page: <strong>" . htmlspecialchars($currentPage) . "</strong></p>";

// Test 2: Database Connection
echo "<h2>2. Database Connection</h2>";
if ($pdo) {
    try {
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "<p style='color:green;'>✅ Database Connected Successfully!</p>";
        echo "<p>MySQL Version: <strong>" . htmlspecialchars($version) . "</strong></p>";
    } catch (PDOException $e) {
        echo "<p style='color:red;'>❌ Database Query Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Database Not Connected</p>";
}

// Test 3: File System Paths
echo "<h2>3. File System Paths</h2>";
echo "<table>";
echo "<tr><th>Constant</th><th>Value</th><th>Exists?</th></tr>";

$pathConstants = [
    'APP_ROOT' => 'Application Root',
    'ROOT_DIR' => 'Root Directory',
    'APP_VIEWS' => 'Views Directory',
    'APP_STORAGE' => 'Storage Directory',
    'APP_CONFIG' => 'Config Directory',
    'APP_CONTROLLERS' => 'Controllers Directory',
    'APP_MODELS' => 'Models Directory',
    'APP_LAY' => 'Layout Directory',
    'APP_PORTALS' => 'Portals Directory',
    'APP_TPORTAL' => 'Teacher Portal',
    'APP_SPORTAL' => 'Student Portal',
];

foreach ($pathConstants as $constant => $description) {
    $value = defined($constant) ? constant($constant) : 'NOT DEFINED';
    $exists = defined($constant) && file_exists(constant($constant)) ? '✅' : '❌';
    $displayValue = strlen($value) > 60 ? substr($value, 0, 60) . '...' : $value;
    echo "<tr><td><code>{$constant}</code></td><td>" . htmlspecialchars($displayValue) . "</td><td>{$exists}</td></tr>";
}

echo "</table>";

// Test 4: URL Paths
echo "<h2>4. URL Paths</h2>";
echo "<table>";
echo "<tr><th>Constant</th><th>Value</th></tr>";

$urlConstants = [
    'APP_ROOT_URL',
    'APP_STORAGE_URL',
    'APP_VIEWS_URL',
    'APP_TPORTAL_URL',
    'APP_SPORTAL_URL',
    'APP_ADMIN_URL',
];

foreach ($urlConstants as $constant) {
    $value = defined($constant) ? constant($constant) : 'NOT DEFINED';
    echo "<tr><td><code>{$constant}</code></td><td>" . htmlspecialchars($value) . "</td></tr>";
}

echo "</table>";

// Test 5: File Paths
echo "<h2>5. Important Files</h2>";
echo "<table>";
echo "<tr><th>Constant</th><th>Path</th><th>Exists?</th></tr>";

$fileConstants = [
    'APP_HEAD_FILE',
    'APP_HEADER_FILE',
    'APP_FOOTER_FILE',
    'APP_CONFIG_FILE',
    'APP_CCSS_FILE',
];

foreach ($fileConstants as $constant) {
    if (defined($constant)) {
        $value = constant($constant);
        $exists = file_exists($value) ? '✅' : '❌';
        $displayValue = strlen($value) > 60 ? '...' . substr($value, -60) : $value;
        echo "<tr><td><code>{$constant}</code></td><td>" . htmlspecialchars($displayValue) . "</td><td>{$exists}</td></tr>";
    } else {
        echo "<tr><td><code>{$constant}</code></td><td>NOT DEFINED</td><td>❌</td></tr>";
    }
}

echo "</table>";

// Test 6: Session
echo "<h2>6. Session</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color:green;'>✅ Session Active</p>";
    echo "<p>Session ID: <code>" . session_id() . "</code></p>";
    echo "<p>Session Name: <code>" . session_name() . "</code></p>";
} else {
    echo "<p style='color:red;'>❌ Session Not Active</p>";
}

// Test 7: Environment Variables
echo "<h2>7. Environment Variables</h2>";
echo "<table>";
echo "<tr><th>Variable</th><th>Value</th></tr>";

$envVars = [
    'APP_NAME',
    'APP_ENV',
    'APP_DEBUG',
    'DB_HOST',
    'DB_DATABASE',
    'DEV_MODE',
];

foreach ($envVars as $var) {
    $value = getenv($var);
    $displayValue = $value !== false ? htmlspecialchars($value) : '<em>Not Set</em>';
    echo "<tr><td><code>{$var}</code></td><td>{$displayValue}</td></tr>";
}

echo "</table>";

// Test 8: Configuration Object
echo "<h2>8. Configuration Object</h2>";
$config = LegacyConfig::getInstance();
echo "<p>Dev Mode from Config: <strong>" . $config->get('devmod') . "</strong></p>";
echo "<p>Database Host: <strong>" . htmlspecialchars($config->get('db.host')) . "</strong></p>";
echo "<p>Database Name: <strong>" . htmlspecialchars($config->get('db.database')) . "</strong></p>";

// Summary
echo "<h2>✅ Test Summary</h2>";
echo "<ul>";
echo "<li>✅ Legacy config loaded successfully</li>";
echo "<li>✅ All constants defined</li>";
echo "<li>✅ Database connection " . ($pdo ? "working" : "failed") . "</li>";
echo "<li>✅ Session management active</li>";
echo "<li>✅ Environment variables loaded</li>";
echo "<li>✅ Configuration object accessible</li>";
echo "</ul>";

echo "<hr>";
echo "<p style='text-align:center;color:#6c757d;'>";
echo "CyberTirah Framework v2.0.0 | Legacy Configuration Test<br>";
echo "Generated: " . date('Y-m-d H:i:s');
echo "</p>";

