<?php

declare(strict_types=1);
echo '<pre>';
// Test framework loading and basic functionality
echo "Testing CyberTirah Framework...\n";

try {
    require_once 'Brain/ct_brain.php';
    echo "✓ Framework loaded successfully\n";
    
    // Test Registry
    $registry = $GLOBALS['registry'] ?? null;
    if ($registry) {
        echo "✓ Registry: " . get_class($registry) . "\n";
        
        // Test basic registry functionality
        if (method_exists($registry, 'set') && method_exists($registry, 'get')) {
            $registry->set('test_key', 'test_value');
            $value = $registry->get('test_key');
            if ($value === 'test_value') {
                echo "✓ Registry set/get working\n";
            } else {
                echo "✗ Registry set/get failed\n";
            }
        }
    } else {
        echo "✗ Registry not found\n";
    }
    
    // Test Router
    if (class_exists('Router')) {
        echo "✓ Router class exists\n";
        
        // Test basic router functionality
        if (method_exists('Router', 'get')) {
            Router::get('/test', function() { return 'test route'; });
            echo "✓ Router route registration working\n";
        }
    } else {
        echo "✗ Router class not found\n";
    }
    
    // Test Database class
    if (class_exists('Database')) {
        echo "✓ Database class exists\n";
    } else {
        echo "✗ Database class not found\n";
    }
    
    // Test Model class
    if (class_exists('Model')) {
        echo "✓ Model class exists\n";
    } else {
        echo "✗ Model class not found\n";
    }
    
    // Test Controller class
    if (class_exists('Controller')) {
        echo "✓ Controller class exists\n";
    } else {
        echo "✗ Controller class not found\n";
    }
    
    echo "\nFramework test completed!\n";
    
} catch (Throwable $e) {
    echo "✗ Framework test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
