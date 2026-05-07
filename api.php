<?php
header('Content-Type: application/json');

$db_file = __DIR__ . '/boot_data.db';
$db = new SQLite3($db_file);

$db->exec("CREATE TABLE IF NOT EXISTS boot_stats (id INTEGER PRIMARY KEY, total_count INTEGER)");
$row = $db->querySingle("SELECT total_count FROM boot_stats WHERE id = 1");

if ($row === null) {
    $db->exec("INSERT INTO boot_stats (id, total_count) VALUES (1, 0)");
    $count = 0;
} else {
    $count = $row;
}

if (isset($_GET['action']) && $_GET['action'] === 'add') {
    $count++;
    $stmt = $db->prepare("UPDATE boot_stats SET total_count = :count WHERE id = 1");
    $stmt->bindValue(':count', $count, SQLITE3_INTEGER);
    $stmt->execute();
}

echo json_encode(['count' => $count]);
$db->close();
?>