<?php
/**
 * CyberTirah Framework Installation Script
 * Version: 2.0.0
 */

// Prevent direct access if already installed
if (file_exists('installed.lock')) {
    die('Framework is already installed. Delete installed.lock to reinstall.');
}

// Check PHP version
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    die('CyberTirah Framework requires PHP 8.0 or higher. Current version: ' . PHP_VERSION);
}

// Check required extensions
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    die('Missing required PHP extensions: ' . implode(', ', $missing_extensions));
}

// Installation process
echo "<h1>CyberTirah Framework Installation</h1>";
echo "<p>Installing CyberTirah Framework v2.0.0...</p>";

// Create necessary directories
$directories = [
    'Storage/logs',
    'Storage/cache',
    'Storage/temp',
    'Storage/uploads',
    'Storage/library'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<p>✓ Created directory: {$dir}</p>";
        } else {
            echo "<p>✗ Failed to create directory: {$dir}</p>";
        }
    } else {
        echo "<p>✓ Directory exists: {$dir}</p>";
    }
}

// Create .env file if it doesn't exist
if (!file_exists('.env')) {
    if (file_exists('.env copy')) {
        copy('.env copy', '.env');
        echo "<p>✓ Created .env file from template</p>";
    } else {
        // Create basic .env file
        $env_content = "APP_NAME=CT Frame\nAPP_ENV=development\nAPP_DEBUG=true\nDB_DRIVER=mysql\nDB_HOST=localhost\nDB_USERNAME=root\nDB_PASSWORD=\nDB_DATABASE=ct_frame";
        file_put_contents('.env', $env_content);
        echo "<p>✓ Created basic .env file</p>";
    }
} else {
    echo "<p>✓ .env file already exists</p>";
}

// Create database tables
echo "<h2>Database Setup</h2>";
echo "<p>Please create the database 'ct_frame' in your MySQL server.</p>";
echo "<p>You can run the following SQL commands:</p>";
echo "<pre>";
echo "CREATE DATABASE IF NOT EXISTS ct_frame CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
echo "USE ct_frame;\n\n";

// Blog table
echo "-- Blog table\n";
echo "CREATE TABLE IF NOT EXISTS blogs (\n";
echo "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
echo "    title VARCHAR(255) NOT NULL,\n";
echo "    slug VARCHAR(255) UNIQUE,\n";
echo "    content TEXT,\n";
echo "    excerpt TEXT,\n";
echo "    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',\n";
echo "    featured_image VARCHAR(500),\n";
echo "    tags TEXT,\n";
echo "    meta_title VARCHAR(60),\n";
echo "    meta_description VARCHAR(160),\n";
echo "    view_count INT DEFAULT 0,\n";
echo "    author_id INT,\n";
echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
echo "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n";
echo ");\n\n";

// Users table
echo "-- Users table\n";
echo "CREATE TABLE IF NOT EXISTS users (\n";
echo "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
echo "    username VARCHAR(50) UNIQUE NOT NULL,\n";
echo "    email VARCHAR(100) UNIQUE NOT NULL,\n";
echo "    password VARCHAR(255) NOT NULL,\n";
echo "    first_name VARCHAR(50),\n";
echo "    last_name VARCHAR(50),\n";
echo "    role ENUM('admin', 'user') DEFAULT 'user',\n";
echo "    status ENUM('active', 'inactive') DEFAULT 'active',\n";
echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
echo "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n";
echo ");\n\n";

// Settings table
echo "-- Settings table\n";
echo "CREATE TABLE IF NOT EXISTS settings (\n";
echo "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
echo "    setting_key VARCHAR(100) UNIQUE NOT NULL,\n";
echo "    setting_value TEXT,\n";
echo "    description TEXT,\n";
echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
echo "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n";
echo ");\n\n";

// Insert default settings
echo "-- Default settings\n";
echo "INSERT INTO settings (setting_key, setting_value, description) VALUES\n";
echo "('site_name', 'CT Frame', 'Site name'),\n";
echo "('site_description', 'CyberTirah Framework', 'Site description'),\n";
echo "('site_url', 'http://localhost', 'Site URL'),\n";
echo "('admin_email', 'admin@localhost', 'Admin email'),\n";
echo "('timezone', 'UTC', 'Default timezone');\n\n";

// Insert default admin user
echo "-- Default admin user (password: admin123)\n";
echo "INSERT INTO users (username, email, password, first_name, last_name, role) VALUES\n";
echo "('admin', 'admin@localhost', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin');\n";
echo "</pre>";

// Create installed.lock file
file_put_contents('installed.lock', date('Y-m-d H:i:s'));
echo "<p>✓ Created installed.lock file</p>";

echo "<h2>Installation Complete!</h2>";
echo "<p>CyberTirah Framework has been successfully installed.</p>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li>Create the database and run the SQL commands above</li>";
echo "<li>Update your .env file with correct database credentials</li>";
echo "<li>Set proper file permissions (755 for directories, 644 for files)</li>";
echo "<li>Delete this install.php file for security</li>";
echo "</ul>";
echo "<p><a href='index.php'>Go to your application</a></p>";
?>
