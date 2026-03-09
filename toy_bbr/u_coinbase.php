<?php
// u_mining.php
$db = new SQLite3(__DIR__ . "/main.db");

// --- Načtení posledního bloku (pro JS výpočet) ---
$last_block_query = $db->query("
    SELECT id_block, prev_hash, timestamp, tx_root, nonce 
    FROM blockchain 
    ORDER BY id_block DESC 
    LIMIT 1
");

$last_block = $last_block_query ? $last_block_query->fetchArray(SQLITE3_ASSOC) : false;

$lb_id    = $last_block ? $last_block['id_block'] : 0;
$lb_prev  = $last_block ? $last_block['prev_hash'] : 0;
$lb_ts    = $last_block ? $last_block['timestamp'] : 0;
$lb_root  = $last_block ? $last_block['tx_root'] : '000000';
$lb_nonce = $last_block ? $last_block['nonce'] : '99999';

// Initialize mining delay
if (!isset($_SESSION['minerdelay'])) {
    $_SESSION['minerdelay'] = 5;
}
$currentDelay = intval($_SESSION['minerdelay']);

// --- Handle mining request ---
if (isset($_POST['action']) && $_POST['action'] === 'mining') {
    ob_clean(); 
    header('Content-Type: application/json');
    $addr = $_POST['addr'];
    $value = 10;
    $utxo_time = time();

    $db->exec("INSERT INTO transactions (txid,sig,from_addr,to_addr,val1,val2,mp,utxo_time) VALUES (0,NULL,NULL,'$addr',0,$value,0,$utxo_time)");
    $lastId = $db->lastInsertRowID();
    $new_txid = $lastId + 1000;
    $db->exec("UPDATE transactions SET txid = $new_txid WHERE rowid = $lastId");
    $db->exec("INSERT INTO utxo (txid,owner,value,spent) VALUES ($new_txid,'$addr',$value,0)");

    $_SESSION['minerdelay'] = $_SESSION['minerdelay'] * 2;

    echo json_encode([
        "status" => "success",
        "txid" => $new_txid,
        "new_delay" => $_SESSION['minerdelay']
    ]);
    exit;
}
$current_k1 = $_SESSION['k1'] ?? null;
?>

<div id="last-block-data" 
     data-id="<?= $lb_id ?>" 
     data-prev="<?= $lb_prev ?>" 
     data-ts="<?= $lb_ts ?>" 
     data-root="<?= $lb_root ?>" 
     data-nonce="<?= $lb_nonce ?>" 
     style="display:none;">
</div>

<div class="panel box2">
    <h3 class="col_ora">Mining Terminal</h3>
    <?php if (!$current_k1): ?>
        <p style="color: orange;">⚠ You do not have a private key (k1) set.</p>
    <?php else: ?>
        <div>
            <p>✔ Private key (k1) loaded securely.</p>
            <div>
                <b>Address:</b> <span id="pub-addr">...</span><br>
                <b>Mining delay:</b> <span><?= $currentDelay ?> s</span>
            </div>
        </div>
        <br />
        <button id="miningBtn" class="btn">⛏ Mine 10 coins</button>
    <?php endif; ?>
    
    <div id="mining-log" style="margin-top: 15px; font-family: monospace; line-height: 1.5;"></div>
</div>

<script>
$(function() {
    let myPriv = <?= intval($_SESSION['k1'] ?? 0) ?>;
    let minerDelay = <?= $currentDelay ?>;

    if (myPriv !== 0) {
        let myPub = scalar_mult(myPriv, G_POINT); 
        let myAddr = pubkey_to_addr(myPub);
        $("#pub-addr").text(myAddr);

        $("#miningBtn").on("click", function() {
    let btn = $(this);
    btn.prop("disabled", true);
    let countdown = minerDelay;

    function tick() {
        if (countdown > 0) {
            let randBin = (Math.floor(Math.random() * 8000) + 1000).toString(2);
            $("#mining-log").html("<span style='color:#0f0;'>⏳ Mining: " + countdown + "s | " + randBin + "</span>");
            countdown--;
            setTimeout(tick, 1000);
        } else {
            // --- VÝPOČET PŘED ODESLÁNÍM ---
            let lb = $('#last-block-data').data();
            let prev_string = lb.id + "|" + lb.prev + "|" + lb.ts + "|" + lb.root + "|" + lb.nonce;
            let calculated_prev_hash = window.hex24(window.ASH24(prev_string));

            //let raw_tx  = window.ASH24(tx_list_string);
            //let tx_root = window.hex24(raw_tx);


            btn.text("⚡ Sealing...");

            // Odesíláme do index.php akci mining + náš vypočítaný prev_hash
            $.post("index.php?page=mining", { 
                action: "mining", 
                addr: myAddr,
                prev_hash: calculated_prev_hash 
            }, function(res) {
                if (res.status === "success") {
                    // Zobrazíme log a necháme ho viset
                    let html = `
                        <div style="background: #111; border: 1px solid #0f0; padding: 10px; color: #0f0;">
                            <b style="color:#fff;">✅ BLOCK SEALED & SAVED</b><br>
                            TXID: <span style="color:white;">${res.txid}</span><br>
                            Block ID: ${res.block_id}<br>
                            <hr style="border:0; border-top:1px solid #333; margin: 10px 0;">
                            <b>Prev Hash sent:</b> <span style="color:#ff0;">${calculated_prev_hash}</span><br>
                            <b>TX Root:</b> <span style="color:#ff0;">${res.tx_root}</span><br>
                            <hr style="border:0; border-top:1px solid #333; margin: 10px 0;">
                            <span style="color:#aaa;">Refreshing in 10s...</span>
                        </div>
                    `;
                    $("#mining-log").html(html);
                    setTimeout(() => { location.reload(); }, 10000);
                }
            }, "json");
        }
    }
    tick();
});
            
    }
});
</script>