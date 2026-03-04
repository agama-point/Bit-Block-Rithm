<div style="background:#000;color:#0f0;padding:10px;margin:20px 0;font-family:monospace;border:1px solid #0f0;">
    <h3>ASH32</h3>

    <input type="text" id="tool-input2" maxlength="60" style="width:250px; font-family:monospace;" placeholder="Enter hex or data" />

    <div style="margin-top:10px;">
        <button id="btn-hash32">HASH32</button>
        <button id="btn-hex2addrx"> ... </button>
        
    </div>

    <div id="tool-output2" style="margin-top:15px; white-space: pre-wrap;"></div>

</div>

<script>
(function(){
    $(document).ready(function() {

        const $input = $("#tool-input2");
        const $output = $("#tool-output2");

        function log2(txt) {
            $output.text(txt);
        }

        // HASH32 button
        $("#btn-hash32").click(function() {
            let val = $input.val().trim();
            if(!val) { log2("Enter input!"); return; }

            if(typeof window.ASH32 !== "function") { log2("Error: ASH32 is not defined!"); return; }

            let raw = window.ASH32(val);        // use correct function from library
            let hexa = window.hex32(raw);       // convert raw to hex (if needed)
            log2("[HASH32] RAW: " + raw + " | HEX: " + hexa);
        });

        // HEX > ADDR button
        $("#btn-hex2addr").click(function() {
            let val = $input.val().trim().toLowerCase();
            if(!val) { log2("Enter hex!"); return; }

            // valid hex characters only
            if(!/^[0-9a-f]*$/.test(val)) { log2("Error: Invalid hex string!"); return; }

            try {
                let addr = hexa_to_toy32(val); // without 0x prefix
                log2("[HEX>ADDR] " + val + " -> " + addr);
            } catch(e) {
                log2("Error converting HEX -> ADDR: " + e.message);
            }
        });

        // ADDR > HEX button
        $("#btn-addr2hex").click(function() {
            let val = $input.val().trim();
            if(!val) { log2("Enter toy32 addr!"); return; }

            try {
                let hex = toy32_to_hexa(val);
                log2("[ADDR>HEX] " + val + " -> " + hex);
            } catch(e) {
                log2("Error converting ADDR -> HEX: " + e.message);
            }
        });

    });
})();
</script>
