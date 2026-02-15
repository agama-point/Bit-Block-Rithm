<?php
$db = new SQLite3(__DIR__ . "/main.db");

echo "<h3>Database Overview: main.db</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Table Name</th><th>Number of Records</th><th>Last Modified (if available)</th></tr>";

// 1. Seznam všech tabulek
$tablesRes = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

while($tbl = $tablesRes->fetchArray(SQLITE3_ASSOC)){
    $tableName = $tbl['name'];
    
    // 2. Počet záznamů
    $countRes = $db->query("SELECT COUNT(*) as cnt FROM $tableName");
    $countRow = $countRes->fetchArray(SQLITE3_ASSOC);
    $numRecords = $countRow['cnt'] ?? 0;
    
    // 3. Poslední modifikace (pokud existuje sloupec typu timestamp/utxo_time/created_at)
    $lastMod = "N/A";
    $columnsRes = $db->query("PRAGMA table_info($tableName)");
    $timestampCol = null;
    while($col = $columnsRes->fetchArray(SQLITE3_ASSOC)){
        $name = $col['name'];
        if(in_array($name, ['utxo_time','created_at','updated_at','time'])){
            $timestampCol = $name;
            break;
        }
    }
    if($timestampCol){
        $resTime = $db->query("SELECT MAX($timestampCol) as last_mod FROM $tableName");
        $rowTime = $resTime->fetchArray(SQLITE3_ASSOC);
        if(!empty($rowTime['last_mod'])){
            $lastMod = date('Y-m-d H:i:s', $rowTime['last_mod']);
        }
    }

    echo "<tr><td>$tableName</td><td>$numRecords</td><td>$lastMod</td></tr>";
}

echo "</table>";
