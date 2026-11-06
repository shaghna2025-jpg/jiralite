<?php require 'config.php'; check_csrf();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $pdo->prepare('SELECT * FROM users WHERE email=?');
    $st->execute([strtolower(trim($_POST['email']))]);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'], $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $u['id'], 'name' => $u['name'], 'email' => $u['email']];
        header('Location: index.php'); exit;
    }
    $err = 'Invalid credentials.';
}
include 'header.php'; ?>
<div class="panel" style="max-width:400px;margin:auto"><h2>Login</h2>
<?php if ($err) echo '<p class="error">' . e($err) . '</p>'; ?>
<form method="post"><?= csrf_field() ?>
<input name="email" type="email" placeholder="Email" required>
<input name="password" type="password" placeholder="Password" required>
<button>Login</button> <a href="register.php">Register</a></form></div>
<?php include 'footer.php'; ?>
