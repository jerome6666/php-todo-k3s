<?php
/*
 * Minimal, SAFE Todo app (PHP) for the GitHub -> ECR -> K3s pipeline demo.
 * Single file, SQLite storage (no separate DB container) = smallest footprint.
 * App code uses prepared statements and output escaping (no deliberate flaws).
 */
declare(strict_types=1);

$DB_PATH = getenv('DB_PATH') ?: '/data/todo.sqlite';
$APP_VERSION = getenv('APP_VERSION') ?: 'dev';

function db(): PDO {
    static $pdo = null;
    global $DB_PATH;
    if ($pdo instanceof PDO) return $pdo;
    @mkdir(dirname($DB_PATH), 0775, true);
    $pdo = new PDO('sqlite:' . $DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE IF NOT EXISTS todos (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, done INTEGER DEFAULT 0)');
    return $pdo;
}
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$action = $_GET['action'] ?? '';

// Health check: used by Kubernetes readiness/liveness probes.
if ($action === 'health') {
    header('Content-Type: application/json');
    try { db()->query('SELECT 1'); echo json_encode(['status'=>'ok','version'=>$APP_VERSION]); }
    catch (Throwable $e) { http_response_code(503); echo json_encode(['status'=>'error']); }
    exit;
}
// Version endpoint, handy to confirm which image is running after a deploy.
if ($action === 'version') {
    header('Content-Type: application/json');
    echo json_encode(['version'=>$APP_VERSION, 'php'=>PHP_VERSION, 'host'=>gethostname()]);
    exit;
}
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title !== '') { $s = db()->prepare('INSERT INTO todos (title) VALUES (?)'); $s->execute([mb_substr($title,0,200)]); }
    header('Location: /'); exit;
}
if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $s = db()->prepare('UPDATE todos SET done = 1 - done WHERE id = ?'); $s->execute([$id]);
    header('Location: /'); exit;
}

$todos = db()->query('SELECT id,title,done FROM todos ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Todo</title></head>
<body style="font-family:sans-serif;max-width:560px;margin:40px auto">
<h1>Todo list</h1>
<form method="post" action="/?action=add">
  <input name="title" maxlength="200" required placeholder="What needs doing?"><button>Add</button>
</form>
<ul>
<?php foreach ($todos as $t): ?>
  <li>
    <form method="post" action="/?action=toggle" style="display:inline">
      <input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button><?= $t['done']?'✅':'⬜' ?></button>
    </form>
    <?php if ($t['done']): ?><s><?= h($t['title']) ?></s><?php else: ?><?= h($t['title']) ?><?php endif; ?>
  </li>
<?php endforeach; ?>
<?php if (!$todos): ?><li>Nothing yet.</li><?php endif; ?>
</ul>
<p style="color:#777;font-size:13px">Version <?= h($APP_VERSION) ?> · <?= h(gethostname() ?: '') ?></p>
</body></html>
