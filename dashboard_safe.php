<?php
// FIX: Stored + Reflected XSS -> htmlspecialchars() on all dynamic output.
require_once __DIR__ . '/db.php';
session_start();
if (!isset($_SESSION['username'])) { header('Location: login_safe.php'); exit; }
$username = $_SESSION['username'];

// SQLi fix carried over: prepared statement.
$stmt = $conn->prepare('SELECT bio FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->bind_result($bio);
$stmt->fetch();
$stmt->close();

$msg = $_GET['msg'] ?? '';
function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html>
<head><title>Dashboard (Safe)</title>
<style>body{font-family:sans-serif;max-width:560px;margin:50px auto}h2{color:#06c}.box{border:1px solid #ccc;padding:10px;margin:10px 0;border-radius:6px}</style>
</head>
<body>
  <h2>Welcome, <?php echo e($username); ?>!</h2>
  <?php if ($msg): ?><div class="box" style="background:#ffe"><?php echo e($msg); ?></div><?php endif; ?>
  <h3>Your bio:</h3>
  <div class="box"><?php echo e($bio); ?></div>
  <p><a href="logout.php">Logout</a></p>
</body>
</html>
