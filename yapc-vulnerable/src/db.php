<?php
function db(): PDO {
    static $db;
    if ($db) return $db;
    $path = __DIR__ . '/../storage/yapc.sqlite';
    $db = new PDO('sqlite:' . $path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}
