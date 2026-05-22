<?php
require_once __DIR__ . '/db.php';
session_start();
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit; }
$username = $_SESSION['username'];

$res = $conn->query("SELECT bio FROM users WHERE username = '$username' LIMIT 1");
$bio = ($res && $res->num_rows) ? $res->fetch_assoc()['bio'] : '';
$msg = $_GET['msg'] ?? '';   // FLAW: reflected XSS
?>
<!DOCTYPE html>
<html>
<head>
  <title>Dashboard</title>
  <style>body{font-family:sans-serif;max-width:560px;margin:50px auto}h2{color:#2a6}.box{border:1px solid #ccc;padding:10px;margin:10px 0;border-radius:6px}</style>
</head>
<body>
  <h2>Welcome, <?php echo $username; ?>!</h2>
  <?php if ($msg): ?><div class="box" style="background:#ffe"><?php echo $msg; ?></div><?php endif; ?>
  <h3>Your bio:</h3>
  <div class="box"><?php echo $bio; ?></div>
  <p><a href="logout.php">Logout</a> | <a href="download.php?file=welcome.txt">Download welcome.txt</a></p>
</body>
</html>
