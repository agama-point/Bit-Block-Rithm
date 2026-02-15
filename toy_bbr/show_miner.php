<?php
// Nepřepisujme session zde! Jen načíst
$currentDelay = $_SESSION['minerdelay'] ?? 5; 
?>

<div style="background:#000;color:#0f0;padding:10px;margin:20px 0;font-family:monospace;border:1px solid #0f0;">
<h3>MINER DEBUG</h3>

<pre id="log-system" style="white-space: pre-wrap; margin: 0;"></pre>

<script>
(function() {
    document.addEventListener("DOMContentLoaded", function() {
        
        // Přelití dat ze Session do JS objektu
        const sessionData = {
            nick: <?php echo json_encode($_SESSION['nick'] ?? 'Anonym'); ?>,
            k1:   <?php echo json_encode($_SESSION['k1'] ?? 111); ?>,
            mdel: <?php echo json_encode($currentDelay); ?>
        };

        const logElement = document.getElementById("log-system");
        function log(txt) {
            if (logElement) {
                logElement.textContent += txt + "\n";
            } else {
                console.error("Chyba: Element #log-system nebyl nalezen!");
            }
        }

        // ======================= start ====================
        log("===== SESSION =====");
        log("user: " + sessionData.nick);
        
        let priv = sessionData.k1; 
        // log("Používám privátní klíč (k1): " + priv);

        if (sessionData.mdel > 100) {
           log("Varování: Miner delay (" + sessionData.mdel + ") je příliš vysoký!");
        } else {
           log("Miner delay: " + sessionData.mdel + " s");
        }
        
        log("=========================");
        // ======================= end ====================
    });
})();
</script>
</div>
