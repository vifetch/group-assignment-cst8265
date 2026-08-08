<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$db = db();
$message = '';
$error = '';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
    header('Location: /');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }

    if (($_POST['action'] ?? '') === 'login') {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['last_activity'] = time();
            header('Location: /');
            exit;
        }
        $error = 'Invalid username or password.';
    }

    if (($_POST['action'] ?? '') === 'create') {
        require_login();

        $title = trim((string)($_POST['title'] ?? ''));
        $content = (string)($_POST['content'] ?? '');
        $private = isset($_POST['private']) ? 1 : 0;

        if ($title === '' || $content === '') {
            $error = 'Title and content are required.';
        } else {
            $storedContent = $private
                ? encrypt_paste($content, encryption_key())
                : $content;

            $stmt = $db->prepare(
                "INSERT INTO pastes(user_id,title,content,is_private,created_at)
                 VALUES(?,?,?,?,datetime('now'))"
            );
            $stmt->execute([$_SESSION['user_id'], $title, $storedContent, $private]);
            $message = 'Paste created.';
        }
    }
}

$stmt = $db->prepare(
    "SELECT pastes.*, users.username
     FROM public_pastes AS pastes
     JOIN users ON users.id = pastes.user_id
     ORDER BY pastes.id DESC"
);
$stmt->execute();
$publicPastes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$userPastes = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT * FROM pastes WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $userPastes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html>
<head>
<link rel="stylesheet" href="/style.css">
<meta charset="utf-8">
<meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'none'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'">
<title>YAPC Secured</title>
</head>
<body>
<h1>YAPC — Secured Application</h1>
<p class="small">This copy implements the security controls described in the CST8265 proposal.</p>

<?php if ($message): ?><p><?= e($message) ?></p><?php endif; ?>
<?php if ($error): ?><p class="danger"><?= e($error) ?></p><?php endif; ?>

<section>
<?php if (isset($_SESSION['user_id'])): ?>
    <div class="nav">
        <strong>Logged in as <?= e($_SESSION['username']) ?></strong>
        <a href="/?logout=1">Log out</a>
    </div>
<?php else: ?>
    <h2>Login</h2>
    <form method="post">
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label>Username</label><input name="username" autocomplete="username">
        <label>Password</label><input name="password" type="password" autocomplete="current-password">
        <button>Login</button>
    </form>
    <p class="small">Demo accounts: admin / admin123 and alice / password</p>
<?php endif; ?>
</section>

<?php if (isset($_SESSION['user_id'])): ?>
<section>
<h2>Create Paste</h2>
<form method="post">
    <input type="hidden" name="action" value="create">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>Title</label><input name="title" required>
    <label>Content</label><textarea name="content" rows="8" required></textarea>
    <label><input type="checkbox" name="private"> Private (encrypted at rest)</label>
    <button>Create</button>
</form>
</section>
<?php endif; ?>

<section>
<h2>Public Pastes</h2>
<?php foreach ($publicPastes as $paste): ?>
<div class="paste">
    <h3><?= e($paste['title']) ?></h3>
    <pre><?= e($paste['content']) ?></pre>
    <p class="small">By <?= e($paste['username']) ?> at <?= e($paste['created_at']) ?></p>
</div>
<?php endforeach; ?>
</section>

<?php if ($userPastes): ?>
<section>
<h2>Your Pastes</h2>
<?php foreach ($userPastes as $paste):
    $content = $paste['is_private'] ? decrypt_paste($paste['content'], encryption_key()) : $paste['content'];
?>
<div class="paste">
    <h3><?= e($paste['title']) ?></h3>
    <pre><?= e($content) ?></pre>
</div>
<?php endforeach; ?>
</section>
<?php endif; ?>
</body>
</html>
