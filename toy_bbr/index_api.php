<?php
//----------------------------------ajax------------------------
$db = new SQLite3(__DIR__ . "/main.db");

// --- Add this to your AJAX section in index.php ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean();
    $action = $_POST['action'];

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
    //$nonce = random_int(100, 99999);
    $nonce = $_POST['nonce'];


    // Vyčištění tx_root
    $tx_root = $_POST['tx_root'];
    if (stripos($tx_root, '0x') === 0) $tx_root = substr($tx_root, 2);
    $tx_root = preg_replace('/[^a-f0-9]/i', '', $tx_root);

    // Vyčištění prev_hash (který přišel z JS výpočtu)
    $prev_hash = $_POST['prev_hash'];
    if (stripos($prev_hash, '0x') === 0) $prev_hash = substr($prev_hash, 2);
    $prev_hash = preg_replace('/[^a-f0-9]/i', '', $prev_hash);

    // 1. Označit transakce jako vytěžené (vypadnou z mempoolu)
    if (!$db->exec("UPDATE transactions SET mp = 0 WHERE txid IN ($ids_list)")) {
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


if ($action === "mining") {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    ob_clean();
    header('Content-Type: application/json');

    $addr = $_POST['addr'];
    $value = 10;
    $timestamp = time();

    // --- PART 1: Transaction Creation ---
    // We set mp = 0 because it's being "mined" into a block immediately
    $db->exec("INSERT INTO transactions (txid, sig, from_addr, to_addr, val1, val2, mp, utxo_time) 
               VALUES (0, NULL, NULL, '$addr', 0, $value, 0, $timestamp)");
    
    $lastId = $db->lastInsertRowID();
    $new_txid = $lastId + 1000;

    $db->exec("UPDATE transactions SET txid = $new_txid WHERE rowid = $lastId");
    $db->exec("INSERT INTO utxo (txid, owner, value, spent) VALUES ($new_txid, '$addr', $value, 0)");

    // --- PART 2: Automatic Block Creation ---
    
    // Generate random 6-character hex hashes (simulating ASH24)
    $random_tx_root = bin2hex(random_bytes(3));   // 6 hex chars
    $random_prev_hash = bin2hex(random_bytes(3)); // 6 hex chars
    $nonce = random_int(1000, 9999);
    $tx_txt = (string)$new_txid;

    $safe_tx_txt = SQLite3::escapeString($tx_txt);
    
    $sqlBlock = "
        INSERT INTO blockchain (prev_hash, tx_root, nonce, timestamp, tx_txt, note_block, k)
        VALUES ('$random_prev_hash', '$random_tx_root', $nonce, $timestamp, '$safe_tx_txt', 'Auto Coinbase', '')
    ";

    if (!$db->exec($sqlBlock)) {
        echo json_encode(['status' => 'error', 'msg' => 'Block insert failed']);
        exit;
    }

    $new_block_id = $db->lastInsertRowID();

    // --- PART 3: Session Delay Logic ---
    if (!isset($_SESSION['minerdelay'])) {
        $_SESSION['minerdelay'] = 5;
    }
    $_SESSION['minerdelay'] = intval($_SESSION['minerdelay']) * 2;
    session_write_close(); 

    // Return complete data to Frontend
    echo json_encode([
        "status" => "success",
        "txid" => $new_txid,
        "block_id" => $new_block_id,
        "tx_root" => $random_tx_root,
        "prev_hash" => $random_prev_hash,
        "new_delay" => $_SESSION['minerdelay'],
        "msg" => "Transaction $new_txid created and sealed in Block #$new_block_id"
    ]);
    exit;
}

//-----------------------------------------------------------
if ($action === "mining_ok") {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    ob_clean();
    header('Content-Type: application/json');

    $addr = $_POST['addr'];
    $value = 10;
    $utxo_time = time();

    // 1. Database operations
    $db->exec("INSERT INTO transactions (txid, sig, from_addr, to_addr, val1, val2, mp, utxo_time) 
               VALUES (0, NULL, NULL, '$addr', 0, $value, 1, $utxo_time)");
    
    $lastId = $db->lastInsertRowID();
    $new_txid = $lastId + 1000;

    $db->exec("UPDATE transactions SET txid = $new_txid WHERE rowid = $lastId");
    $db->exec("INSERT INTO utxo (txid, owner, value, spent) VALUES ($new_txid, '$addr', $value, 0)");

    // 2. DOUBLE THE DELAY
    if (!isset($_SESSION['minerdelay'])) {
        $_SESSION['minerdelay'] = 5;
    }
    
    $_SESSION['minerdelay'] = intval($_SESSION['minerdelay']) * 2;
    
    // Safety check: ensure session is written
    session_write_close(); 

    echo json_encode([
        "status" => "success",
        "txid" => $new_txid,
        "new_delay" => $_SESSION['minerdelay'],
        "msg" => "Mining successful! Delay updated."
    ]);
    exit;
}

//-------------------------
if ($action === "miningX" || $action === "coinbaseX") {
    header('Content-Type: application/json'); // Nastavíme hlavičku pro JSON
    $addr = $_POST['addr'];
    $value = 10;
    $utxo_time = time();

    // 1. Vložit transakci
    $db->exec("INSERT INTO transactions (txid,sig,from_addr,to_addr,val1,val2,mp,utxo_time) VALUES (0,NULL,NULL,'$addr',0,$value,1,$utxo_time)");
    $lastId = $db->lastInsertRowID();
    $new_txid = $lastId + 1000;
    
    // 2. Update txid a vložení UTXO
    $db->exec("UPDATE transactions SET txid = $new_txid WHERE rowid = $lastId");
    $db->exec("INSERT INTO utxo (txid,owner,value,spent) VALUES ($new_txid,'$addr',$value,0)");

    // Vrátíme strukturovaná data
    echo json_encode([
        "status" => "success",
        "txid" => $new_txid,
        "msg" => "Mining successful! You earned 10 coins.",
        "addr" => $addr
    ]);
    exit;
}


//-------------------send-----------------------

    if ($action === "send") {
    $from = $_POST['from']; 
    $to = $_POST['to'];
    $val1 = intval($_POST['val1']); 
    $val2 = intval($_POST['val2']);
    //$r = $_POST['r']; 
    //$s = $_POST['s'];
    $sig = $_POST['sig_hex'];
    $utxo_id = intval($_POST['utxo_id']);
    $utxo_txid = intval($_POST['utxo_txid']); // <--- nově

    if ($val2 > $val1) { 
        echo "Error: Insufficient funds in selected UTXO."; 
        exit; 
    }

    $db->exec("INSERT INTO transactions (txid, prev_txid, sig, from_addr, to_addr, val1, val2, mp, utxo_time) 
               VALUES (0, $utxo_txid, '$sig', '$from', '$to', $val1, $val2, 1, ".time().")");
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
