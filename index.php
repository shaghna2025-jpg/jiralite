<?php require 'config.php'; require_login(); check_csrf();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = strtoupper(trim($_POST['pkey'])); $name = trim($_POST['name']);
    if (!preg_match('/^[A-Z]{2,10}$/', $key) || !$name) $err = 'Key must be 2-10 letters and name is required.';
    else {
        try {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO projects(pkey,name,description,owner_id) VALUES(?,?,?,?)')
                ->execute([$key, $name, $_POST['description'], user()['id']]);
            $pid = $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO project_members VALUES(?,?)')->execute([$pid, user()['id']]);
            $pdo->commit();
            header("Location: project.php?id=$pid"); exit;
        } catch (PDOException $ex) { $pdo->rollBack(); $err = 'Project key already exists.'; }
    }
}
$st = $pdo->prepare('SELECT p.*, (SELECT COUNT(*) FROM issues i WHERE i.project_id=p.id AND i.status<>"done") open_issues
  FROM projects p JOIN project_members m ON m.project_id=p.id WHERE m.user_id=? ORDER BY p.name');
$st->execute([user()['id']]);
$projects = $st->fetchAll();
include 'header.php'; ?>
<h2>Your projects</h2>
<div class="grid">
<div>
<?php foreach ($projects as $p): ?>
  <div class="panel"><a href="project.php?id=<?= $p['id'] ?>"><strong><?= e($p['pkey']) ?></strong> — <?= e($p['name']) ?></a>
  <p><?= e($p['description']) ?></p><small><?= $p['open_issues'] ?> open issues</small></div>
<?php endforeach; if (!$projects) echo '<p>No projects yet.</p>'; ?>
</div>
<div class="panel"><h3>New project</h3>
<?php if ($err) echo '<p class="error">' . e($err) . '</p>'; ?>
<form method="post"><?= csrf_field() ?>
<input name="pkey" placeholder="KEY (e.g. WEB)" maxlength="10" required>
<input name="name" placeholder="Project name" required>
<textarea name="description" placeholder="Description"></textarea>
<button>Create</button></form></div>
</div>
<?php include 'footer.php'; ?>
