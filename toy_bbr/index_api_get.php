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
    // unknown
    //---------------------------------------

    echo json_encode([
        "status" => "error",
        "msg" => "Unknown endpoint"
    ]);

    exit;
}