<?php require 'config.php'; require_login(); check_csrf();
$project = get_project_for_user($pdo, (int)($_GET['id'] ?? 0));
$pid = $project['id'];
$team = members($pdo, $pid);
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_issue' && trim($_POST['title'])) {
        $type = in_array($_POST['type'], TYPES) ? $_POST['type'] : 'task';
        $prio = in_array($_POST['priority'], PRIORITIES) ? $_POST['priority'] : 'medium';
        $assignee = (int)$_POST['assignee_id'] ?: null;
        if ($assignee && !in_array($assignee, array_column($team, 'id'))) $assignee = null;
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE projects SET issue_seq = LAST_INSERT_ID(issue_seq + 1) WHERE id=?')->execute([$pid]);
        $no = $pdo->query('SELECT LAST_INSERT_ID()')->fetchColumn();
        $pdo->prepare('INSERT INTO issues(project_id,issue_no,title,description,type,priority,reporter_id,assignee_id) VALUES(?,?,?,?,?,?,?,?)')
            ->execute([$pid, $no, trim($_POST['title']), $_POST['description'], $type, $prio, user()['id'], $assignee]);
        $pdo->commit();
        header("Location: project.php?id=$pid"); exit;
    }
    if ($action === 'move' && isset(STATUSES[$_POST['status'] ?? ''])) {
        $pdo->prepare('UPDATE issues SET status=? WHERE id=? AND project_id=?')->execute([$_POST['status'], (int)$_POST['issue_id'], $pid]);
        header("Location: project.php?id=$pid"); exit;
    }
    if ($action === 'add_member') {
        $st = $pdo->prepare('SELECT id FROM users WHERE email=?'); $st->execute([strtolower(trim($_POST['email']))]);
        if ($uid = $st->fetchColumn()) {
            $pdo->prepare('INSERT IGNORE INTO project_members VALUES(?,?)')->execute([$pid, $uid]);
            header("Location: project.php?id=$pid"); exit;
        }
        $msg = 'No user with that email.';
    }
}

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT i.*, u.name assignee_name FROM issues i LEFT JOIN users u ON u.id=i.assignee_id WHERE i.project_id=?';
$params = [$pid];
if ($q !== '') { $sql .= ' AND (i.title LIKE ? OR i.description LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
$st = $pdo->prepare($sql . ' ORDER BY FIELD(i.priority,"critical","high","medium","low"), i.id DESC');
$st->execute($params);
$board = array_fill_keys(array_keys(STATUSES), []);
foreach ($st->fetchAll() as $i) $board[$i['status']][] = $i;
include 'header.php'; ?>
<h2><?= e($project['pkey']) ?> — <?= e($project['name']) ?></h2>
<form method="get" style="display:flex;gap:8px;max-width:400px"><input type="hidden" name="id" value="<?= $pid ?>">
<input name="q" value="<?= e($q) ?>" placeholder="Search issues"><button>Search</button></form>

<div class="board">
<?php foreach (STATUSES as $s => $label): ?>
  <div class="col"><h3><?= $label ?> (<?= count($board[$s]) ?>)</h3>
  <?php foreach ($board[$s] as $i): ?>
    <div class="card">
      <a href="issue.php?id=<?= $i['id'] ?>"><?= e($i['title']) ?></a><br>
      <small><?= e($project['pkey']) ?>-<?= $i['issue_no'] ?></small>
      <span class="tag t-<?= $i['type'] ?>"><?= $i['type'] ?></span>
      <span class="tag p-<?= $i['priority'] ?>"><?= $i['priority'] ?></span>
      <?php if ($i['assignee_name']): ?><br><small>👤 <?= e($i['assignee_name']) ?></small><?php endif; ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="move">
      <input type="hidden" name="issue_id" value="<?= $i['id'] ?>">
      <select name="status" onchange="this.form.submit()">
        <?php foreach (STATUSES as $k => $l) echo "<option value='$k'" . ($k === $s ? ' selected' : '') . ">$l</option>"; ?>
      </select></form>
    </div>
  <?php endforeach; ?></div>
<?php endforeach; ?>
</div>

<div class="grid" style="margin-top:24px">
<div class="panel"><h3>Create issue</h3>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="create_issue">
<input name="title" placeholder="Summary" required>
<textarea name="description" placeholder="Description"></textarea>
<div class="row">
<select name="type"><?php foreach (TYPES as $t) echo "<option>$t</option>"; ?></select>
<select name="priority"><?php foreach (PRIORITIES as $p) echo "<option" . ($p === 'medium' ? ' selected' : '') . ">$p</option>"; ?></select>
<select name="assignee_id"><option value="0">Unassigned</option>
<?php foreach ($team as $m) echo "<option value='{$m['id']}'>" . e($m['name']) . "</option>"; ?></select>
</div><button>Create</button></form></div>

<div class="panel"><h3>Team</h3>
<ul><?php foreach ($team as $m) echo '<li>' . e($m['name']) . ' <small>' . e($m['email']) . '</small></li>'; ?></ul>
<?php if ($msg) echo '<p class="error">' . e($msg) . '</p>'; ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add_member">
<input name="email" type="email" placeholder="Add member by email" required><button>Add</button></form></div>
</div>
<?php include 'footer.php'; ?>
