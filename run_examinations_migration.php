<?php
/**
 * Run Examinations Migration
 * Access via: http://localhost/LilayiParkSchool/run_examinations_migration.php
 */

require_once __DIR__ . '/includes/bootstrap.php';
RBAC::requireAuth();

// Check if user is admin (roleID = 1)
if (Session::get('roleID') != 1) {
    Session::setFlash('error', 'Only administrators can run migrations');
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Read and split migration file
    $sql = file_get_contents(__DIR__ . '/migrations/007_create_examinations_tables.sql');
    
    // Remove comments and split by semicolon
    $statements = array_filter(
        array_map('trim',
            preg_split('/;(\r\n|\r|\n|$)/', 
                preg_replace('/^--.*$/m', '', $sql)
            )
        )
    );
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $db->exec($statement);
        }
    }
    
    Session::setFlash('success', "✅ Examinations migration completed successfully!");
    header('Location: examinations_list.php');
    exit;
    
} catch (PDOException $e) {
    Session::setFlash('error', "❌ Migration failed: " . $e->getMessage());
    header('Location: index.php');
    exit;
}
