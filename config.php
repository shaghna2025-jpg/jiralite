<?php
session_start();

$pdo = new PDO('mysql:host=localhost;dbname=jiralite;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

const STATUSES = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'in_review' => 'In Review', 'done' => 'Done'];
const TYPES = ['task', 'bug', 'story', 'epic'];
const PRIORITIES = ['low', 'medium', 'high', 'critical'];

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function user() { return $_SESSION['user'] ?? null; }
function require_login() { if (!user()) { header('Location: login.php'); exit; } }
function csrf() { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function check_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals(csrf(), $_POST['csrf'] ?? '')) {
        http_response_code(400); exit('Invalid CSRF token');
    }
}
function get_project_for_user(PDO $pdo, int $id): array {
    $st = $pdo->prepare('SELECT p.* FROM projects p JOIN project_members m ON m.project_id=p.id WHERE p.id=? AND m.user_id=?');
    $st->execute([$id, user()['id']]);
    $p = $st->fetch();
    if (!$p) { http_response_code(404); exit('Project not found or access denied'); }
    return $p;
}
function members(PDO $pdo, int $pid): array {
    $st = $pdo->prepare('SELECT u.id,u.name,u.email FROM users u JOIN project_members m ON m.user_id=u.id WHERE m.project_id=? ORDER BY u.name');
    $st->execute([$pid]);
    return $st->fetchAll();
}
