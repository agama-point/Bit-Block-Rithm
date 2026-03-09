<?php
$current_k1 = $_SESSION['k1'] ?? null;
?>

<div class="box" style="background: #111; color: #eee; border-top: 1px solid #ccc; border-radius: 8px; margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3>Recieve</h3>
        <span style="font-family: monospace; color: #777; font-size: 0.9em;">BBR-NETWORK-v1.0</span>
    </div>  

    <?php if (!$current_k1): ?>
        <p>To use the wallet, you must set a private key in the <b>Keys</b> section.</p>
    <?php else: ?>
        <div style="background:#000; color:#0f0; padding:10px; margin-bottom:10px; font-family:monospace;">
            PubKey | Address: <span id="myFullAddr">...</span>
        </div>
    <?php endif; ?>
</div>

<div>
    <p>Hex PubKey addres: <b><span id="myHexAddr"></span></b></p>
    <p>Bech32__Toy address: <b><span id="myToy32Addr"></span></b></p>
</div>

<script>
$(function(){
    let myPriv = <?= intval($current_k1) ?>;
    if(!myPriv) return;

    let myPub  = scalar_mult(myPriv, G_POINT);

    // hex adresa
    let hexRawAddr = pubkey_to_addr(myPub);
    //let hexKey = hexRawAddr.toUpperCase();
    let hexKey = hexRawAddr;
    $("#myHexAddr").text(hexKey);

    // toy32 adresa
    let toy32 = hexa_to_toy32(hexRawAddr, "a"); // z agama_bech32.js x a1
    $("#myToy32Addr").text(toy32);

});
</script><br />
<hr />
