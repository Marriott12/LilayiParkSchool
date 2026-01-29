<?php
// install.php: Guided installer for LilayiParkSchool
// Blocks access if already installed
if (file_exists(__DIR__ . '/install.lock') || file_exists(__DIR__ . '/config/config.php')) {
    die('<h2>Already installed. Remove install.lock to reinstall.</h2>');
}

$error = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? '');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = trim($_POST['db_pass'] ?? '');
    $admin_user = trim($_POST['admin_user'] ?? '');
    $admin_pass = $_POST['admin_pass'] ?? '';
    $admin_email = trim($_POST['admin_email'] ?? '');
    // Basic validation
    if (!$db_host || !$db_name || !$db_user || !$admin_user || !$admin_pass || !$admin_email) {
        $error = 'All fields are required.';
    } else {
        // Try DB connection
        $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($mysqli->connect_errno) {
            $error = 'Database connection failed: ' . $mysqli->connect_error;
        } else {
            // Write config/config.php
            $config_dir = __DIR__ . '/config';
            if (!is_dir($config_dir)) mkdir($config_dir, 0755, true);
            $config_php = "<?php\nreturn [\n    'db_host' => '$db_host',\n    'db_name' => '$db_name',\n    'db_user' => '$db_user',\n    'db_pass' => '$db_pass',\n];\n";
            file_put_contents($config_dir . '/config.php', $config_php);
            // Create tables if not exist (minimal example)
            $mysqli->query("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) UNIQUE, password VARCHAR(255), email VARCHAR(100), role VARCHAR(20), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            // Hash admin password
            $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
            // Insert admin user
            $stmt = $mysqli->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')");
            $stmt->bind_param('sss', $admin_user, $hash, $admin_email);
            if (!$stmt->execute()) {
                $error = 'Failed to create admin user: ' . $stmt->error;
            } else {
                // Create install.lock
                file_put_contents(__DIR__ . '/install.lock', 'LilayiParkSchool installed on ' . date('Y-m-d H:i:s'));
                $success = true;
            }
            $stmt->close();
            $mysqli->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Install LilayiParkSchool</title>
    <style>body{font-family:sans-serif;max-width:500px;margin:2em auto;}label{display:block;margin-top:1em;}input{width:100%;padding:0.5em;}</style>
</head>
<body>
<h2>Install LilayiParkSchool</h2>
<?php if ($error): ?><div style="color:red;"><b><?= htmlspecialchars($error) ?></b></div><?php endif; ?>
<?php if ($success): ?>
    <div style="color:green;"><b>Installation complete! <a href="index.php">Go to login</a></b></div>
<?php else: ?>
<form method="post">
    <label>Database Host<input name="db_host" required value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>"></label>
    <label>Database Name<input name="db_name" required value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>"></label>
    <label>Database User<input name="db_user" required value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>"></label>
    <label>Database Password<input name="db_pass" type="password" required value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>"></label>
    <hr>
    <label>Admin Username<input name="admin_user" required value="<?= htmlspecialchars($_POST['admin_user'] ?? 'admin') ?>"></label>
    <label>Admin Password<input name="admin_pass" type="password" required></label>
    <label>Admin Email<input name="admin_email" type="email" required value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>"></label>
    <button type="submit" style="margin-top:1em;">Install</button>
</form>
<?php endif; ?>
</body>
</html>
