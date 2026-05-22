<?php
require_once __DIR__ . '/db.php';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $bio      = $_POST['bio'] ?? '';

    // FLAW: concatenation (SQLi) + bio stored unsanitized (stored XSS)
    $sql = "INSERT INTO users (username, password, bio) VALUES ('$username', '$password', '$bio')";
    $msg = $conn->query($sql) ? 'Registered! You can now log in.' : ('Error: ' . $conn->error);
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Register</title>
  <style>body{font-family:sans-serif;max-width:420px;margin:60px auto}input,textarea{display:block;margin:6px 0;padding:6px;width:100%}h2{color:#2a6}</style>
</head>
<body>
  <h2>Rick Portal — Register</h2>
  <?php if ($msg): ?><p><?php echo $msg; ?></p><?php endif; ?>
  <form method="post">
    Username: <input type="text" name="username">
    Password: <input type="password" name="password">
    Bio: <textarea name="bio"></textarea>
    <input type="submit" value="Register">
  </form>
  <p><a href="login.php">Login</a></p>
</body>
</html>
