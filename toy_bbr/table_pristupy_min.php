<?php
$dbFile = __DIR__ . "/pristupy.db";

try {
    $db = new SQLite3($dbFile);
    $res = $db->query("
        SELECT uid, ip, note, ver, x1, created_at
        FROM logs
        ORDER BY created_at DESC
        LIMIT 10
    ");

} catch (Exception $e) { die("DB error");}
?>

<h3 class="digip">Access log (10)</h3>

<table class="tab">
<tr>
<th>time</th>
<th>nick</th>
<th>note</th>
</tr>

<?php
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {

    $t = date("Y-m-d H:i:s", $row["created_at"]);
    echo "<tr>";
    echo "<td>$t</td>";
    echo "<td>{$row["note"]}</td>";
    echo "<td>{$row["ver"]}</td>";

    echo "</tr>";
}
?>
</table>

