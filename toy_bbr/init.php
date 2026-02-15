<?php
/**
 * init.php
 * Initializes SQLite database structures and seed data.
 */

$messages = [];

/* ====================================================
 * PART 1: test.db
 * ==================================================== */
$testDbFile = __DIR__ . "/test.db";

try {
    $testDb = new SQLite3($testDbFile);
    $testDb->enableExceptions(true);

    $messages[] = "Database file <b>test.db</b> is present or has just been created.";

    $testDb->exec("
        CREATE TABLE IF NOT EXISTS test (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ts INTEGER NOT NULL,
            value INTEGER NOT NULL
        )
    ");

    $messages[] = "Table <b>test</b> in test.db is ready.";

} catch (Exception $e) {
    $messages[] = "Initialization of <b>test.db</b> failed: " . $e->getMessage();
}


/* ====================================================
 * PART 2: main.db
 * ==================================================== */
$mainDbFile = __DIR__ . "/main.db";

try {
    $mainDb = new SQLite3($mainDbFile);
    $mainDb->enableExceptions(true);

    $messages[] = "Database file <b>main.db</b> is present or has just been created.";

    /* ---- create users table ---- */
    $mainDb->exec("
        CREATE TABLE IF NOT EXISTS users (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            nick TEXT NOT NULL,
            psw  TEXT NOT NULL,
            k1   INTEGER DEFAULT 0,
            k2   INTEGER DEFAULT 0,
            k3   INTEGER,
            note TEXT,
            k5   INTEGER,
            k6   TEXT
        )
    ");

    $messages[] = "Table <b>users</b> in main.db is ready.";

    /* ---- create transactions table ---- ?txid  INTEGER NOT NULL UNIQUE,*/
    $mainDb->exec("
    CREATE TABLE IF NOT EXISTS transactions (
      id         INTEGER PRIMARY KEY AUTOINCREMENT,
      txid       INTEGER NOT NULL,
      sig        TEXT,
      from_addr  TEXT,
      prev_txid  INTEGER, 
      to_addr    TEXT NOT NULL,
      val1       INTEGER NOT NULL,
      val2       INTEGER NOT NULL,
      mp         INTEGER NOT NULL,
      utxo_time  INTEGER
      )
    ");

    $messages[] = "Table <b>transactions</b> in main.db is ready.";

    /* ---- seed users ---- */
    $seedUsers = [
        ["Alice", "alice11"],
        ["Bob",   "bob22"],
    ];

    $stmt = $mainDb->prepare("
        INSERT OR IGNORE INTO users (nick, psw, k1, k2)
        VALUES (:nick, :psw, 0, 0)
    ");

    foreach ($seedUsers as [$nick, $psw]) {
        $stmt->bindValue(":nick", $nick, SQLITE3_TEXT);
        $stmt->bindValue(":psw",  $psw,  SQLITE3_TEXT);
        $stmt->execute();

        if ($mainDb->changes() > 0) {
            $messages[] = "User <b>$nick</b> inserted.";
        } else {
            $messages[] = "User <b>$nick</b> already exists.";
        }
    }

} catch (Exception $e) {
    $messages[] = "Initialization of <b>main.db</b> failed: " . $e->getMessage();
}


/* ---- create utxo table ---- */
    $mainDb->exec("
        CREATE TABLE IF NOT EXISTS utxo (
            id      INTEGER PRIMARY KEY AUTOINCREMENT,
            txid    INTEGER NOT NULL,
            owner   TEXT NOT NULL,
            value   INTEGER NOT NULL,
            spent   INTEGER NOT NULL
        )
    ");

    $messages[] = "Table <b>utxo</b> in main.db is ready.";


/* ---- create blockchain table ---- */
    $mainDb->exec("
        CREATE TABLE IF NOT EXISTS blockchain (
            id_block   INTEGER PRIMARY KEY AUTOINCREMENT,
            prev_hash  TEXT,
            tx_root    TEXT,
            nonce      INTEGER,
            timestamp  INTEGER,
            tx_txt     TEXT,
            note_block TEXT,  
            k          TEXT
        )
    ");

    $messages[] = "Table <b>blockchain</b> in main.db is ready.";

    /* ---- initialize genesis block ---- */
    $checkGenesis = $mainDb->querySingle("SELECT COUNT(*) FROM blockchain WHERE id_block = 1");

    if ($checkGenesis == 0) {

        $stmt = $mainDb->prepare("
            INSERT INTO blockchain
            (id_block, prev_hash, tx_root, nonce, timestamp, tx_txt, note_block,k)
            VALUES
            (1, :prev_hash, :tx_root, :nonce, :timestamp, :tx_txt, :note_block, :k)
        ");

        $stmt->bindValue(":prev_hash", "000000", SQLITE3_TEXT);
        $stmt->bindValue(":tx_root", "00e267", SQLITE3_TEXT);
        $stmt->bindValue(":nonce", 2180891, SQLITE3_INTEGER);
        $stmt->bindValue(":timestamp", 1742976064, SQLITE3_INTEGER);
        $stmt->bindValue(":tx_txt", "1|000000|test", SQLITE3_TEXT);
        $stmt->bindValue(":note_block", "BBR.genesis|h.000007", SQLITE3_TEXT);
        $stmt->bindValue(":k", 1, SQLITE3_INTEGER);

        $stmt->execute();

        $messages[] = "Genesis block inserted.";
    } else {
        $messages[] = "Genesis block already exists.";
    }







/* ====================================================
 * OUTPUT
 * ==================================================== */
$status = "<b>SQLite initialization finished.</b>";
$output = implode("<br>", $messages) . "<br><br>" . $status;

if (php_sapi_name() === "cli") {
    echo strip_tags(str_replace("<br>", PHP_EOL, $output)) . PHP_EOL;
} else {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <title>Database Init</title>
        <style>
            body {
                background: #000;
                color: #bbb;
                font-family: Verdana, sans-serif;
                padding: 20px;
            }
            b { color: #fff; }
        </style>
    </head>
    <body>
        $output
    </body>
    </html>";
}





