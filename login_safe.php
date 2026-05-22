<?php
// FIX: SQLi -> prepared statements. Brute force -> lockout after N tries.
require_once __DIR__ . '/db.php';
session_start();

$error = '';
$MAX_ATTEMPTS = 5;
$LOCKOUT_SECS = 300;

$now = time();
if (!isset($_SESSION['attempts'])) { $_SESSION['attempts'] = 0; $_SESSION['lock_until'] = 0; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($now < $_SESSION['lock_until']) {
        $error = 'Too many attempts. Locked for ' . ($_SESSION['lock_until'] - $now) . 's.';
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // FIX (SQLi): parameterized query — input never becomes SQL code.
        $stmt = $conn->prepare('SELECT password FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->bind_result($stored);
        $found = $stmt->fetch();
        $stmt->close();

        // Production: store password_hash() and use password_verify($password, $stored).
        if ($found && hash_equals((string)$stored, (string)$password)) {
            session_regenerate_id(true);
            $_SESSION['attempts'] = 0;
            $_SESSION['username'] = $username;
            header('Location: dashboard_safe.php');
            exit;
        }

        // FIX (brute force): count failures, lock the account temporarily.
        $_SESSION['attempts']++;
        if ($_SESSION['attempts'] >= $MAX_ATTEMPTS) {
            $_SESSION['lock_until'] = $now + $LOCKOUT_SECS;
            $_SESSION['attempts'] = 0;
        }
        $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Login (Safe)</title>
<style>body{font-family:sans-serif;max-width:420px;margin:60px auto}input{display:block;margin:6px 0;padding:6px;width:100%}h2{color:#06c}</style>
</head>
<body>
  <h2>Rick Portal — Login (Hardened)</h2>
  <?php if ($error): ?><p style="color:red"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
  <form method="post">
    Username: <input type="text" name="username">
    Password: <input type="password" name="password">
    <input type="submit" value="Login">
  </form>
</body>
</html>
