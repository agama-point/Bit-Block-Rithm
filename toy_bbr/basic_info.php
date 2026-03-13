<style>
    .info-container { margin-top: 10px;  font-family: monospace; }
    .info-box {
        background: #111;
        padding: 15px;
        border: 1px solid;
        min-height: 200px; /* Aby stránka při přepínání neposkakovala */
    }
    #techInfo {  display: none; /* Výchozí stav: schováno */
    }
    .controls {  display: flex;  gap: 5px;   }
    buttonX {
        padding: 8px 15px;
        cursor: pointer;
        background: #222;
        color: #888;
        border: 1px solid #444;
        transition: 0.2s;
    }
    /* Styl pro aktivní tlačítko */
    button.active {
        background: #333;
        color: #fff;
        border-color: #eee;
        font-weight: bold;
    }
   
</style>

<div class="controls">
    <button id="btnBasic" class="active">Basic Info</button>
    <button id="btnTech">Technical Info</button>
</div>

<div class="info-container">
    <div id="basicInfo" class="info-box">
<img src="svg/bbr_block.svg" alt="Blockchain" width="650">
 
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
<img src="svg/bbr2601.svg" alt="test" width="650">
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