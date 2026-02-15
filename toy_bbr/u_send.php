<?php
$current_k1 = $_SESSION['k1'] ?? null;
?>

<?php if ($current_k1): ?>
<div class="box" style="border: 1px solid #333; padding: 15px; border-radius: 8px;">
    <h3>Send</h3>
    <input type="text" id="targetAddr" placeholder="Recipient address (e.g., e875)" style="width:150px">
    <input type="number" id="sendAmount" value="3" style="width:60px">
    <button id="sendBtn" style="color:#0f0; border-color:#0f0;">Send</button>

    <pre id="walletLog" style="height:150px; overflow:auto; margin-top:10px; border-left: 2px solid #0f0; padding-left:10px;"></pre>
</div>

<script>
$(function(){
    let myPriv = <?= intval($current_k1) ?>;
    if(!myPriv) return;

    let myPub  = scalar_mult(myPriv, G_POINT);
    let myAddr = pubkey_to_addr(myPub);

    function log(t) {
        $("#walletLog").append("[" + new Date().toLocaleTimeString() + "] " + t + "\n");
        $("#walletLog").scrollTop($("#walletLog")[0].scrollHeight);
    }

    // --- SEND TRANSACTION ---
    $("#sendBtn").click(function(){
    let to = $("#targetAddr").val().trim();
    let amount = parseInt($("#sendAmount").val());
    if(!to) { alert("Enter recipient address!"); return; }

    log("Searching for available UTXO...");

    $.post("index.php", { action: "get_utxo", addr: myAddr }, function(utxo){
        if(!utxo || utxo.error) {
            log("ERROR: You have no available coins.");
            return;
        }
        if(amount > utxo.value) {
            log("ERROR: UTXO value is only " + utxo.value);
            return;
        }

        // --- SIGNING TRANSACTION ---
        let txid = utxo.txid; // <--- nově získaný txid
        let msg = myAddr + "|" + txid + "|" + to + "|" + amount;
        let h = ASH24(msg);
        let h_hex = hex24(ASH24(msg));
        let sig = signToy(myPriv, h);

        log("Signing transaction for " + amount + " units.");
        //log(h_hex);
        log("Msg + hash: " + msg + " = " + h_hex);
        log("Hash: " + h + " | Sig: r=" + sig.r + ", s=" + sig.s);

        // --- SEND TO BACKEND ---
        $.post("index.php", {
            action: "send",
            from: myAddr,
            to: to,
            val1: utxo.value,
            val2: amount,
            r: sig.r,
            s: sig.s,
            utxo_id: utxo.id,
            utxo_txid: txid // <--- posíláme txid
        }, function(resp){
            log(resp);
            if(resp.includes("OK")) {
                setTimeout(()=>location.reload(), 3500);
            }
        });
    });
});

});
</script>
<?php endif; ?>
