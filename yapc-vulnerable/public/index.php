<?php
session_start();
require __DIR__ . '/../src/db.php';

$db = db();
$message = '';
$error = '';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'login') {
        // INTENTIONALLY VULNERABLE: raw SQL interpolation and plaintext passwords.
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password' LIMIT 1";
        try {
            $user = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $error = 'Database error: ' . $e->getMessage();
            $user = false;
        }
        if ($user) {
            // INTENTIONALLY VULNERABLE: no session ID regeneration.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: /');
            exit;
        }
        $error = $error ?: 'Invalid username or password.';
    }

    if (($_POST['action'] ?? '') === 'create') {
    if (!isset($_SESSION['user_id'])) {
        $error = 'Log in first.';
    } else {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $private = isset($_POST['private']) ? 1 : 0;

        // The vulnerable application deliberately uses a prepared statement
        // here so arbitrary paste text can be stored.
        //
        // The XSS vulnerability occurs when the stored content is later
        // rendered without HTML encoding.
        $stmt = $db->prepare(
            "INSERT INTO pastes (user_id, title, content, is_private, created_at)
             VALUES (?, ?, ?, ?, datetime('now'))"
        );

        $stmt->execute([
            (int)$_SESSION['user_id'],
            $title,
            $content,
            $private
        ]);

        $message = 'Paste created.';
    }

}
}

$publicPastes = $db->query(
    "SELECT pastes.*, users.username FROM pastes JOIN users ON users.id = pastes.user_id
     WHERE is_private = 0 ORDER BY pastes.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$userPastes = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $db->query("SELECT * FROM pastes WHERE user_id = " . (int)$_SESSION['user_id'] . " ORDER BY id DESC");
    $userPastes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>YAPC Vulnerable</title>
<style>
body{font-family:Arial,sans-serif;max-width:1000px;margin:2rem auto;padding:0 1rem;background:#f5f5f5}
section{background:white;padding:1.2rem;margin:1rem 0;border-radius:8px}
input,textarea{width:100%;box-sizing:border-box;margin:.35rem 0 1rem;padding:.6rem}
button{padding:.6rem 1rem}.danger{background:#fee}.small{font-size:.9rem;color:#555}
.paste{border-top:1px solid #ddd;padding:1rem 0}.nav{display:flex;gap:1rem}
</style>
</head>
<body>
<h1>YAPC — Vulnerable Application</h1>
<p class="small">This copy intentionally contains security weaknesses for controlled classroom demonstrations.</p>

<?php if ($message): ?><p><?= $message ?></p><?php endif; ?>
<?php if ($error): ?><p class="danger"><?= $error ?></p><?php endif; ?>

<section>
<?php if (isset($_SESSION['user_id'])): ?>
    <div class="nav">
        <strong>Logged in as <?= $_SESSION['username'] ?></strong>
        <a href="/?logout=1">Log out</a>
    </div>
<?php else: ?>
    <h2>Login</h2>
    <form method="post">
        <input type="hidden" name="action" value="login">
        <label>Username</label><input name="username">
        <label>Password</label><input name="password" type="password">
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
    <label>Title</label><input name="title" required>
    <label>Content</label><textarea name="content" rows="8" required></textarea>
    <label><input type="checkbox" name="private"> Private</label>
    <button>Create</button>
</form>
</section>
<?php endif; ?>

<section>
<h2>Public Pastes</h2>
<?php foreach ($publicPastes as $paste): ?>
<div class="paste">
    <!-- INTENTIONALLY VULNERABLE: stored XSS through unescaped output. -->
    <h3><?= $paste['title'] ?></h3>
    <div><?= nl2br($paste['content']) ?></div>
    <p class="small">By <?= $paste['username'] ?> at <?= $paste['created_at'] ?></p>
</div>
<?php endforeach; ?>
</section>

<?php if ($userPastes): ?>
<section>
<h2>Your Pastes</h2>
<?php foreach ($userPastes as $paste): ?>
<div class="paste">
    <h3><?= $paste['title'] ?></h3>
    <div><?= nl2br($paste['content']) ?></div>
</div>
<?php endforeach; ?>
</section>
<?php endif; ?>
</body>
</html>
