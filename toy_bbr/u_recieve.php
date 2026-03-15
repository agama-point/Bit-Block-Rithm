<div class="box1">
<h3 class="col_ora">Receive | Accept assets to your personal address</h3>  

    <?php if (!$current_k1): ?>
        <p>To use the wallet, you must set a private key in the <b>Keys</b> section.</p>
    <?php else: ?>
        <div class="box2">
            PubKey | Address: <span id="myFullAddr">...</span>
        
    <?php endif; ?>

<pre class="log">
    Hex PubKey addres: <b><span id="myHexAddr"></span></b>
    Bech32__Toy address: <b><span id="myToy32Addr"></span></b>
</pre>
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
</script>
</div>


