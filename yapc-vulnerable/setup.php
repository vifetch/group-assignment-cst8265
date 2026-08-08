<?php
require __DIR__ . '/src/db.php';
$db = db();

$db->exec("DROP TABLE IF EXISTS audit_log");
$db->exec("DROP TABLE IF EXISTS pastes");
$db->exec("DROP TABLE IF EXISTS users");

$db->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
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

$now = date('Y-m-d H:i:s');
$stmt = $db->prepare("INSERT INTO users(username,password,created_at) VALUES(?,?,?)");
$stmt->execute(['admin', 'admin123', $now]);
$stmt->execute(['alice', 'password', $now]);

$stmt = $db->prepare("INSERT INTO pastes(user_id,title,content,is_private,created_at) VALUES(?,?,?,?,?)");
$stmt->execute([1, 'Welcome', 'This is the vulnerable YAPC demo.', 0, $now]);
$stmt->execute([2, 'Example', 'Try the controlled XSS demonstration in the assignment.', 0, $now]);

echo "Vulnerable YAPC database initialized.\n";
