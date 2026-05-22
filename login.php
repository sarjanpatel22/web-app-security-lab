<?php
require_once __DIR__ . '/db.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // FLAW: user input concatenated straight into SQL
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['username'] = $row['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
  <style>body{font-family:sans-serif;max-width:420px;margin:60px auto}input{display:block;margin:6px 0;padding:6px;width:100%}h2{color:#2a6}</style>
</head>
<body>
  <h2>Rick Portal — Login</h2>
  <?php if ($error): ?><p style="color:red"><?php echo $error; ?></p><?php endif; ?>
  <form method="post">
    Username: <input type="text" name="username">
    Password: <input type="password" name="password">
    <input type="submit" value="Login">
  </form>
  <p><a href="register.php">Register</a></p>
</body>
</html>
