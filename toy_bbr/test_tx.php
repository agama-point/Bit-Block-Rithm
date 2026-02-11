<?php
$db = new SQLite3(__DIR__ . "/main.db");

/* ===============================
   AJAX API
=================================*/

if(isset($_POST['action'])){

    $action = $_POST['action'];

    // ---------------- COINBASE ----------------
    if($action === "coinbase"){

        $addr  = $_POST['addr'];
        $value = intval($_POST['value']);
        $txid  = hash("sha256", "coinbase".$addr.$value.time());

        $db->exec("INSERT INTO transactions
            (txid,sig,from_addr,to_addr,val1,val2,mp)
            VALUES ('$txid',NULL,NULL,'$addr',0,$value,0)");

        $db->exec("INSERT INTO utxo
            (txid,owner,value,spent)
            VALUES ('$txid','$addr',$value,0)");

        echo "Coinbase vytvořen: $value jednotek → $addr";
        exit;
    }

    // ---------------- GET UTXO ----------------
    if($action === "get_utxo"){

        $addr = $_POST['addr'];

        $res = $db->query("SELECT * FROM utxo
                           WHERE owner='$addr' AND spent=0
                           LIMIT 1");

        $row = $res->fetchArray(SQLITE3_ASSOC);
        echo json_encode($row);
        exit;
    }

    // ---------------- SEND ----------------
    if($action === "send"){

        $from = $_POST['from'];
        $to   = $_POST['to'];
        $val1 = intval($_POST['val1']);
        $val2 = intval($_POST['val2']);
        $r    = intval($_POST['r']);
        $s    = intval($_POST['s']);
        $utxo_id = intval($_POST['utxo_id']);

        if($val2 > $val1){
            echo "Nedostatek prostředků.";
            exit;
        }

        $txdata = $from."|".$to."|".$val2;
        $txid   = hash("sha256",$txdata.$r.$s.time());

        $db->exec("INSERT INTO transactions
            (txid,sig,from_addr,to_addr,val1,val2,mp)
            VALUES ('$txid','$r,$s','$from','$to',$val1,$val2,1)");

        // označit UTXO jako spent
        $db->exec("UPDATE utxo SET spent=1 WHERE id=$utxo_id");

        // nové UTXO pro příjemce
        $db->exec("INSERT INTO utxo
            (txid,owner,value,spent)
            VALUES ('$txid','$to',$val2,0)");

        // change
        $change = $val1 - $val2;
        if($change > 0){
            $db->exec("INSERT INTO utxo
                (txid,owner,value,spent)
                VALUES ('$txid','$from',$change,0)");
        }

        echo "Transakce OK. Change: $change";
        exit;
    }

    // ---------------- BALANCE ----------------
    if($action === "balance"){

        $addr = $_POST['addr'];

        $res = $db->query("SELECT SUM(value) as total
                           FROM utxo
                           WHERE owner='$addr' AND spent=0");

        $row = $res->fetchArray(SQLITE3_ASSOC);
        echo intval($row['total']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="utf-8">
<title>ESS251 Test UTXO Flow</title>

<script src="js/jquery.min.js"></script>
<script src="js/ash24.js"></script>
<script src="js/ess251.js"></script>

<style>
body { background:#111; color:#ddd; font-family:monospace; }
button { margin:5px; padding:6px 12px; }
pre { background:#222; padding:10px; }
</style>
</head>
<body>

<h2>ESS251 – Test jednoduchých transakcí</h2>

<button id="coinbaseBtn">1) Coinbase 10 → Alice</button>
<button id="sendBtn">2) Alice pošle 3 Bobovi</button>
<button id="balanceBtn">3) Zobraz balance</button>

<pre id="log"></pre>

<hr>
<?php include "table_tx.php"; ?>



<script>

function hex24(n){ return "0x"+n.toString(16).padStart(6,'0'); }
function log(t){ $("#log").append(t+"\n"); }

// TEST KLÍČE
let alicePriv = 111;
let bobPriv   = 222;

let alicePub = scalar_mult(alicePriv, G_POINT);
let bobPub   = scalar_mult(bobPriv, G_POINT);

function pubkey_to_addr(pub){
    return pub[0].toString(16).padStart(2,'0') +
           pub[1].toString(16).padStart(2,'0');
}

let aliceAddr = pubkey_to_addr(alicePub);
let bobAddr   = pubkey_to_addr(bobPub);

log("Alice pub: ["+alicePub+"] → "+aliceAddr);
log("Bob pub: ["+bobPub+"] → "+bobAddr);
log("----------------------------------");

// ================= COINBASE =================
$("#coinbaseBtn").click(function(){

    $.post("test_tx.php",{
        action:"coinbase",
        addr:aliceAddr,
        value:10
    },function(res){
        log(res);
    });
});

// ================= SEND =================
$("#sendBtn").click(function(){

    $.post("test_tx.php",{
        action:"get_utxo",
        addr:aliceAddr
    },function(res){

        let utxo = JSON.parse(res);
        if(!utxo){
            log("Žádné UTXO pro Alice.");
            return;
        }

        let amount = 3;

        let msg = aliceAddr+"|"+bobAddr+"|"+amount;
        let h = ASH24(msg);

        log("Tx message: "+msg);
        log("Tx hash: "+hex24(h));

        let sig = sign(alicePriv, h);
        log("Signature r="+sig.r+", s="+sig.s);

        let valid = verify(alicePub, h, sig);
        log("Local verify: "+(valid?"Valid":"Invalid"));

        if(!valid){
            log("Podpis neplatný.");
            return;
        }

        $.post("test_tx.php",{
            action:"send",
            from:aliceAddr,
            to:bobAddr,
            val1:utxo.value,
            val2:amount,
            r:sig.r,
            s:sig.s,
            utxo_id:utxo.id
        },function(resp){
            log(resp);
        });

    });
});

// ================= BALANCE =================
$("#balanceBtn").click(function(){

    $.post("test_tx.php",{
        action:"balance",
        addr:aliceAddr
    },function(res){
        log("Alice balance: "+res);
    });

    $.post("test_tx.php",{
        action:"balance",
        addr:bobAddr
    },function(res){
        log("Bob balance: "+res);
    });
});

</script>
</body>
</html>
