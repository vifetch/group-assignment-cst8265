<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Run this script from the command line.\n");
}

$backup = $argv[1] ?? '';
if ($backup === '' || !is_file($backup)) {
    exit("Usage: php recover.php storage/backup-YYYYMMDD-HHMMSS.sqlite\n");
}

$target = __DIR__ . '/storage/yapc.sqlite';
copy($backup, $target) or exit("Recovery failed.\n");
echo "Database restored from backup. Verify that storage/encryption.key is also present.\n";
