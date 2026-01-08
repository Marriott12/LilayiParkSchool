<?php
require_once 'includes/bootstrap.php';

echo "<h2>CSRF Debug Test</h2>";

echo "<h3>Session Info:</h3>";
echo "Session Status: " . session_status() . " (2 = active)<br>";
echo "Session ID: " . session_id() . "<br>";
echo "CSRF Token in Session: " . (isset($_SESSION['csrf_token']) ? htmlspecialchars($_SESSION['csrf_token']) : 'NOT SET') . "<br>";

echo "<h3>POST Data:</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST csrf_token: " . (isset($_POST['csrf_token']) ? htmlspecialchars($_POST['csrf_token']) : 'NOT SENT') . "<br>";
    echo "All POST: <pre>" . htmlspecialchars(print_r($_POST, true)) . "</pre>";
    
    // Validate token
    if (!isset($_SESSION['csrf_token'])) {
        echo "<p style='color:red'>FAIL: No session token</p>";
    } elseif (empty($_POST['csrf_token'])) {
        echo "<p style='color:red'>FAIL: No POST token</p>";
    } elseif (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo "<p style='color:red'>FAIL: Token mismatch</p>";
        echo "Session: " . htmlspecialchars($_SESSION['csrf_token']) . "<br>";
        echo "POST: " . htmlspecialchars($_POST['csrf_token']) . "<br>";
    } else {
        echo "<p style='color:green'>SUCCESS: Token valid!</p>";
    }
} else {
    echo "No POST data yet<br>";
}

echo "<h3>Test Form:</h3>";
$csrfField = CSRF::field();
echo "Generated Field: " . htmlspecialchars($csrfField) . "<br><br>";
?>
<form method="POST" action="">
    <?= CSRF::field() ?>
    <input type="text" name="test_field" value="test value" />
    <button type="submit">Submit Test</button>
</form>

<h3>Raw HTML Source of CSRF Field:</h3>
<textarea rows="3" cols="80"><?= htmlspecialchars(CSRF::field()) ?></textarea>
