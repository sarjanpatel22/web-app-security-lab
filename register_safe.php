<?php
// FIX: SQLi on insert -> prepared statement. Stored XSS is neutralized at OUTPUT
// time (dashboard_safe.php) — the correct layer to encode.
require_once __DIR__ . '/db.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $bio      = $_POST['bio'] ?? '';

    // Production: $password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (username, password, bio) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $username, $password, $bio);
    $msg = $stmt->execute() ? 'Registered! You can now log in.' : 'Error registering.';
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head><title>Register (Safe)</title>
<style>body{font-family:sans-serif;max-width:420px;margin:60px auto}input,textarea{display:block;margin:6px 0;padding:6px;width:100%}h2{color:#06c}</style>
</head>
<body>
  <h2>Register (Hardened)</h2>
  <?php if ($msg): ?><p><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
  <form method="post">
    Username: <input type="text" name="username">
    Password: <input type="password" name="password">
    Bio: <textarea name="bio"></textarea>
    <input type="submit" value="Register">
  </form>
</body>
</html>
