<?php
// endpoints:
//api/get_last_block
//api/get_balance
//api/get_tx
//api/send_tx

//$db = new SQLite3(__DIR__ . "/main.db");

$route = $_GET['route'] ?? null;

if ($route) {

    header("Content-Type: application/json");

    $parts = explode("/", trim($route, "/"));

    $endpoint = $parts[0] ?? null;
    $param1  = $parts[1] ?? null;


    //---------------------------------------
    // get_last_block
    //---------------------------------------
    if ($endpoint === "get_last_block") {

        $res = $db->query("
            SELECT id_block, timestamp, tx_root
            FROM blockchain
            ORDER BY id_block DESC
            LIMIT 1
        ");

        $row = $res ? $res->fetchArray(SQLITE3_ASSOC) : false;

        if (!$row) {
            echo json_encode([
                "status" => "error",
                "msg" => "No blocks"
            ]);
            exit;
        }

        echo json_encode([
            "status" => "ok",
            "block" => $row
        ]);

        exit;
    }


    //---------------------------------------
    // get_block /api/get_block/10
    //---------------------------------------
    if ($endpoint === "get_block" && $param1) {

        $stmt = $db->prepare("
            SELECT *
            FROM blockchain
            WHERE id_block = :id
            LIMIT 1
        ");

        $stmt->bindValue(":id", intval($param1));

        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);

        echo json_encode([
            "status" => $row ? "ok" : "error",
            "block" => $row
        ]);

        exit;
    }


    //---------------------------------------
    // get_blocks
    //---------------------------------------
    if ($endpoint === "get_blocks") {

        $res = $db->query("
            SELECT id_block, timestamp
            FROM blockchain
            ORDER BY id_block DESC
            LIMIT 20
        ");

        $arr = [];

        while ($r = $res->fetchArray(SQLITE3_ASSOC)) {
            $arr[] = $r;
        }

        echo json_encode([
            "status" => "ok",
            "blocks" => $arr
        ]);

        exit;
    }

//---------------------------------------
// get_tx /api/get_tx_raw/123
//---------------------------------------
if ($endpoint === "get_tx_raw" && $param1) {

    $stmt = $db->prepare("
        SELECT
            txid,
            from_addr,
            prev_txid,
            to_addr,
            val1,
            val2,
            sig,
            utxo_time
        FROM transactions
        WHERE txid = :txid
        LIMIT 1
    ");

    $stmt->bindValue(":txid", intval($param1), SQLITE3_INTEGER);

    $res = $stmt->execute();
    $row = $res->fetchArray(SQLITE3_ASSOC);

    if (!$row) {

        echo json_encode([
            "status" => "error",
            "msg" => "tx not found"
        ]);

        exit;
    }

    echo json_encode([
        "status" => "ok",

        "TXID" => $row["txid"],
        "From" => $row["from_addr"],
        "Prev_txid" => $row["prev_txid"],
        "To" => $row["to_addr"],

        "Val1" => $row["val1"],
        "Val2" => $row["val2"],

        "Signature" => $row["sig"],

        "date_time" => date(
            "Y-m-d H:i:s",
            intval($row["utxo_time"])
        )
    ]);

    exit;
}


//---------------------------------------
// get_tx /api/get_tx/1230
//---------------------------------------
if ($endpoint === "get_tx" && $param1) {

    $stmt = $db->prepare("
        SELECT
            txid,
            from_addr,
            prev_txid,
            to_addr,
            val1,
            val2,
            sig,
            utxo_time
        FROM transactions
        WHERE txid = :txid
        LIMIT 1
    ");

    $stmt->bindValue(":txid", intval($param1), SQLITE3_INTEGER);

    $res = $stmt->execute();
    $row = $res->fetchArray(SQLITE3_ASSOC);

    if (!$row) {

        echo json_encode([
            "status" => "error",
            "msg" => "tx not found"
        ]);
        exit;
    }

    // type
    $type = empty($row["prev_txid"]) ? "coinbase" : "p2pk";

    $tx = [

        "status" => "ok",

        "type" => $type,

        "txid" => intval($row["txid"]),

        "vin" => [
            [
                "txid" => intval($row["prev_txid"]),
                "value" => intval($row["val1"]),

                "scriptSig" => [
                    "sig" => $row["sig"],
                    "pub" => $row["from_addr"]
                ]
            ]
        ],

        "vout" => [
            [
                "value" => intval($row["val2"]),

                "scriptPubKey" => [
                    "pub" => $row["to_addr"],
                    "op" => "OP_CHECKSIG"
                ]
            ]
        ],

        "date_time" => date(
            "Y-m-d H:i:s",
            intval($row["utxo_time"])
        )
    ];

    echo json_encode($tx, JSON_PRETTY_PRINT);

    exit;
}


//---------------------------------------
// get_balance /api/get_balance/adresa
//---------------------------------------
if ($endpoint === "get_balance" && $param1) {

    // Sečteme hodnotu všech UTXO, které patří dané adrese a nejsou utracené
    $stmt = $db->prepare("
        SELECT SUM(value) as total 
        FROM utxo 
        WHERE owner = :addr AND spent = 0
    ");

    $stmt->bindValue(":addr", $param1, SQLITE3_TEXT);
    $res = $stmt->execute();
    $row = $res->fetchArray(SQLITE3_ASSOC);

    $balance = $row['total'] ?? 0;

    // Volitelně můžeme vytáhnout i seznam konkrétních UTXO id
    $stmt_list = $db->prepare("
        SELECT txid, value 
        FROM utxo 
        WHERE owner = :addr AND spent = 0
    ");
    $stmt_list->bindValue(":addr", $param1, SQLITE3_TEXT);
    $res_list = $stmt_list->execute();
    
    $utxos = [];
    while ($u = $res_list->fetchArray(SQLITE3_ASSOC)) {
        $utxos[] = [
            "txid" => $u['txid'],
            "value" => intval($u['value'])
        ];
    }

    echo json_encode([
        "status" => "ok",
        "address" => $param1,
        "balance" => intval($balance),
        "utxo_count" => count($utxos),
        "unspent_outputs" => $utxos
    ], JSON_PRETTY_PRINT);

    exit;
}


//---------------------------------------
// send_transaction
//---------------------------------------
if ($endpoint === "send_transaction") {
    // Načtení dat (podpora pro JSON i klasický POST)
    $input = json_decode(file_get_contents('php://input'), true);
    $data = $input ? $input : $_POST;

    $from      = $data['from'];
    $to        = $data['to'];
    $val1      = intval($data['val1']); // Hodnota v UTXO (celková)
    $val2      = intval($data['val2']); // Kolik posílám
    $sig       = $data['sig_hex'];
    $utxo_txid = intval($data['utxo_txid']); // ID transakce, ze které beru

    // Základní validace
    if (empty($from) || empty($to) || empty($sig) || $utxo_txid <= 0) {
        echo json_encode(["status" => "error", "message" => "Missing required fields"]);
        exit;
    }

    if ($val2 > $val1) {
        echo json_encode(["status" => "error", "message" => "Insufficient funds in selected UTXO"]);
        exit;
    }

    try {
        // 1. Vložení transakce
        $db->exec("INSERT INTO transactions (txid, prev_txid, sig, from_addr, to_addr, val1, val2, mp, utxo_time) 
                   VALUES (0, $utxo_txid, '$sig', '$from', '$to', $val1, $val2, 1, ".time().")");
        
        $lastId = $db->lastInsertRowID();
        $new_txid = $lastId + 1000;
        $db->exec("UPDATE transactions SET txid = $new_txid WHERE rowid = $lastId");

        // 2. Mark staré UTXO jako utracené
        // Předpokládáme, že hledáme UTXO podle majitele a txid
        $db->exec("UPDATE utxo SET spent=1 WHERE owner='$from' AND txid=$utxo_txid");

        // 3. Vytvoření nového UTXO pro příjemce
        $db->exec("INSERT INTO utxo (txid, owner, value, spent) VALUES ($new_txid, '$to', $val2, 0)");

        // 4. Výpočet a vytvoření UTXO pro vrácení (change)
        $change = $val1 - $val2;
        if ($change > 0) {
            $db->exec("INSERT INTO utxo (txid, owner, value, spent) VALUES ($new_txid, '$from', $change, 0)");
        }

        echo json_encode([
            "status" => "ok",
            "message" => "Transaction broadcasted successfully",
            "txid" => $new_txid,
            "details" => [
                "sent" => $val2,
                "change" => $change,
                "signature" => substr($sig, 0, 8) . "..."
            ]
        ]);

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
    exit;
}




    //---------------------------------------
    // unknown
    //---------------------------------------

    echo json_encode([
        "status" => "error",
        "msg" => "Unknown endpoint"
    ]);

    exit;
}