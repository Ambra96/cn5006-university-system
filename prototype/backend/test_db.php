<!-- test if connected succesfuly to database -->
<?php
require_once __DIR__ . '/db.php';

$db = (new DatabaseConnection())->getConnection();

$stmt = $db->query("SELECT NOW() as time");
$result = $stmt->fetch();

echo "DB OK, server time: " . $result['time'];
