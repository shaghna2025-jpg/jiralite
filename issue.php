<?php require 'config.php'; require_login(); check_csrf();
$st = $pdo->prepare('SELECT i.*, p.pkey, r.name reporter_name FROM issues i JOIN projects p ON p.id=i.project_id JOIN users r ON r.id=i.reporter_id WHERE i.id=?');
$st->execute([(int)($_GET['id'] ?? 0)]);
$issue = $st->fetch();
if (!$issue) { http_response_code(404); exit('Issue not found'); }
$project = get_project_for_user($pdo, $issue['project_id']);
$team = members($pdo, $project['id']);
$id = $issue['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'update') {
        $assignee = (int)$_POST['assignee_id'] ?: null;
        if ($assignee && !in_array($assignee, array_column($team, 'id'))) $assignee = null;
        $pdo->prepare('UPDATE issues SET title=?,description=?,type=?,priority=?,status=?,assignee_id=? WHERE id=?')->execute([
            trim($_POST['title']) ?: $issue['title'], $_POST['description'],
            in_array($_POST['type'], TYPES) ? $_POST['type'] : $issue['type'],
            in_array($_POST['priority'], PRIORITIES) ? $_POST['priority'] : $issue['priority'],
            isset(STATUSES[$_POST['status']]) ? $_POST['status'] : $issue['status'], $assignee, $id]);
    } elseif (($_POST['action'] ?? '') === 'comment' && trim($_POST['body'])) {
        $pdo->prepare('INSERT INTO comments(issue_id,user_id,body) VALUES(?,?,?)')->execute([$id, user()['id'], trim($_POST['body'])]);
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare('DELETE FROM issues WHERE id=?')->execute([$id]);
        header('Location: project.php?id=' . $project['id']); exit;
    }
    header("Location: issue.php?id=$id"); exit;
}
$st = $pdo->prepare('SELECT c.*, u.name FROM comments c JOIN users u ON u.id=c.user_id WHERE issue_id=? ORDER BY c.id');
$st->execute([$id]);
$comments = $st->fetchAll();
include 'header.php'; ?>
<p><a href="project.php?id=<?= $project['id'] ?>">← <?= e($project['name']) ?></a></p>
<h2><?= e($issue['pkey']) ?>-<?= $issue['issue_no'] ?></h2>
<div class="grid">
<div>
  <div class="panel"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="update">
  <input name="title" value="<?= e($issue['title']) ?>">
  <textarea name="description" rows="6"><?= e($issue['description']) ?></textarea>
  <div class="row">
    <select name="status"><?php foreach (STATUSES as $k => $l) echo "<option value='$k'" . ($k === $issue['status'] ? ' selected' : '') . ">$l</option>"; ?></select>
    <select name="type"><?php foreach (TYPES as $t) echo "<option" . ($t === $issue['type'] ? ' selected' : '') . ">$t</option>"; ?></select>
    <select name="priority"><?php foreach (PRIORITIES as $p) echo "<option" . ($p === $issue['priority'] ? ' selected' : '') . ">$p</option>"; ?></select>
    <select name="assignee_id"><option value="0">Unassigned</option>
    <?php foreach ($team as $m) echo "<option value='{$m['id']}'" . ($m['id'] == $issue['assignee_id'] ? ' selected' : '') . '>' . e($m['name']) . '</option>'; ?></select>
  </div><button>Save</button></form>
  <form method="post" onsubmit="return confirm('Delete this issue?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete">
  <button style="background:#bf2600">Delete</button></form></div>

  <div class="panel"><h3>Comments</h3>
  <?php foreach ($comments as $c): ?>
    <p><strong><?= e($c['name']) ?></strong> <small><?= e($c['created_at']) ?></small><br><?= nl2br(e($c['body'])) ?></p><hr>
  <?php endforeach; ?>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="comment">
  <textarea name="body" placeholder="Add a comment" required></textarea><button>Comment</button></form></div>
</div>
<div class="panel"><small>Reporter</small><br><?= e($issue['reporter_name']) ?><br><br>
<small>Created</small><br><?= e($issue['created_at']) ?><br><br><small>Updated</small><br><?= e($issue['updated_at']) ?></div>
</div>
<?php include 'footer.php'; ?>
