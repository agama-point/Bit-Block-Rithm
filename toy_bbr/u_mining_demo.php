<br />

<div class="flex-wrap">

<div class="flex-left">

<h3 class="col_ora">Mining Demo — ASH24</h3>
Base string:
<input type="text" id="base24" value="AGAMA">
<button id="start24">START</button>
<pre id="output24" class="log"></pre>

</div>

<div class="flex-right">

<h3 class="col_ora">Mining Demo — ASH32</h3>
Base string:
<input type="text" id="base32" value="AGAMA">
<button id="start32">START</button>
<pre id="output32" class="log"></pre>

</div>

</div>


<script>

let running24 = false;
let running32 = false;


// =========================
// UNIVERSAL MINER
// =========================

function mineHash(prefix, difficulty, hashFunc, hexFunc, binFunc, callback) {

    let nonce = 1;
    let total = 0;

    const target = "0".repeat(difficulty);
    const startTime = Date.now();

    function step() {
        for (let i = 0; i < 10000; i++) {
            const candidate = prefix + nonce;

            const hash = hashFunc(candidate);
            const hex = hexFunc(hash);
            total++;

            if (hex.startsWith(target)) {
                const bin = binFunc(hash);
                const elapsed = ((Date.now() - startTime) / 1000).toFixed(2);

                callback({ candidate, hex, bin, elapsed, total});
                return;
            }
            nonce++;
        }
        setTimeout(step, 0);
    }
    step();
}


////////////////////////////
// ASH24
////////////////////////////

$("#start24").click(function () {
    if (running24) return;
    running24 = true;
    $("#output24").text("");
    const base = $("#base24").val();

    let difficulty = 1;
    let totalCount24 = 0;
    let startTime24 = Date.now();

    function next() {
        if (difficulty > 5) {
            const time24 = ((Date.now() - startTime24) / 1000).toFixed(2);
            const rate24 = (totalCount24 / time24 / 1e6).toFixed(3);
            $("#output24").append(
                "Total tested: <span class='col_ora'>" + totalCount24 + "</span>\n" +
                "Total time: " + time24 + " s\n" +
                "Hashrate: " + rate24 + " MH/s\n"
            );

            running24 = false;
            return;
        }

        mineHash(base, difficulty, ASH24, hex24, bin24,
          function (result) {
                totalCount24 += result.total;
                const line =
                    result.candidate.padEnd(18, " ") + " *   " +
                    result.hex.padEnd(8, " ") + "  " +
                    result.bin + "   " +
                    result.elapsed + "s";

                $("#output24").append(line + "\n");
                difficulty++;
                next();
            }
        );
    }
    next();
});


////////////////////////////
// ASH32
////////////////////////////

$("#start32").click(function () {
    if (running32) return;
    running32 = true;
    $("#output32").text("");
    const base = $("#base32").val();

    let difficulty = 1;
    let totalCount32 = 0;
    let startTime32 = Date.now();

    function next() {
        if (difficulty > 6) {
            const time32 = ((Date.now() - startTime32) / 1000).toFixed(2);
            const rate32 = (totalCount32 / time32 / 1e6).toFixed(3);

            $("#output32").append(
                "Total tested: <span class='col_ora'>" + totalCount32 + "</span>\n" +
                "Total time: " + time32 + " s\n" +
                "Hashrate: " + rate32 + " MH/s\n"
            );

            running32 = false;
            return;
        }

        mineHash(base, difficulty, ASH32, hex32, bin32,
            function (result) {
                totalCount32 += result.total;
                const line =
                    result.candidate.padEnd(18, " ") + " *   " +
                    result.hex.padEnd(10, " ") + "  " +
                    result.bin + "   " +
                    result.elapsed + "s";
                $("#output32").append(line + "\n");
                difficulty++;
                next();
            }
        );
    }
    next();
});
</script>