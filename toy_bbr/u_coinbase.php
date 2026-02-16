<?php
// u_mining.php
$db = new SQLite3(__DIR__ . "/main.db");

// Initialize mining delay from session if not set
if (!isset($_SESSION['minerdelay'])) {
    $_SESSION['minerdelay'] = 5;
}

$currentDelay = intval($_SESSION['minerdelay']);

// --- Handle mining request ---
if (isset($_POST['action']) && $_POST['action'] === 'mining') {
    ob_clean(); 
    $addr = $_POST['addr'];
    $value = 10;
    $utxo_time = time();

    // Insert new transaction
    $db->exec("INSERT INTO transactions (txid,sig,from_addr,to_addr,val1,val2,mp,utxo_time) VALUES (0,NULL,NULL,'$addr',0,$value,0,$utxo_time)");
    $lastId = $db->lastInsertRowID();
    $new_txid = $lastId + 1000;
    $db->exec("UPDATE transactions SET txid = $new_txid WHERE rowid = $lastId");
    $db->exec("INSERT INTO utxo (txid,owner,value,spent) VALUES ($new_txid,'$addr',$value,0)");

    // Double mining delay after each mining attempt
    $_SESSION['minerdelay'] = $_SESSION['minerdelay'] * 2;

    echo "Mining successful! You earned 10 coins (TXID: $new_txid)";
    exit;
}

$current_k1 = $_SESSION['k1'] ?? null;
?>

<div class="panel">
    <h3>Mining Terminal</h3>
    <?php if (!$current_k1): ?>
        <p style="color: orange;">⚠ You do not have a private key (k1) set. Go to the <b>keys</b> section.</p>
    <?php else: ?>
        <div style="background: #222; padding: 10px; border-left: 3px solid #0f0; margin-bottom: 15px;">
            <p style="color: #0f0; margin: 0;">✔ Private key (k1) loaded securely.</p>
            <div style="font-size: 0.9em; margin-top: 10px;">
                <b>ECC Public Key (x, y):</b> <span id="pub-coords" style="color: #aaa;">calculating...</span><br>
                <b>Address (HEXA):</b> <span id="pub-addr" style="color: #0f0; font-weight: bold;">...</span><br>
                <b>Mining delay:</b> <span style="color:#aaa;"><?= $currentDelay ?> s</span>
            </div>
        </div>
        
        <button id="miningBtn" class="btn">⛏ Mine 10 coins</button>
    <?php endif; ?>
    
    <div id="mining-log" style="margin-top: 10px; color: #0f0; font-weight: bold;"></div>
</div>

<script>
$(function() {

    let myPriv = <?= intval($_SESSION['k1'] ?? 0) ?>;
    let minerDelay = <?= $currentDelay ?>;

    if (myPriv !== 0) {

        // Calculate public key and address
        let myPub = scalar_mult(myPriv, G_POINT); 
        let myAddr = pubkey_to_addr(myPub);
        
        $("#pub-coords").text("[" + myPub[0] + ", " + myPub[1] + "]");
        $("#pub-addr").text(myAddr);

        $("#miningBtn").on("click", function() {

            let btn = $(this);
            btn.prop("disabled", true);

            let countdown = minerDelay;

            function tick() {
                if (countdown > 0) {
                    // generate random binary string from 1000-9000
                    let randNum = Math.floor(Math.random() * (9000 - 1000 + 1)) + 1000;
                    let randBin = randNum.toString(2).padStart(12, '0');
                    
                    $("#mining-log").html("⏳ Attempting to mine " + countdown + "s... | " + randBin);
                    countdown--;
                    setTimeout(tick, 1000);
                } else {
                    btn.text("⚡ Working...");
                    $("#mining-log").html("Searching for a block for address " + myAddr + "...");

                    $.post("index.php?page=mining", { 
    action: "mining", 
    addr: myAddr 
}, function(res) {
    // Debug output to console
    console.log("Server Response:", res);

    if (res.status === "success") {
        let newTxId = res.txid;

        // Display the obtained TXID immediately
        $("#mining-log").html(
            "<div style='background: #002200; color: #0f0; padding: 10px; border: 1px solid #0f0;'>" +
            "✅ <b>COINBASE SUCCESS</b><br>" +
            "Transaction ID: <span style='color:white; font-family:monospace;'>" + newTxId + "</span><br>" +
            "Next Delay: " + res.new_delay + "s" +
            "</div>"
        );

        // --- Next step: Seal the block using this TXID ---
        console.log("Proceeding to seal block for TXID: " + newTxId);
        
        // Example of how we will call your mine_tx in the next step:
        /*
        let tx_ids = [newTxId];
        let tx_root = window.hex24(window.ASH24(tx_ids.join(",")));
        // ... get prev_hash and call mine_tx
        */

        // Refresh after 3 seconds to update the UI
        setTimeout(() => { location.reload(); }, 3000);

    } else {
        $("#mining-log").html("<span style='color:red;'>Error: " + res.msg + "</span>");
    }
}, "json").fail(function(xhr) {
    console.error("Critical Server Error:", xhr.responseText);
    $("#mining-log").html("<span style='color:red;'>Server failed to respond with JSON. Check console.</span>");
});
                }
            }

            tick();
        });
    }
});
</script>
