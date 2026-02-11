<?php
$db = new SQLite3(__DIR__ . "/main.db");
?>

<h3>Posledních 10 transakcí</h3>
<table border="1" cellpadding="6" cellspacing="0">
<tr>
    <th>ID</th>
    <th>TXID</th>
    <th>Sig</th>
    <th>From</th>
    <th>To</th>
    <th>Val1</th>
    <th>Val2</th>
    <th>MP</th>
</tr>

<?php
$res = $db->query("SELECT * FROM transactions
                   ORDER BY id DESC
                   LIMIT 10");

while($row = $res->fetchArray(SQLITE3_ASSOC)){
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>".substr($row['txid'],0,12)."</td>";
    echo "<td>{$row['sig']}</td>";
    echo "<td>{$row['from_addr']}</td>";
    echo "<td>{$row['to_addr']}</td>";
    echo "<td>{$row['val1']}</td>";
    echo "<td>{$row['val2']}</td>";
    echo "<td>{$row['mp']}</td>";
    echo "</tr>";
}
?>
</table>


<h3>Posledních 10 UTXO</h3>
<table border="1" cellpadding="6" cellspacing="0">
<tr>
    <th>ID</th>
    <th>TXID</th>
    <th>Owner</th>
    <th>Value</th>
    <th>Spent</th>
</tr>

<?php
$res = $db->query("SELECT * FROM utxo
                   ORDER BY id DESC
                   LIMIT 10");

while($row = $res->fetchArray(SQLITE3_ASSOC)){
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>".substr($row['txid'],0,12)."</td>";
    echo "<td>{$row['owner']}</td>";
    echo "<td>{$row['value']}</td>";
    echo "<td>{$row['spent']}</td>";
    echo "</tr>";
}
?>
</table>
