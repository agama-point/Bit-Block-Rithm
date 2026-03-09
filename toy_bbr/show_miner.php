<?php
$currentDelay = $_SESSION['minerdelay'] ?? 5; 
?>

<h3 class="col_gre">MINER DEBUG</h3>
<pre id="log-system" class="log"></pre>

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
    });
})();
</script>

