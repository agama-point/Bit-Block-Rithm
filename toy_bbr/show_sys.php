<div class="box2">
<h3 class="col_gre">SYS. DEBUG</h3>
<pre id="log-system" class="log"></pre>

<script>
//style="white-space: pre-wrap; margin: 0;"

    (function() {
        // Používáme anonymní funkci, aby se proměnné nepletly s ostatními skripty
        document.addEventListener("DOMContentLoaded", function() {
            
            // 1. Přelití dat ze Session do JS objektu
            const sessionData = {
                nick: <?php echo json_encode($_SESSION['nick'] ?? 'Anonym'); ?>,
                k1:   <?php echo json_encode($_SESSION['k1'] ?? 111); ?>,
                mdel: <?php echo json_encode($_SESSION['minerdelay'] ?? 0); ?>
            };
            // 2. Definice logovací funkce s kontrolou existence elementu
            const logElement = document.getElementById("log-system");
            
            function log(txt) {
                if (logElement) {
                    logElement.textContent += txt + "\n";
                } else {
                    console.error("Chyba: Element #log-system nebyl nalezen!");
                }
            }

// ======================= start ====================
log("===== SESSION DEBUG =====");
log("user: " + sessionData.nick);
            
let priv = sessionData.k1; 
//log("Používám privátní klíč (k1): " + priv);
log("=========================");
log("ESS251_VER: " + ESS251_VER);
log(` -> Curve: y^2 = x^3 + ${ECC_PARAMS.b} mod ${ECC_PARAMS.p} | [${ECC_PARAMS.G[0]}, ${ECC_PARAMS.G[1]}]`);
log("ASH24_VER: " + ASH24_VER);
log("ASH32_VER: " + ASH32_VER);
log("p5.js: " + p5.prototype.VERSION);
log("========================="); 

let pub = scalar_mult(priv, G_POINT);
let pubKeyAddr = pubkey_to_addr(pub);
log("[PubKey]");
log("pubkey_to_addr ->" + pubKeyAddr);
let pub_point = hexa_to_point(pubKeyAddr); 
log("[TEST] hexa_to_point(pubKeyAddr) = " + pub_point);

//const hex = "01c0";        // např. bod [1,192]
const addr_long1 = hexa_to_bech32(pubKeyAddr, "a"); //a1
log("hexa_to_bech32: " + addr_long1 + " (6-character checksum)");

//const addr1 = hexa_to_toy32(pubKeyAddr, "a"); //a1
const addr1 = hexa_to_toy32(pubKeyAddr); //stačí bez prefixu

log("bech32_toy: " + addr1);
let test_hex = toy32_to_hexa(addr1);
log("[TEST] toy32_to_hexa: " + test_hex);

log("========================="); 
log("[Miner]");

if (sessionData.mdel > 100) {
   log("Varování: Miner delay (" + sessionData.mdel + ") je příliš vysoký!");
} else {
   log("Miner delay: " + sessionData.mdel);
}

// Např. window.myCurrentPrivKey = sessionData.k1;
//================== end ===========================
});
})();
</script>
</div>