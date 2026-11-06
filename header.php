<?php require_once __DIR__ . '/config.php'; ?>
<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>JiraLite</title><link rel="stylesheet" href="style.css"></head>
<body>
<nav><a class="brand" href="index.php">JiraLite</a>
<?php if (user()): ?><span><?= e(user()['name']) ?> · <a href="logout.php">Logout</a></span><?php endif; ?></nav>
<main>
