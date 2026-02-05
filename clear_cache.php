<?php
// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared successfully!<br>";
} else {
    echo "OPcache not enabled.<br>";
}

// Clear stat cache
clearstatcache(true);
echo "Stat cache cleared successfully!<br>";

echo "<br>All caches cleared. Please refresh your browser and try again.";
echo "<br><br><a href='teachers_bulk_accounts.php'>Go to Bulk Accounts</a>";
echo "<br><a href='users_form.php'>Go to Users Form</a>";
