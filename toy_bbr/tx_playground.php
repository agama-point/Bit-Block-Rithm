<?php
$db = new SQLite3(__DIR__ . "/main.db");

if(isset($_POST['action'])){
    $action = $_POST['action'];

    if($action === "coinbase"){
        $addr  = $_POST['addr'];
        $value = intval($_POST['value']);
        $utxo_time = time();
        $db->exec("INSERT INTO transactions (txid,sig,from_addr,to_addr,val1,val2,mp,utxo_time) VALUES (0,NULL,NULL,'$addr',0,$value,0,$utxo_time)");
        $lastId = $db->lastInsertRowID();
        $new_txid = $lastId + 1000;
        $db->exec("UPDATE transactions SET txid = $new_txid WHERE rowid = $lastId");
        $db->exec("INSERT INTO utxo (txid,owner,value,spent) VALUES ($new_txid,'$addr',$value,0)");
        echo "Coinbase OK: $value j. (TXID: $new_txid) pro $addr";
        exit;
    }

    if($action === "get_utxo"){
        header('Content-Type: application/json');
        $addr = $_POST['addr'];
        $res = $db->query("SELECT * FROM utxo WHERE owner='$addr' AND spent=0 LIMIT 1");
        echo json_encode($res->fetchArray(SQLITE3_ASSOC));
        exit;
    }

    if($action === "send"){
        $from = $_POST['from'];
        $to   = $_POST['to'];
        $val1 = intval($_POST['val1']);
        $val2 = intval($_POST['val2']);
        $r    = $_POST['r'];
        $s    = $_POST['s'];
        $utxo_id = intval($_POST['utxo_id']);
        $utxo_time = time();

        if($val2 > $val1){ echo "Chyba: Nedostatek prostředků v UTXO."; exit; }

        $db->exec("INSERT INTO transactions (txid,sig,from_addr,to_addr,val1,val2,mp,utxo_time) VALUES (0,'$r,$s','$from','$to',$val1,$val2,1,$utxo_time)");
        $lastId = $db->lastInsertRowID();
        $new_txid = $lastId + 1000;
        $db->exec("UPDATE transactions SET txid = $new_txid WHERE rowid = $lastId");
        $db->exec("UPDATE utxo SET spent=1 WHERE id=$utxo_id");
        $db->exec("INSERT INTO utxo (txid,owner,value,spent) VALUES ($new_txid,'$to',$val2,0)");
        $change = $val1 - $val2;
        if($change > 0) $db->exec("INSERT INTO utxo (txid,owner,value,spent) VALUES ($new_txid,'$from',$change,0)");

        echo "Platba OK (TXID: $new_txid). Posláno $val2 j., vráceno $change j.";
        exit;
    }

    if($action === "balance"){
        $addr = $_POST['addr'];
        $total = $db->querySingle("SELECT SUM(value) FROM utxo WHERE owner='$addr' AND spent=0");
        echo intval($total);
        exit;
    }

    if($action === "delete_all"){
        $db->exec("DELETE FROM utxo; DELETE FROM transactions;");
        echo "Ledger vyčištěn.";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title>ESS251 UTXO Wallet</title>
    <script src="js/jquery.min.js"></script>
    <script src="js/ash24.js"></script>
    <script src="js/ess251.js"></script>
    <style>
        body { background:#111; color:#ddd; font-family:monospace; padding:20px; }
        .box { background:#222; padding:15px; border:1px solid #444; margin-bottom:20px; }
        button { background:#333; color:#0f0; border:1px solid #0f0; padding:8px 15px; cursor:pointer; }
        button:hover { background:#0f0; color:#000; }
        input[type="number"] { background:#000; color:#0f0; border:1px solid #444; padding:7px; width:60px; }
        pre { background:#000; padding:10px; border-left:3px solid #0f0; color:#0f0; max-height:200px; overflow-y:auto; }
        .danger { border-color:#f44; color:#f44; }
        label { margin-right: 15px; cursor: pointer; color: #0f0; }
    </style>
</head>
<body>

<h2>Agama UTXO Wallet</h2>

<div class="box">
    <strong>Aktivní uživatel:</strong><br><br>
    <label><input type="radio" name="user" value="alice" checked> Alice (83c1)</label>
    <label><input type="radio" name="user" value="bob"> Bob (e875)</label>
    <button id="balanceBtn">Aktualizovat Balance</button>
</div>

<div class="box">
    <strong>Akce:</strong><br><br>
    <button id="coinbaseBtn">Vytěžit 10 mincí</button> 
    | Poslat: <input type="number" id="sendAmount" value="3" min="1" max="9"> 
    <button id="sendBtn">Provést platbu</button>
    <button id="deleteBtn" class="danger" style="float:right">Smazat vše</button>
</div>

<pre id="log"></pre>

<hr />
<?php 
include "table_tx.php"; 
include "table_utxo_all.php"; 
?>
<hr />


<script>
const URL = window.location.href;

function log(t){ 
    $("#log").append("[" + new Date().toLocaleTimeString() + "] " + t + "\n"); 
    $("#log").scrollTop($("#log")[0].scrollHeight);
}

let keys = {
    alice: { priv: 111, pub: scalar_mult(111, G_POINT), addr: pubkey_to_addr(scalar_mult(111, G_POINT)) },
    bob:   { priv: 222, pub: scalar_mult(222, G_POINT), addr: pubkey_to_addr(scalar_mult(222, G_POINT)) }
};

log("Peněženka připravena.");

// Pomocná funkce pro získání aktuálně zvoleného uživatele
function getActive() {
    let val = $('input[name="user"]:checked').val();
    return { 
        name: val,
        sender: keys[val],
        receiver: (val === 'alice') ? keys.bob : keys.alice
    };
}

// 1) COINBASE
$("#coinbaseBtn").click(() => {
    let active = getActive();
    $.post(URL, { action:"coinbase", addr: active.sender.addr, value:10 }, (res) => {
        log(res);
        setTimeout(() => location.reload(), 800);
    });
});

// 2) SEND
$("#sendBtn").click(() => {
    let active = getActive();
    let amount = parseInt($("#sendAmount").val());

    // Validace
    if(amount <= 0 || amount >= 10) {
        alert("Částka musí být mezi 1 a 9!");
        return;
    }

    log("Hledám UTXO pro " + active.name + "...");

    $.post(URL, { action:"get_utxo", addr: active.sender.addr }, (utxo) => {
        if(!utxo || !utxo.value) { 
            log("CHYBA: " + active.name + " nemá žádné volné mince!"); 
            return; 
        }

        let msg = active.sender.addr + "|" + active.receiver.addr + "|" + amount;
        let h = ASH24(msg);
        let sig = signToy(active.sender.priv, h);

        log("Podepisuji transakci: " + active.name + " -> " + active.receiver.name + " (" + amount + " j.)");

        $.post(URL, {
            action: "send",
            from: active.sender.addr,
            to: active.receiver.addr,
            val1: utxo.value,
            val2: amount,
            r: sig.r,
            s: sig.s,
            utxo_id: utxo.id
        }, (resp) => {
            log(resp);
            setTimeout(() => location.reload(), 1200);
        });
    });
});

// 3) BALANCE
$("#balanceBtn").click(() => {
    $.post(URL, { action:"balance", addr: keys.alice.addr }, (r) => log("Alice balance: " + (r || 0)));
    $.post(URL, { action:"balance", addr: keys.bob.addr }, (r) => log("Bob balance: " + (r || 0)));
});

// 4) DELETE
$("#deleteBtn").click(() => {
    if(confirm("Smazat historii?")) $.post(URL, { action:"delete_all" }, (r) => { log(r); location.reload(); });
});
</script>

</body>
</html>