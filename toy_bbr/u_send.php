<?php
$current_k1 = $_SESSION['k1'] ?? null;
?>

<?php if ($current_k1): ?>

<div class="box3">
<h3 class="col_ora">Send | Transfer funds to a destination address</h3>

<div style="display:flex; align-items:center; gap:20px;">
<div>

<div class="box1">
Balance: <span class="digip size2 col2 padd8" id="balance-val">0</span>
</div>

<input type="text" id="targetAddr" placeholder="Recipient address (e.g., e875)" style="width:100px;">

<input type="number" id="sendAmount" value="3" style="width:35px">
<button id="sendBtn" class="ui-btn">Send</button>
<br>

<button type="button" class="addr-btn" data-addr="7214">→ A:7214</button>
<button type="button" class="addr-btn" data-addr="83ca">→ B:83ca</button>
<button type="button" class="addr-btn" data-addr="0aaf">→ C:0aaf</button>
<button type="button" id="clr">CLR</button>

<div id="addrError" style="color:#ff3333; margin-top:5px; display:none;">
Invalid format! Use lowercase hex (0-9, a-f) only.
</div>

</div>
</div>

<div style="display:flex; align-items:center; gap:20px;">
<pre id="walletLog" class="log"></pre>
<div id="qr-code-container"></div>
</div>

</div>

<script>
$(function(){

let myPriv = <?= intval($current_k1) ?>;
if(!myPriv) return;

let myPub  = scalar_mult(myPriv, G_POINT);
let myAddr = pubkey_to_addr(myPub);


// ---------- LOG ----------
function log(t) {

    $("#walletLog").append(
        "[" + new Date().toLocaleTimeString() + "] " + t + "\n"
    );

    $("#walletLog").scrollTop(
        $("#walletLog")[0].scrollHeight
    );
}


// ---------- CLR ----------
$("#clr").on("click", function() {

    $("#walletLog").text("");
    $("#qr-code-container").empty();

});


// ---------- QR ----------
function generateQR(text) {
    $("#qr-code-container").empty();
    if (!text) return;

    new QRCode(
        document.getElementById("qr-code-container"),
        {
            text: text, width: 100, height: 100
        }
    );
}


// ---------- OK mark ----------
function showOK() {

    $("#qr-code-container").html(
        '<div class="padd8" id="okMark">✓</div>'
    );

    $("#okMark").css({
        fontSize: "90px",
        color: "#00cc44",
        fontWeight: "bold",
        textAlign: "center",
        width: "128px"
    });

    setTimeout(function(){

        $("#okMark").fadeOut(
            800,
            function(){
                $("#qr-code-container").empty();
            }
        );

    }, 3000);
}



// ---------- addr buttons ----------
$(".addr-btn").on("click", function(){

    let value = $(this).data("addr");

    log("Recipient address set to: " + value);

    $("#targetAddr").val(value);

    generateQR(value);

});



// ---------- input check ----------
$("#targetAddr").on("input", function(){

let val = $(this).val();

let lowerVal = val.toLowerCase();

let hexRegex = /^[0-9a-f]*$/;

if (val !== lowerVal) {
    $(this).val(lowerVal);
    val = lowerVal;
}

if (!hexRegex.test(val)) {

    $(this).css("border","2px solid #ff4444");
    $("#addrError").fadeIn();

    $("#sendBtn")
        .prop("disabled", true)
        .css("opacity","0.5");

}
else {

    $(this).css("border","1px solid #333");
    $("#addrError").fadeOut();

    $("#sendBtn")
        .prop("disabled", false)
        .css("opacity","1");

}

});



// ---------- SEND ----------
$("#sendBtn").click(function(){

let to = $("#targetAddr").val().trim();
let amount = parseInt($("#sendAmount").val());

if(!to) {
    alert("Enter recipient address!");
    return;
}

log("Searching for available UTXO...");

$.post(
"index.php",
{ action:"get_utxo", addr: myAddr },

function(utxo){

    if(!utxo || utxo.error) {
        log("ERROR: You have no available coins.");
        return;
    }

    if(amount > utxo.value) {
        log("ERROR: UTXO value is only " + utxo.value);
        return;
    }

    let txid = utxo.txid;

    let msg =
        myAddr + "|" +
        txid + "|" +
        to + "|" +
        amount;

    let h = ASH24(msg);
    let h_hex = hex24(ASH24(msg));

    let sigN = signToy(myPriv, h);
    let sigHexa = sig_to_hexa(sigN);

    log("Signing transaction for " + amount + " units.");

    log(
        "Msg + hash: " +
        msg +
        " = " +
        h_hex
    );

    log(
        "Hash: " + h +
        " | Sig: r=" +
        sigN.r +
        ", s=" +
        sigN.s +
        " | " +
        sigHexa
    );

    $.post(
    "index.php",
    {
        action:"send",
        from: myAddr,
        to: to,
        val1: utxo.value,
        val2: amount,
        sig_hex: sigHexa,
        utxo_id: utxo.id,
        utxo_txid: txid
    },

    function(resp){

        log(resp);

        // ✅ zde změna
        if(resp.includes("Transaction broadcasted")) {

            showOK();

            setTimeout(
                ()=>location.reload(),
                3500
            );
        }

    });

});

});

});
</script>

<?php endif; ?>