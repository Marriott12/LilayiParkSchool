<?php
/**
 * Script to fix hard-coded paths throughout the application
 * Replaces /LilayiParkSchool/ with relative paths for production compatibility
 */

$rootDir = __DIR__;
$filesFixed = 0;
$totalReplacements = 0;

// Patterns to replace
$patterns = [
    "header('Location: " => "header('Location: ",
    'header("Location: ' => 'header("Location: ',
    "apiEndpoint: '" => "apiEndpoint: '",
    'apiEndpoint: "' => 'apiEndpoint: "',
    'action="' => 'action="',
    "action='" => "action='",
    'href="' => 'href="',
    "href='" => "href='",
    'src="' => 'src="',
    "src='" => "src='",
];

// Get all PHP files
$directory = new RecursiveDirectoryIterator($rootDir);
$iterator = new RecursiveIteratorIterator($directory);
$phpFiles = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

foreach ($phpFiles as $file) {
    $filePath = $file[0];
    
    // Skip vendor and node_modules directories
    if (strpos($filePath, '/vendor/') !== false || strpos($filePath, '\vendor\\') !== false ||
        strpos($filePath, '/node_modules/') !== false || strpos($filePath, '\node_modules\\') !== false) {
        continue;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $fileReplacements = 0;
    
    // Apply all patterns
    foreach ($patterns as $search => $replace) {
        $newContent = str_replace($search, $replace, $content);
        if ($newContent !== $content) {
            $count = substr_count($content, $search);
            $fileReplacements += $count;
            $content = $newContent;
        }
    }
    
    // If content changed, save the file
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        $filesFixed++;
        $totalReplacements += $fileReplacements;
        echo "Fixed: " . str_replace($rootDir, '', $filePath) . " ($fileReplacements replacements)\n";
    }
}

echo "\n=== Summary ===\n";
echo "Files fixed: $filesFixed\n";
echo "Total replacements: $totalReplacements\n";
echo "\nAll hard-coded /LilayiParkSchool/ paths have been replaced with relative paths.\n";
echo "The application should now work on both local (localhost/LilayiParkSchool) and production environments.\n";
?>
