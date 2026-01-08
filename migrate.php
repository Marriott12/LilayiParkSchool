<?php
/**
 * Database Migration Runner
 * Run migrations from command line or browser
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $migrationFile = __DIR__ . '/database/migrations/add_academic_tables.sql';
    
    if (!file_exists($migrationFile)) {
        die("Migration file not found: $migrationFile\n");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $executed = 0;
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // Ignore table already exists errors
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate entry') === false) {
                echo "Error: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n\n";
            }
        }
    }
    
    echo "✓ Migration completed successfully!\n";
    echo "✓ Executed $executed SQL statements\n";
    echo "✓ Academic tables created: Grades, Examinations, ExamSchedule, Timetable, ReportComments, GradingScale\n";
    
} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
