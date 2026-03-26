<style>
    .info-container { margin-top: 10px;  font-family: monospace; }
    .info-box {
        padding: 15px;
        border: 1px solid;
        min-height: 200px; /* Aby stránka při přepínání neposkakovala */
    }
    #techInfo {  display: none; /* Výchozí stav: schováno */
    }
    .controls {  display: flex;  gap: 5px;   }
    
    /* Styl pro aktivní tlačítko */
    button.active {
        background: #333;
        color: #fff;
        border-color: #eee;
        font-weight: bold;
    }
   
</style>


<img class="w800" src="svg/bbr_obt.svg" alt="Blockchain">
<div class="padd8 w800">
OBC is based on the BBR <span class="col1">- Bit-Block-(algo)Ritm -</span> platform, an experimental architecture focused on minimalistic blockchain design. The system uses 8-bit ECC cryptography (ESS251) together with ASH24/32 hashing, creating a lightweight and educational cryptographic environment optimized for small data structures, simple verification, and transparent mathematical logic.
</div>
<br />
<div class="grad_line"></div>
<br />

<div class="controls">
    <button id="btnBasic" class="active">Basic Info</button>
    <button id="btnTech">Technical Info</button>
</div>

<div class="info-container">
    <div id="basicInfo" class="info-box">
 
<pre class="log">
create account or login
"Test" | psw Test ->
---------------------------------------------

<span class="col_ora">[ keys ]</span>
generation of a private key:
- with visualization of jumps on the ECC251 curve
- or numerical value
<span class="col_ora">  A = a * G | B = b* G</span>

<span class="col_ora">[ wallet ]</span>
obtaining the first coins:
- mining
- receiving from someone

working with the coins
- sending
- tracking UTXO

<span class="col_ora">[ mining ]</span>
- empty block
- transaction

<span class="col_ora">[ consider the vulnerabilities and attack vectors ]</span>
- limited number of private keys
- address collisions
- low bit depth from 24-bit hashing
- centralized database on a single server
- …
</pre>
    </div>

    <div id="techInfo" class="info-box">

<div>
<img class="w800" src="svg/bbr_keys.svg?v2" alt="Keys">
<br />
<img class="w800" src="svg/bbr_tx.svg?v2" alt="Transaction">
<br />
<img class="w800" src="svg/bbr_block.svg?v2" alt="Blockchain">
</div>

<pre>
---------------------------------------------
<span class="col_ora">[ blockchain math ]</span>

Formula for transaction root:
tx_root = ash24(TX1|TX2|TX3)

Formula for block identification:
block_hash = ash24(block_ID|timestamp|TX_ROOT)

Current Network Difficulty: 1 (k)
Hash Algorithm: ASH24-ECC
---------------------------------------------
secp256k1:
Gx:0x79be667ef9dcbbac55a06295ce870b07029bfcdb2dce28d959f2815b16f81798
Gy:0x483ada7726a3c4655da4fbfc0e1108a8fd17b448a68554199c47d08ffb10d4b8
p = (2**256 - 2**32 - 2**9 - 2**8 - 2**7 - 2**6 - 2**4 -1)
  = (2**256 - 2**32 - 977)
  = 0xfffffffffffffffffffffffffffffffffffffffffffffffffffffffefffffc2f
  = 115792089237316195423570985008687907852837564279074904382605163141518161494337
---------------------------------------------
ess251:
G = [10,76]
p = 251
---------------------------------------------



<img class="w800" src="svg/bbr_sign.svg?v2" alt="Sign/Verify">

<img class="w800" src="svg/bbr_script.svg?v2" alt="Sign/Verify">

</pre>
    </div>
</div>

<script>
$(function(){
    // Funkce pro přepínání
    $("#btnBasic").click(function(){
        // Efekt zmizení tech a objevení basic
        $("#techInfo").hide();
        $("#basicInfo").fadeIn(300);
        
        // Úprava tlačítek
        $("#btnTech").removeClass("active");
        $(this).addClass("active");
    });

    $("#btnTech").click(function(){
        // Efekt zmizení basic a objevení tech
        $("#basicInfo").hide();
        $("#techInfo").fadeIn(300);
        
        // Úprava tlačítek
        $("#btnBasic").removeClass("active");
        $(this).addClass("active");
    });
});
</script>