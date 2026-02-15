<?php
//----------------------------------ajax------------------------
$db = new SQLite3(__DIR__ . "/main.db");

// --- Add this to your AJAX section in index.php ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean();
    $action = $_POST['action'];

    if ($action === "coinbase") {
        $addr = $_POST['addr'];
        $value = 10;
        $utxo_time = time();
        $db->exec("INSERT INTO transactions (txid, sig, from_addr, to_addr, val1, val2, mp, utxo_time) 
                   VALUES (0, NULL, NULL, '$addr', 0, $value, 0, $utxo_time)");
        $lastId = $db->lastInsertRowID();
        $new_txid = $lastId + 1000;
        $db->exec("UPDATE transactions SET txid = $new_txid WHERE rowid = $lastId");
        $db->exec("INSERT INTO utxo (txid, owner, value, spent) VALUES ($new_txid, '$addr', $value, 0)");
        echo "Success: 10 coins mined (TXID: $new_txid) for $addr";
        exit;
    }

    if ($action === "get_utxo") {
        header('Content-Type: application/json');
        $addr = $_POST['addr'];
        $res = $db->query("SELECT * FROM utxo WHERE owner='$addr' AND spent=0 LIMIT 1");
        $row = $res->fetchArray(SQLITE3_ASSOC);
        echo json_encode($row ?: ["error" => "No available UTXO found"]);
        exit;
    }

// --------- Get Last Block ----------
if ($action === "get_last_blockX") {
    header('Content-Type: application/json');

    $res = $db->query("
        SELECT id_block, timestamp, tx_root 
        FROM blockchain 
        ORDER BY id_block DESC 
        LIMIT 1
    ");

    $row = $res ? $res->fetchArray(SQLITE3_ASSOC) : false;

    if (!$row) {
        echo json_encode([
            'status' => 'error',
            'msg' => 'No blocks found'
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'ok',
        'id_block' => $row['id_block'],
        'timestamp' => $row['timestamp'],
        'tx_root' => $row['tx_root']
    ]);
    exit;
}


// --------- Mining ----------
if ($action === "mine_tx") {
    header('Content-Type: application/json');

    // Základní validace
    if (!isset($_POST['tx_ids']) || !is_array($_POST['tx_ids'])) {
        echo json_encode(['status'=>'error','msg'=>'No tx_ids provided']);
        exit;
    }

    if (!isset($_POST['tx_root']) || !isset($_POST['prev_hash'])) {
        echo json_encode(['status'=>'error','msg'=>'Missing hash data (tx_root or prev_hash)']);
        exit;
    }

    $ids = array_map('intval', $_POST['tx_ids']);
    if(count($ids) === 0){
        echo json_encode(['status'=>'error','msg'=>'Empty tx_ids']);
        exit;
    }

    $ids_list = implode(',', $ids);
    $timestamp = time();
    $tx_txt = $ids_list; // Seznam ID transakcí v bloku

    // Nonce pro budoucí Proof of Work (zatím náhodný)
    $nonce = random_int(100, 9999);

    // Vyčištění tx_root
    $tx_root = $_POST['tx_root'];
    if (stripos($tx_root, '0x') === 0) $tx_root = substr($tx_root, 2);
    $tx_root = preg_replace('/[^a-f0-9]/i', '', $tx_root);

    // Vyčištění prev_hash (který přišel z JS výpočtu)
    $prev_hash = $_POST['prev_hash'];
    if (stripos($prev_hash, '0x') === 0) $prev_hash = substr($prev_hash, 2);
    $prev_hash = preg_replace('/[^a-f0-9]/i', '', $prev_hash);

    // 1. Označit transakce jako vytěžené (vypadnou z mempoolu)
    if (!$db->exec("UPDATE transactions SET mp = 0 WHERE id IN ($ids_list)")) {
        echo json_encode(['status'=>'error','msg'=>'UPDATE failed: '.$db->lastErrorMsg()]);
        exit;
    }

    // 2. Vložit nový blok do blockchainu s propojením na předchozí
    // Používáme SQLite3::escapeString pro textová pole, aby byl kód bezpečný
    $safe_tx_txt = SQLite3::escapeString($tx_txt);
    
    $sql = "
        INSERT INTO blockchain (prev_hash, tx_root, nonce, timestamp, tx_txt, note_block, k)
        VALUES ('$prev_hash', '$tx_root', $nonce, $timestamp, '$safe_tx_txt', '', '')
    ";

    if (!$db->exec($sql)) {
        // Pokud insert selže, měli bychom teoreticky vrátit mp = 1, ale pro jednoduchost:
        echo json_encode(['status'=>'error','msg'=>'INSERT failed: '.$db->lastErrorMsg()]);
        exit;
    }

    echo json_encode([
        'status'=>'ok',
        'updated'=>count($ids),
        'tx_txt'=>$tx_txt,
        'tx_root'=>$tx_root,
        'prev_hash'=>$prev_hash,
        'nonce'=>$nonce
    ]);
    exit;
}

//-------------------send-----------------------

    if ($action === "send") {
    $from = $_POST['from']; 
    $to = $_POST['to'];
    $val1 = intval($_POST['val1']); 
    $val2 = intval($_POST['val2']);
    $r = $_POST['r']; 
    $s = $_POST['s'];
    $utxo_id = intval($_POST['utxo_id']);
    $utxo_txid = intval($_POST['utxo_txid']); // <--- nově

    if ($val2 > $val1) { 
        echo "Error: Insufficient funds in selected UTXO."; 
        exit; 
    }

    $db->exec("INSERT INTO transactions (txid, prev_txid, sig, from_addr, to_addr, val1, val2, mp, utxo_time) 
               VALUES (0, $utxo_txid, '$r,$s', '$from', '$to', $val1, $val2, 1, ".time().")");
    $lastId = $db->lastInsertRowID();
    $new_txid = $lastId + 1000;
    $db->exec("UPDATE transactions SET txid = $new_txid WHERE rowid = $lastId");

    $db->exec("UPDATE utxo SET spent=1 WHERE id=$utxo_id");
    $db->exec("INSERT INTO utxo (txid, owner, value, spent) VALUES ($new_txid, '$to', $val2, 0)");

    $change = $val1 - $val2;
    if ($change > 0) $db->exec("INSERT INTO utxo (txid, owner, value, spent) VALUES ($new_txid, '$from', $change, 0)");

    echo "Transaction broadcasted! TXID: $new_txid. Sent: $val2, Change: $change";
    exit;
}

}
//--------------------------------------/ajax-------------------------

$nick = $_SESSION['nick'] ?? null;
$k1 = $_SESSION['k1'] ?? null;
$mode = $_SESSION['mode'] ?? null;
$net = $_SESSION['net'] ?? null;
$minerdelay = $_SESSION['minerdelay'] ?? null;
