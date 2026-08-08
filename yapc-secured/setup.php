<?php
declare(strict_types=1);

require __DIR__ . '/src/db.php';
require __DIR__ . '/src/security.php';

$keyFile = __DIR__ . '/storage/encryption.key';
if (!is_file($keyFile)) {
    file_put_contents($keyFile, random_bytes(32), LOCK_EX);
    chmod($keyFile, 0600);
}

$db = db();
$db->exec("DROP VIEW IF EXISTS public_pastes");
$db->exec("DROP TABLE IF EXISTS audit_log");
$db->exec("DROP TABLE IF EXISTS pastes");
$db->exec("DROP TABLE IF EXISTS users");

$db->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    created_at TEXT NOT NULL
)");

$db->exec("CREATE TABLE pastes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    is_private INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    FOREIGN KEY(user_id) REFERENCES users(id)
)");

$db->exec("CREATE TABLE audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    paste_id INTEGER,
    user_id INTEGER,
    action TEXT NOT NULL,
    occurred_at TEXT NOT NULL
)");

$db->exec("CREATE TRIGGER audit_pastes_insert
AFTER INSERT ON pastes
BEGIN
    INSERT INTO audit_log(paste_id,user_id,action,occurred_at)
    VALUES(NEW.id,NEW.user_id,'INSERT',datetime('now'));
END");

$db->exec("CREATE TRIGGER audit_pastes_update
AFTER UPDATE ON pastes
BEGIN
    INSERT INTO audit_log(paste_id,user_id,action,occurred_at)
    VALUES(NEW.id,NEW.user_id,'UPDATE',datetime('now'));
END");

$db->exec("CREATE TRIGGER audit_pastes_delete
AFTER DELETE ON pastes
BEGIN
    INSERT INTO audit_log(paste_id,user_id,action,occurred_at)
    VALUES(OLD.id,OLD.user_id,'DELETE',datetime('now'));
END");

$db->exec("CREATE VIEW public_pastes AS
    SELECT id,user_id,title,content,is_private,created_at
    FROM pastes
    WHERE is_private = 0");

$now = date('Y-m-d H:i:s');
$stmt = $db->prepare("INSERT INTO users(username,password_hash,created_at) VALUES(?,?,?)");
$stmt->execute(['admin', password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]), $now]);
$stmt->execute(['alice', password_hash('password', PASSWORD_BCRYPT, ['cost' => 12]), $now]);

$stmt = $db->prepare("INSERT INTO pastes(user_id,title,content,is_private,created_at) VALUES(?,?,?,?,?)");
$stmt->execute([1, 'Welcome', 'This is the secured YAPC demo.', 0, $now]);
$stmt->execute([2, 'Private example', encrypt_paste('This content is encrypted at rest.', encryption_key()), 1, $now]);

echo "Secured YAPC database initialized.\n";
