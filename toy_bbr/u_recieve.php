<div class="box3">

<h3 class="col_ora">
Receive | Accept assets to your personal address
</h3>

<?php if (!$current_k1): ?>

<p>
To use the wallet, you must set a private key
in the <b>Keys</b> section.
</p>

<?php else: ?>

<div class="box1">
PubKey | Address:
<span id="myFullAddr">...</span>

<span class="padd8" id="addrQR"></span>

</div>


<pre class="log">

PubKey address (hex): <b class="col3"><span id="myHexAddr"></span></b>

Bech32__Toy address: <b><span id="myToy32Addr"></span></b>

</pre>


<?php endif; ?>

<script>

$(function(){

let myPriv = <?= intval($current_k1) ?>;

if(!myPriv) return;
let myPub = scalar_mult( myPriv, G_POINT );
let hexRawAddr = pubkey_to_addr( myPub);
let toy32 = hexa_to_toy32( hexRawAddr, "a" );

$("#myHexAddr").text(hexRawAddr);
$("#myToy32Addr").text(toy32);
$("#myFullAddr").text(toy32);

// hex canvas

let canvasHex = drawHexCanvas(hexRawAddr, { size:12, gap:3 });
$("#myFullAddr").append("<br><br>").append(canvasHex);

// QR CODE
new QRCode(
    document.getElementById("addrQR"),
    {
        text: hexRawAddr,
        width: 100, height: 100, colorDark: "#000000", colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
    }
);

});

</script>

</div>