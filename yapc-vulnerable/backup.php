<?php
require __DIR__ . '/src/db.php';
$db = db();
$db->exec("VACUUM INTO '" . __DIR__ . "/storage/backup-" . date('Ymd-His') . ".sqlite'");
echo "Backup created in storage/.\n";
