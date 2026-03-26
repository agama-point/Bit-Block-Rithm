<?php

$dbFile = __DIR__ . "/pristupy.db";

try {

    $db = new SQLite3($dbFile);

    $res = $db->query("
        SELECT uid, ip, note, ver, x1, created_at
        FROM logs
        ORDER BY created_at DESC
        LIMIT 200
    ");

} catch (Exception $e) {
    die("DB error");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Access logs</title>

<style>

body{
    background:#111;
    color:#ddd;
    font-family:monospace;
}

table{
    border-collapse:collapse;
    margin:auto;
}

td,th{
    border:1px solid #444;
    padding:6px 10px;
}

th{
    background:#222;
}

tr:nth-child(even){
    background:#181818;
}

</style>

</head>
<body>

<h2 style="text-align:center">Access log (200)</h2>

<table>

<tr>
<th>time</th>
<th>ip</th>
<th>note</th>
<th>ver</th>
<th>x1</th>
<th>uid</th>
</tr>

<?php

while ($row = $res->fetchArray(SQLITE3_ASSOC)) {

    $t = date("Y-m-d H:i:s", $row["created_at"]);

    echo "<tr>";

    echo "<td>$t</td>";
    echo "<td>{$row["ip"]}</td>";
    echo "<td>{$row["note"]}</td>";
    echo "<td>{$row["ver"]}</td>";
    echo "<td>{$row["x1"]}</td>";
    echo "<td>{$row["uid"]}</td>";

    echo "</tr>";
}

?>

</table>

</body>
</html>