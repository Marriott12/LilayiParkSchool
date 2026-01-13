<?php
/**
 * Database Diagnostic Script
 * Run this file in your browser to check database state
 * URL: https://your-domain.com/diagnose_db.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Diagnostic Report</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    pre { background: #f4f4f4; padding: 10px; border: 1px solid #ddd; }
</style>";

// Load database configuration
require_once 'config/database.php';

echo "<h2>1. Database Connection</h2>";
if (isset($db) && $db instanceof PDO) {
    echo "<p class='success'>✅ Database connected successfully!</p>";
} else {
    echo "<p class='error'>❌ Database connection failed!</p>";
    exit;
}

// Check if lilayiparkschool database is selected
try {
    $dbName = $db->query('SELECT DATABASE()')->fetchColumn();
    echo "<p>Current database: <strong>$dbName</strong></p>";
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Users Table Check</h2>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'Users'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ Users table exists</p>";
        
        // Show table structure
        echo "<h3>Users Table Structure:</h3>";
        $stmt = $db->query("DESCRIBE Users");
        echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $val) {
                echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ Users table does NOT exist!</p>";
        echo "<p>You need to import the database schema first.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Admin User Check</h2>";
try {
    $stmt = $db->query("SELECT userID, username, email, role, isActive, SUBSTRING(password, 1, 30) as pass_preview, LENGTH(password) as pass_length FROM Users WHERE username = 'admin'");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p class='success'>✅ Admin user found!</p>";
        echo "<table><tr><th>Field</th><th>Value</th></tr>";
        foreach ($admin as $key => $val) {
            echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($val ?? '') . "</td></tr>";
        }
        echo "</table>";
        
        if ($admin['pass_length'] == 60 && strpos($admin['pass_preview'], '$2y$') === 0) {
            echo "<p class='success'>✅ Password hash format looks correct (bcrypt)</p>";
        } else {
            echo "<p class='error'>❌ Password hash format incorrect!</p>";
            echo "<p>Length: {$admin['pass_length']} (should be 60)</p>";
            echo "<p>Format: {$admin['pass_preview']} (should start with \$2y\$)</p>";
        }
        
        if ($admin['isActive'] == 'Y') {
            echo "<p class='success'>✅ Admin is active</p>";
        } else {
            echo "<p class='error'>❌ Admin is NOT active (isActive = {$admin['isActive']})</p>";
        }
    } else {
        echo "<p class='error'>❌ Admin user NOT found in database!</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Test Teacher Check</h2>";
try {
    $stmt = $db->query("SELECT userID, username, email, role, isActive, SUBSTRING(password, 1, 30) as pass_preview FROM Users WHERE username = 'test.teacher'");
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($teacher) {
        echo "<p class='success'>✅ test.teacher user found!</p>";
        echo "<table><tr><th>Field</th><th>Value</th></tr>";
        foreach ($teacher as $key => $val) {
            echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($val ?? '') . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ test.teacher user NOT found</p>";
        echo "<p>This user might not have been created yet.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>5. All Users List</h2>";
try {
    $stmt = $db->query("SELECT userID, username, email, role, isActive FROM Users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total users in database: <strong>" . count($users) . "</strong></p>";
    
    if (count($users) > 0) {
        echo "<table><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Active</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['userID']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>" . ($user['isActive'] == 'Y' ? '✅' : '❌') . " {$user['isActive']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ No users found in database!</p>";
        echo "<p>You need to import seed_data_deployment.sql</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>6. Roles Check</h2>";
try {
    $stmt = $db->query("SELECT * FROM Roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($roles) > 0) {
        echo "<p class='success'>✅ Found " . count($roles) . " roles</p>";
        echo "<table><tr><th>Role ID</th><th>Role Name</th><th>Description</th></tr>";
        foreach ($roles as $role) {
            echo "<tr><td>{$role['roleID']}</td><td>{$role['roleName']}</td><td>" . htmlspecialchars($role['description'] ?? '') . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ No roles found!</p>";
    }
} catch (Exception $e) {
    echo "<p class='warning'>⚠️ Roles table might not exist: " . $e->getMessage() . "</p>";
}

echo "<h2>7. UserRoles Check</h2>";
try {
    $stmt = $db->query("DESCRIBE UserRoles");
    echo "<h3>UserRoles Table Structure:</h3>";
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
    
    // Check userID type
    $stmt = $db->query("SHOW COLUMNS FROM UserRoles LIKE 'userID'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($column) {
        if (strpos($column['Type'], 'int') !== false) {
            echo "<p class='success'>✅ UserRoles.userID is INT (correct!)</p>";
        } else {
            echo "<p class='error'>❌ UserRoles.userID is {$column['Type']} (should be INT)</p>";
            echo "<p><strong>ACTION REQUIRED:</strong> Run fix_userroles_type.sql</p>";
        }
    }
    
    // Show role assignments
    $stmt = $db->query("SELECT ur.userID, u.username, ur.roleID, r.roleName 
                        FROM UserRoles ur
                        LEFT JOIN Users u ON ur.userID = u.userID
                        LEFT JOIN Roles r ON ur.roleID = r.roleID");
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Role Assignments:</h3>";
    if (count($assignments) > 0) {
        echo "<table><tr><th>User ID</th><th>Username</th><th>Role ID</th><th>Role Name</th></tr>";
        foreach ($assignments as $assign) {
            echo "<tr><td>{$assign['userID']}</td><td>{$assign['username']}</td><td>{$assign['roleID']}</td><td>{$assign['roleName']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ No role assignments found!</p>";
        echo "<p>Admin user has no role assigned. This will cause login to fail.</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>8. Password Verification Test</h2>";
try {
    $stmt = $db->query("SELECT password FROM Users WHERE username = 'admin'");
    $hash = $stmt->fetchColumn();
    
    if ($hash) {
        echo "<p>Admin password hash: <code>" . htmlspecialchars(substr($hash, 0, 50)) . "...</code></p>";
        
        $testPassword = 'admin123';
        if (password_verify($testPassword, $hash)) {
            echo "<p class='success'>✅ Password 'admin123' MATCHES the hash!</p>";
        } else {
            echo "<p class='error'>❌ Password 'admin123' does NOT match the hash!</p>";
            echo "<p><strong>ACTION REQUIRED:</strong> Run fix_admin_login.sql or fix_userroles_type.sql</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>9. Auth Class Test</h2>";
try {
    require_once 'includes/Auth.php';
    
    echo "<p>Testing Auth::attempt('admin', 'admin123')...</p>";
    $result = Auth::attempt('admin', 'admin123');
    
    if ($result === true) {
        echo "<p class='success'>✅ Auth::attempt() returned TRUE - Login should work!</p>";
        echo "<p>Session data:</p>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    } else {
        echo "<p class='error'>❌ Auth::attempt() failed with message: " . htmlspecialchars($result) . "</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Auth test error: " . $e->getMessage() . "</p>";
}

echo "<h2>10. Recommendations</h2>";
echo "<ul>";

// Check if admin exists
try {
    $stmt = $db->query("SELECT COUNT(*) FROM Users WHERE username = 'admin'");
    if ($stmt->fetchColumn() == 0) {
        echo "<li class='error'>❌ Run <code>seed_data_deployment.sql</code> to create admin user</li>";
    }
} catch (Exception $e) {}

// Check if admin has role
try {
    $stmt = $db->query("SELECT COUNT(*) FROM UserRoles ur JOIN Users u ON ur.userID = u.userID WHERE u.username = 'admin'");
    if ($stmt->fetchColumn() == 0) {
        echo "<li class='error'>❌ Run <code>fix_userroles_type.sql</code> to assign admin role</li>";
    }
} catch (Exception $e) {}

// Check password
try {
    $stmt = $db->query("SELECT password FROM Users WHERE username = 'admin'");
    $hash = $stmt->fetchColumn();
    if ($hash && !password_verify('admin123', $hash)) {
        echo "<li class='error'>❌ Run <code>fix_admin_login.sql</code> to reset admin password</li>";
    }
} catch (Exception $e) {}

echo "<li>After making changes, refresh this page to verify</li>";
echo "<li>Delete this file after troubleshooting for security</li>";
echo "</ul>";

echo "<hr><p><strong>Diagnostic complete!</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
