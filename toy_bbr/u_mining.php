<div class="container">
<?php 
include "u_coinbase.php"; 
?>
</div>
<div class="container">
<?php 
include "show_miner.php"; 
?>
</div>
<hr />
Base string:
<input type="text" id="base" value="AGAMA">
<button id="start">START</button>

<pre id="output"></pre>

<script>
let running = false;

function mine(prefix, difficulty, callback) {

    let nonce = 1;
    const target = "0".repeat(difficulty);
    const startTime = Date.now();

    function step() {

        if (!running) return;

        for (let i = 0; i < 10000; i++) {

            const candidate = prefix + nonce;
            const hash = ASH24(candidate);
            const hex = hex24(hash).substring(2); // bez 0x

            if (hex.startsWith(target)) {

                const bin = bin24(hash);
                const elapsed = ((Date.now() - startTime) / 1000).toFixed(2);

                callback({
                    candidate,
                    hex,
                    bin,
                    elapsed
                });

                return;
            }

            nonce++;
        }

        setTimeout(step, 0);
    }

    step();
}

$("#start").click(function () {

    if (running) return;

    running = true;
    $("#output").text("");

    const base = $("#base").val();
    let difficulty = 1;

    function nextDifficulty() {

        if (difficulty > 5) {
            running = false;
            return;
        }

        mine(base, difficulty, function (result) {

            const line =
                result.candidate.padEnd(18, " ") + " *   " +
                result.hex.padEnd(6, " ") + "  " +
                result.bin + "   " +
                result.elapsed + "s";

            $("#output").append(line + "\n");

            difficulty++;
            nextDifficulty();
        });
    }

    nextDifficulty();
});

</script>
<hr />
<pre>
AGAMA151418        *   000007  000000000000000000000111
0645657            *   00000f  000000000000000000001111 
</pre>

<?php 
include "table_utxo.php"; 
include "table_tx2m.php";

?>