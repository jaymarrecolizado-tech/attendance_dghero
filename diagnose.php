<?php
/**
 * Diagnostic Script for Hostinger Deployment
 * 
 * This script will check all common issues and provide detailed diagnostics.
 * DELETE this file after fixing issues!
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(30);

?>
<!DOCTYPE html>
<html>
<head>
    <title>ISSP Solo - Diagnostic Tool</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 15px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        h1 { color: #333; }
        h2 { color: #555; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .check-item { padding: 8px 0; border-bottom: 1px solid #eee; }
        .check-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <h1>🔍 ISSP Solo - Diagnostic Tool</h1>
    
    <?php
    $issues = [];
    $warnings = [];
    
    // 1. Check PHP Version
    echo '<div class="section">';
    echo '<h2>1. PHP Version</h2>';
    $phpVersion = PHP_VERSION;
    $phpMajor = (int)explode('.', $phpVersion)[0];
    if ($phpMajor >= 8) {
        echo '<div class="check-item success">✅ PHP Version: ' . $phpVersion . ' (OK)</div>';
    } else {
        echo '<div class="check-item error">❌ PHP Version: ' . $phpVersion . ' (Requires PHP 8.0+)</div>';
        $issues[] = 'PHP version too old';
    }
    echo '</div>';
    
    // 2. Check Required Extensions
    echo '<div class="section">';
    echo '<h2>2. PHP Extensions</h2>';
    $required = ['pdo', 'pdo_mysql', 'gd', 'mbstring', 'json', 'session'];
    foreach ($required as $ext) {
        if (extension_loaded($ext)) {
            echo '<div class="check-item success">✅ ' . $ext . ' extension loaded</div>';
        } else {
            echo '<div class="check-item error">❌ ' . $ext . ' extension NOT loaded</div>';
            $issues[] = "Missing extension: $ext";
        }
    }
    echo '</div>';
    
    // 3. Check File Structure
    echo '<div class="section">';
    echo '<h2>3. File Structure</h2>';
    $files = [
        'index.php' => 'Main entry point',
        'config/bootstrap.php' => 'Bootstrap configuration',
        'config/routes.php' => 'Route definitions',
        'vendor/autoload.php' => 'Composer autoloader',
        '.htaccess' => 'Apache configuration',
        '.env' => 'Environment configuration',
    ];
    
    foreach ($files as $file => $desc) {
        $path = __DIR__ . '/' . $file;
        if (file_exists($path)) {
            echo '<div class="check-item success">✅ ' . $file . ' - ' . $desc . '</div>';
        } else {
            echo '<div class="check-item error">❌ ' . $file . ' - MISSING (' . $desc . ')</div>';
            $issues[] = "Missing file: $file";
        }
    }
    echo '</div>';
    
    // 4. Check Directory Structure
    echo '<div class="section">';
    echo '<h2>4. Directory Structure</h2>';
    $dirs = ['src', 'views', 'migrations', 'storage', 'storage/qrcodes', 'storage/signatures', 'storage/imports', 'storage/runtime'];
    foreach ($dirs as $dir) {
        $path = __DIR__ . '/' . $dir;
        if (is_dir($path)) {
            $writable = is_writable($path);
            $status = $writable ? '✅' : '⚠️';
            $class = $writable ? 'success' : 'warning';
            echo '<div class="check-item ' . $class . '">' . $status . ' ' . $dir . ' - ' . ($writable ? 'Writable' : 'NOT Writable') . '</div>';
            if (!$writable) {
                $warnings[] = "Directory not writable: $dir";
            }
        } else {
            echo '<div class="check-item error">❌ ' . $dir . ' - MISSING</div>';
            $issues[] = "Missing directory: $dir";
        }
    }
    echo '</div>';
    
    // 5. Check .env Configuration
    echo '<div class="section">';
    echo '<h2>5. Environment Configuration (.env)</h2>';
    $envPath = __DIR__ . '/.env';
    if (file_exists($envPath)) {
        $envContent = file_get_contents($envPath);
        $envLines = explode("\n", $envContent);
        $envVars = [];
        foreach ($envLines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') continue;
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $envVars[trim($key)] = trim($value);
            }
        }
        
        $required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
        foreach ($required as $key) {
            if (isset($envVars[$key]) && !empty($envVars[$key])) {
                $display = ($key === 'DB_PASS') ? '***' : $envVars[$key];
                echo '<div class="check-item success">✅ ' . $key . ' = ' . htmlspecialchars($display) . '</div>';
            } else {
                echo '<div class="check-item error">❌ ' . $key . ' - NOT SET</div>';
                $issues[] = "Missing .env variable: $key";
            }
        }
    } else {
        echo '<div class="check-item error">❌ .env file NOT FOUND</div>';
        $issues[] = 'Missing .env file';
    }
    echo '</div>';
    
    // 6. Test Database Connection
    echo '<div class="section">';
    echo '<h2>6. Database Connection</h2>';
    try {
        if (file_exists(__DIR__ . '/config/bootstrap.php')) {
            require __DIR__ . '/config/bootstrap.php';
        } else {
            throw new Exception('Bootstrap file not found');
        }
        
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require __DIR__ . '/vendor/autoload.php';
        } else {
            throw new Exception('Vendor autoload not found');
        }
        
        $pdo = \App\Services\Database::pdo();
        echo '<div class="check-item success">✅ Database connection successful</div>';
        
        // Check if tables exist
        $tables = ['participants', 'attendance', 'admins', 'events', 'action_logs', 'report_templates'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo '<div class="check-item success">✅ Table exists: ' . $table . '</div>';
            } else {
                echo '<div class="check-item error">❌ Table missing: ' . $table . '</div>';
                $issues[] = "Missing table: $table";
            }
        }
        
    } catch (Exception $e) {
        echo '<div class="check-item error">❌ Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $issues[] = 'Database connection failed: ' . $e->getMessage();
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
    echo '</div>';
    
    // 7. Test Bootstrap Loading
    echo '<div class="section">';
    echo '<h2>7. Application Bootstrap</h2>';
    try {
        if (file_exists(__DIR__ . '/config/bootstrap.php')) {
            require __DIR__ . '/config/bootstrap.php';
            echo '<div class="check-item success">✅ Bootstrap loaded successfully</div>';
        } else {
            throw new Exception('Bootstrap file not found');
        }
        
        // Test autoloader
        if (class_exists('App\Core\Router')) {
            echo '<div class="check-item success">✅ Router class found</div>';
        } else {
            echo '<div class="check-item error">❌ Router class NOT found</div>';
            $issues[] = 'Router class not found';
        }
        
        if (class_exists('App\Services\Database')) {
            echo '<div class="check-item success">✅ Database service found</div>';
        } else {
            echo '<div class="check-item error">❌ Database service NOT found</div>';
            $issues[] = 'Database service not found';
        }
        
    } catch (Exception $e) {
        echo '<div class="check-item error">❌ Bootstrap Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $issues[] = 'Bootstrap failed: ' . $e->getMessage();
    }
    echo '</div>';
    
    // 8. Check .htaccess
    echo '<div class="section">';
    echo '<h2>8. Apache Configuration (.htaccess)</h2>';
    $htaccessPath = __DIR__ . '/.htaccess';
    if (file_exists($htaccessPath)) {
        echo '<div class="check-item success">✅ .htaccess file exists</div>';
        $htaccessContent = file_get_contents($htaccessPath);
        if (strpos($htaccessContent, 'RewriteEngine On') !== false) {
            echo '<div class="check-item success">✅ RewriteEngine enabled</div>';
        } else {
            echo '<div class="check-item warning">⚠️ RewriteEngine not found in .htaccess</div>';
            $warnings[] = 'RewriteEngine not configured';
        }
    } else {
        echo '<div class="check-item error">❌ .htaccess file NOT FOUND</div>';
        $issues[] = 'Missing .htaccess file';
    }
    echo '</div>';
    
    // Summary
    echo '<div class="section">';
    echo '<h2>📊 Summary</h2>';
    
    if (empty($issues) && empty($warnings)) {
        echo '<div class="check-item success" style="font-size: 18px; font-weight: bold;">✅ All checks passed! Your application should be working.</div>';
    } else {
        if (!empty($issues)) {
            echo '<div class="check-item error" style="font-size: 18px; font-weight: bold;">❌ ' . count($issues) . ' Critical Issue(s) Found:</div>';
            echo '<ul>';
            foreach ($issues as $issue) {
                echo '<li class="error">' . htmlspecialchars($issue) . '</li>';
            }
            echo '</ul>';
        }
        
        if (!empty($warnings)) {
            echo '<div class="check-item warning" style="font-size: 18px; font-weight: bold;">⚠️ ' . count($warnings) . ' Warning(s):</div>';
            echo '<ul>';
            foreach ($warnings as $warning) {
                echo '<li class="warning">' . htmlspecialchars($warning) . '</li>';
            }
            echo '</ul>';
        }
    }
    echo '</div>';
    
    // Recommendations
    if (!empty($issues) || !empty($warnings)) {
        echo '<div class="section">';
        echo '<h2>💡 Recommendations</h2>';
        echo '<ol>';
        if (in_array('Missing .env file', $issues)) {
            echo '<li>Create .env file from env.example and configure database credentials</li>';
        }
        if (in_array('Database connection failed', $issues)) {
            echo '<li>Check your .env database credentials</li>';
            echo '<li>Verify database exists in Hostinger hPanel</li>';
            echo '<li>Ensure database user has ALL PRIVILEGES</li>';
        }
        if (in_array('Missing table', $issues)) {
            echo '<li>Run database migrations: visit run_migrations_once.php</li>';
        }
        if (in_array('Missing .htaccess file', $issues)) {
            echo '<li>Ensure .htaccess file is uploaded to root directory</li>';
        }
        if (in_array('Directory not writable', $warnings)) {
            echo '<li>Set directory permissions: chmod 755 storage/ and subdirectories</li>';
        }
        echo '</ol>';
        echo '</div>';
    }
    ?>
    
    <div class="section" style="background: #fff3cd; border: 2px solid #ffc107;">
        <strong>⚠️ SECURITY WARNING:</strong><br>
        DELETE this file (<code>diagnose.php</code>) after fixing issues!
    </div>
</body>
</html>

