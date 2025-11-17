<?php
/**
 * Database Verification Script
 * Place this file in: ticketing_v2/public/check_database.php
 * Access: http://localhost/ticketing_v2/public/check_database.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Database Check</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.status { padding: 10px; margin: 10px 0; border-radius: 5px; }
.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
h1 { color: #333; }
h2 { color: #666; margin-top: 20px; }
pre { background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
table { border-collapse: collapse; width: 100%; background: white; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #667eea; color: white; }
</style></head><body>";

echo "<h1>🔍 Ticketing System Database Verification</h1>";

// Step 1: Check PHP PDO Extension
echo "<h2>1. PHP PDO Extension Check</h2>";
if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
    echo "<div class='status success'>✅ PDO and PDO_MySQL extensions are installed</div>";
} else {
    echo "<div class='status error'>❌ PDO or PDO_MySQL extension is missing. Please enable them in php.ini</div>";
    exit;
}

// Step 2: Database Connection
echo "<h2>2. Database Connection Test</h2>";
$host = "localhost";
$dbname = "ticketing_v2";
$username = "root";
$password = "";

try {
    $dsn = "mysql:host={$host};charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<div class='status success'>✅ Connected to MySQL Server</div>";
    
    // Check if database exists
    $stmt = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    if ($stmt->fetch()) {
        echo "<div class='status success'>✅ Database '{$dbname}' exists</div>";
        $conn->exec("USE $dbname");
    } else {
        echo "<div class='status error'>❌ Database '{$dbname}' does not exist. Please create it first!</div>";
        echo "<div class='status info'>Run this SQL in phpMyAdmin:<br><pre>CREATE DATABASE ticketing_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</pre></div>";
        exit;
    }
    
} catch (PDOException $e) {
    echo "<div class='status error'>❌ Connection Failed: " . $e->getMessage() . "</div>";
    exit;
}

// Step 3: Check Required Tables
echo "<h2>3. Required Tables Check</h2>";
$requiredTables = [
    'users' => 'User accounts',
    'employees' => 'Employee profiles',
    'service_providers' => 'Service provider profiles',
    'departments' => 'Department information',
    'device_types' => 'Device types',
    'tickets' => 'Support tickets',
    'ticket_updates' => 'Ticket timeline',
    'ticket_attachments' => 'File attachments',
    'ticket_ratings' => 'Service ratings',
    'notifications' => 'In-app notifications',
    'audit_logs' => 'System audit logs'
];

$missingTables = [];
foreach ($requiredTables as $table => $description) {
    $stmt = $conn->query("SHOW TABLES LIKE '$table'");
    if ($stmt->fetch()) {
        echo "<div class='status success'>✅ Table '{$table}' exists ({$description})</div>";
    } else {
        echo "<div class='status error'>❌ Table '{$table}' is missing ({$description})</div>";
        $missingTables[] = $table;
    }
}

if (!empty($missingTables)) {
    echo "<div class='status error'><strong>Missing tables detected!</strong> Please run the database.sql file to create all required tables.</div>";
    exit;
}

// Step 4: Check users table structure
echo "<h2>4. Users Table Structure Check</h2>";
$stmt = $conn->query("DESCRIBE users");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
$userColumns = array_column($columns, 'Field');

$requiredColumns = ['id', 'email', 'password', 'user_type', 'theme', 'is_active', 'created_at'];
$hasAllColumns = true;

echo "<table><tr><th>Column</th><th>Status</th></tr>";
foreach ($requiredColumns as $col) {
    if (in_array($col, $userColumns)) {
        echo "<tr><td>{$col}</td><td style='color: green;'>✅ Present</td></tr>";
    } else {
        echo "<tr><td>{$col}</td><td style='color: red;'>❌ Missing</td></tr>";
        $hasAllColumns = false;
    }
}
echo "</table>";

if (!$hasAllColumns) {
    echo "<div class='status error'>⚠️ Users table is missing required columns. Please recreate the database.</div>";
}

// Step 5: Check if admin user exists
echo "<h2>5. Admin User Check</h2>";
$stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin'");
$result = $stmt->fetch();
if ($result['count'] > 0) {
    echo "<div class='status success'>✅ Admin user(s) found: {$result['count']}</div>";
    
    // Show admin users
    $stmt = $conn->query("SELECT id, email, is_active, created_at FROM users WHERE user_type = 'admin'");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table><tr><th>ID</th><th>Email</th><th>Status</th><th>Created</th></tr>";
    foreach ($admins as $admin) {
        $status = $admin['is_active'] ? '✅ Active' : '❌ Inactive';
        echo "<tr><td>{$admin['id']}</td><td>{$admin['email']}</td><td>{$status}</td><td>{$admin['created_at']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<div class='status warning'>⚠️ No admin user found. You need to create one!</div>";
    echo "<div class='status info'>You can register an admin account through the system or insert one manually.</div>";
}

// Step 6: Test password hashing
echo "<h2>6. Password Verification Test</h2>";
$testPassword = "password123";
$testHash = password_hash($testPassword, PASSWORD_DEFAULT);
if (password_verify($testPassword, $testHash)) {
    echo "<div class='status success'>✅ Password hashing and verification works correctly</div>";
} else {
    echo "<div class='status error'>❌ Password verification failed. PHP password functions may not be working.</div>";
}

// Step 7: Check file permissions
echo "<h2>7. File Permissions Check</h2>";
$uploadsDir = dirname(__DIR__) . '/uploads/tickets';
if (!file_exists($uploadsDir)) {
    if (mkdir($uploadsDir, 0755, true)) {
        echo "<div class='status success'>✅ Created uploads directory: {$uploadsDir}</div>";
    } else {
        echo "<div class='status warning'>⚠️ Could not create uploads directory. You may need to create it manually.</div>";
    }
} else {
    echo "<div class='status success'>✅ Uploads directory exists: {$uploadsDir}</div>";
}

if (is_writable($uploadsDir)) {
    echo "<div class='status success'>✅ Uploads directory is writable</div>";
} else {
    echo "<div class='status warning'>⚠️ Uploads directory is not writable. Attachments won't work!</div>";
}

// Summary
echo "<h2>📋 Summary</h2>";
if (empty($missingTables) && $hasAllColumns) {
    echo "<div class='status success'><h3>✅ All Systems Ready!</h3>";
    echo "Your database is properly configured. You can now:<br>";
    echo "1. <a href='login.php'>Go to Login Page</a><br>";
    echo "2. <a href='register.php'>Create an Account</a><br>";
    echo "</div>";
} else {
    echo "<div class='status error'><h3>❌ Configuration Issues Found</h3>";
    echo "Please fix the issues above before using the system.<br>";
    echo "Make sure to run the database.sql file to create all required tables.";
    echo "</div>";
}

echo "<hr><p style='color: #999; text-align: center;'>Nexon IT Ticketing System - Database Verification Tool</p>";
echo "</body></html>";
?>