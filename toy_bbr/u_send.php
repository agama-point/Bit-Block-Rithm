<?php
$current_k1 = $_SESSION['k1'] ?? null;
?>

<?php if ($current_k1): ?>

<h1 class="digip">WALLET</h1>

<div class="box" style="border: 1px solid #333; padding: 15px; border-radius: 8px;">
<h3>Send</h3>

<div style="display:flex; align-items:center; gap:20px;">
  <div>
 <input type="text" id="targetAddr" placeholder="Recipient address (e.g., e875)" style="width:150px; height:30px;">

  <input type="number" id="sendAmount" value="3" style="width:60px">
  <button id="sendBtn" style="color:#0f0; border-color:#0f0;">Send</button>

  <button type="button" class="addr-btn" data-addr="83c1">A:83c1</button>
  <button type="button" class="addr-btn" data-addr="e875">B:e875</button>
  <button type="button" class="addr-btn" data-addr="01C0">C:01c0</button>

  <div id="addrError" style="color: #ff4444; font-size: 12px; margin-top: 5px; display: none;">
    Invalid format! Use lowercase hex (0-9, a-f) only.
  </div>
  </div>
  
</div>
<div style="display:flex; align-items:center; gap:20px;">
    <pre id="walletLog" style="height:150px; overflow:auto; margin-top:10px; border-left: 2px solid #0f0; padding-left:10px;"></pre>
    <div id="qr-code-container"></div>
</div>
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

     function generateQR(text) {
        $("#qr-code-container").empty();   // smaže předchozí QR

        if (!text) return;

        new QRCode(document.getElementById("qr-code-container"), {
            text: text,
            width: 128,
            height: 128
        });
    }

    $(".addr-btn").on("click", function() {
        let value = $(this).data("addr");
        log("Recipient address set to: " + value);

        $("#targetAddr").val(value);  
        generateQR(value);            
    });

    $("#targetAddr").on("input", function() {
    let val = $(this).val();
    
    // 1. Automatický převod na lowercase
    let lowerVal = val.toLowerCase();
    
    // 2. Kontrola regulárním výrazem (pouze 0-9 a a-f)
    let hexRegex = /^[0-9a-f]*$/;
    
    if (val !== lowerVal) {
        $(this).val(lowerVal);
        val = lowerVal;
    }

    if (!hexRegex.test(val)) {
        $(this).css("border", "2px solid #ff4444");
        $("#addrError").fadeIn();
        $("#sendBtn").prop("disabled", true).css("opacity", "0.5");
    } else {
        $(this).css("border", "1px solid #333");
        $("#addrError").fadeOut();
        $("#sendBtn").prop("disabled", false).css("opacity", "1");
    }
});

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
        let sigN = signToy(myPriv, h);
        let sigHexa = sig_to_hexa(sigN);

        log("Signing transaction for " + amount + " units.");
        //log(h_hex);
        log("Msg + hash: " + msg + " = " + h_hex);
        log("Hash: " + h + " | Sig: r=" + sigN.r + ", s=" + sigN.s + " | " + sigHexa);

        // --- SEND TO BACKEND ---
        $.post("index.php", {
            action: "send",
            from: myAddr,
            to: to,
            val1: utxo.value,
            val2: amount,
            sig_hex: sigHexa,
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
