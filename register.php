<?php require 'config.php'; check_csrf();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']); $email = strtolower(trim($_POST['email'])); $pw = $_POST['password'];
    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 8) $err = 'Valid name, email and 8+ char password required.';
    else {
        try {
            $pdo->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)')
                ->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT)]);
            header('Location: login.php'); exit;
        } catch (PDOException $ex) { $err = 'Email already registered.'; }
    }
}
include 'header.php'; ?>
<div class="panel" style="max-width:400px;margin:auto"><h2>Register</h2>
<?php if ($err) echo '<p class="error">' . e($err) . '</p>'; ?>
<form method="post"><?= csrf_field() ?>
<input name="name" placeholder="Full name" required>
<input name="email" type="email" placeholder="Email" required>
<input name="password" type="password" placeholder="Password (8+ chars)" required>
<button>Create account</button> <a href="login.php">Login</a></form></div>
<?php include 'footer.php'; ?>
