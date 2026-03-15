<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$mainDbFile = __DIR__ . "/main.db";
$txid = $_GET['txid'] ?? '';

if ($txid === '') { die("Missing txid."); }

try {
    $db = new SQLite3($mainDbFile);

    $chain = [];
    $current = $txid;

    // --- walk backwards through prev_txid ---
    while ($current !== '' && $current !== null) {

        $stmt = $db->prepare("SELECT * FROM transactions WHERE txid = :txid LIMIT 1");
        $stmt->bindValue(':txid', $current, SQLITE3_INTEGER);

        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        if (!$row) {
            break;
        }

        $chain[] = $row;

        // next step in chain
        $current = $row['prev_txid'];
    }

} catch (Exception $e) {
    die("DB error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>TX Chain</title>

<link rel="stylesheet" href="css/bbr.css">

<style>

table {
    border-collapse: collapse;
    width: 100%;
    font-family: monospace;
}

th, td {
    border: 1px solid #333;
    padding: 6px 8px;
    text-align: left;
}

th {
    background: #111;
    color: #0ff;
}

tr:nth-child(even) {
    background: #111;
}

a {
    color: #0af;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

</style>

</head>
<body>

<h1 class="digip">TX Chain | Start TXID <?= htmlspecialchars($txid) ?></h1>

<table>

<tr>
<th>TXID</th>
<th>PREV_TXID</th>
<th>From</th>
<th>To</th>
<th>In</th>
<th>Out</th>
<th>Change</th>
<th>Datetime</th>
</tr>

<?php foreach ($chain as $row): ?>

<tr>

<td>
<a href="show_tx.php?txid=<?= urlencode($row['txid']) ?>">
<?= htmlspecialchars($row['txid']) ?>
</a>
</td>

<td>

<?php if (!empty($row['prev_txid'])): ?>

<a href="show_tx.php?txid=<?= urlencode($row['prev_txid']) ?>">
<?= htmlspecialchars($row['prev_txid']) ?>
</a>

<?php else: ?>

coinbase

<?php endif; ?>

</td>

<td><?= htmlspecialchars($row['from_addr']) ?></td>

<td><?= htmlspecialchars($row['to_addr']) ?></td>

<td><?= (int)$row['val1'] ?></td>

<td><?= (int)$row['val2'] ?></td>

<td><?= (int)$row['val1'] - (int)$row['val2'] ?></td>

<td><?= date('ymd H:i:s', $row['utxo_time']) ?></td>

</tr>

<?php endforeach; ?>

</table>

</body>
</html>