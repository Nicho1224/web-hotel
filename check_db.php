<?php
<?php
define('INCLUDED_CONFIG', true);
require_once 'config.php';

// Get table structure
$stmt = $conn->query("SHOW CREATE TABLE transaksi");
$table = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Table Structure:</h3>";
echo "<pre>" . htmlspecialchars($table['Create Table']) . "</pre>";

// Check for existing values in potential problematic fields
$stmt = $conn->query("SELECT id_transaksi FROM transaksi WHERE id_transaksi = 0");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Entries with id_transaksi = 0:</h3>";
if ($row) {
    echo "Found entry with id_transaksi = 0";
} else {
    echo "No entries with id_transaksi = 0";
}

// Check NIK field values
$stmt = $conn->query("SELECT nik, COUNT(*) as count FROM transaksi GROUP BY nik HAVING COUNT(*) > 1");
$duplicateNiks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Duplicate NIK values:</h3>";
if (!empty($duplicateNiks)) {
    echo "<pre>" . print_r($duplicateNiks, true) . "</pre>";
} else {
    echo "No duplicate NIK values found";
}

// Check database error log
echo "<h3>Last MySQL Error:</h3>";
$stmt = $conn->query("SHOW ENGINE INNODB STATUS");
$status = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>" . htmlspecialchars(substr($status['Status'], 0, 1000)) . "...</pre>";
?>