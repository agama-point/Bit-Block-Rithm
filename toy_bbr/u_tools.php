<div style="background:#000;color:#0f0;padding:10px;margin:20px 0;font-family:monospace;border:1px solid #0f0;">
    <h3>TOOLS</h3>

    <input type="text" id="tool-input" maxlength="25" style="width:250px; font-family:monospace;" placeholder="Zadejte hex nebo data" />

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

        // HASH24 tlačítko
        $("#btn-hash24").click(function() {
    let val = $input.val().trim();
    if(!val) { log("Zadejte vstup!"); return; }

    if(typeof window.ASH24 !== "function") { log("Chyba: ASH24 není definováno!"); return; }

    let raw = window.ASH24(val);        // použij správnou funkci z knihovny
    let hexa = window.hex24(raw);       // převede raw na hex (pokud chceš)
    log("[HASH24] RAW: " + raw + " | HEX: " + hexa);
});


        // HEX > ADDR tlačítko
       // HEX > ADDR tlačítko
$("#btn-hex2addr").click(function() {
    let val = $input.val().trim().toLowerCase();
    if(!val) { log("Zadejte hex!"); return; }

    // jen platné hex znaky
    if(!/^[0-9a-f]*$/.test(val)) { log("Chyba: Neplatný hex string!"); return; }

    try {
        let addr = hexa_to_toy32(val); // bez prefixu 0x
        log("[HEX>ADDR] " + val + " -> " + addr);
    } catch(e) {
        log("Chyba při konverzi HEX -> ADDR: " + e.message);
    }
});

// ADDR > HEX tlačítko
$("#btn-addr2hex").click(function() {
    let val = $input.val().trim();
    if(!val) { log("Zadejte toy32 addr!"); return; }

    try {
        let hex = toy32_to_hexa(val);
        log("[ADDR>HEX] " + val + " -> " + hex);
    } catch(e) {
        log("Chyba při konverzi ADDR -> HEX: " + e.message);
    }
});


    });
})();
</script>
