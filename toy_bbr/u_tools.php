<div style="background:#000;color:#0f0;padding:10px;margin:20px 0;font-family:monospace;border:1px solid #0f0;">
    <h3>TOOLS</h3>

    <input type="text" id="tool-input" maxlength="60" style="width:250px; font-family:monospace;" placeholder="Enter hex or data" />

    <div style="margin-top:10px;">
        <button id="btn-hash24">HASH24</button>
        <button id="btn-hex2addr">HEX &gt; ADDR</button>
        <button id="btn-addr2hex">ADDR &gt; HEX</button>
    </div>

    <div id="tool-output" style="margin-top:15px; white-space: pre-wrap;"></div>

</div>

<script>
(function(){
    $(document).ready(function() {

        const $input = $("#tool-input");
        const $output = $("#tool-output");

        function log(txt) {
            $output.text(txt);
        }

        // HASH24 button
        $("#btn-hash24").click(function() {
            let val = $input.val().trim();
            if(!val) { log("Enter input!"); return; }

            if(typeof window.ASH24 !== "function") { log("Error: ASH24 is not defined!"); return; }

            let raw = window.ASH24(val);        // use correct function from library
            let hexa = window.hex24(raw);       // convert raw to hex (if needed)
            log("[HASH24] RAW: " + raw + " | HEX: " + hexa);
        });

        // HEX > ADDR button
        $("#btn-hex2addr").click(function() {
            let val = $input.val().trim().toLowerCase();
            if(!val) { log("Enter hex!"); return; }

            // valid hex characters only
            if(!/^[0-9a-f]*$/.test(val)) { log("Error: Invalid hex string!"); return; }

            try {
                let addr = hexa_to_toy32(val); // without 0x prefix
                log("[HEX>ADDR] " + val + " -> " + addr);
            } catch(e) {
                log("Error converting HEX -> ADDR: " + e.message);
            }
        });

        // ADDR > HEX button
        $("#btn-addr2hex").click(function() {
            let val = $input.val().trim();
            if(!val) { log("Enter toy32 addr!"); return; }

            try {
                let hex = toy32_to_hexa(val);
                log("[ADDR>HEX] " + val + " -> " + hex);
            } catch(e) {
                log("Error converting ADDR -> HEX: " + e.message);
            }
        });

    });
})();
</script>
