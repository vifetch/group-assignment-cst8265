<?php
require __DIR__ . '/src/db.php';
$db = db();

$backup = __DIR__ . '/storage/backup-' . date('Ymd-His') . '.sqlite';
$db->exec("VACUUM INTO " . $db->quote($backup));
echo "Backup created: {$backup}\n";
echo "Keep the encryption.key file backed up separately and securely; without it, encrypted private pastes cannot be recovered.\n";
