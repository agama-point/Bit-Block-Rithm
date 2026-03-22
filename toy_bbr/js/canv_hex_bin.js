function drawHexCanvas(hexStr, options = {}) {
    const size = options.size || 10;
    const gap  = options.gap  || 3;
    const font = options.font || 12;

    // Detekce režimu (hledáme třídu na body)
    const isLightMode = document.body.classList.contains('light-mode');

    // Barvy pro text pod čtverečky
    const textColor = isLightMode ? "#000" : "#bf5af2"; // Tmavá vs. Jasná fialová
    
    // Barvy pro bity (upravíme i nuly, aby v light modu nesvítily moc černě)
    const colorBit1 = "#0a0"; 
    const colorBit0 = isLightMode ? "#ddd" : "#222"; 

    hexStr = hexStr.toUpperCase();
    const cols = hexStr.length;
    const rows = 4;

    const w = cols * (size + gap);
    const h = rows * (size + gap) + font + 5;

    const canvas = document.createElement("canvas");
    canvas.width = w;
    canvas.height = h;

    const ctx = canvas.getContext("2d");

    ctx.font = font + "px monospace";
    ctx.textAlign = "center";
    ctx.textBaseline = "top";

    for (let c = 0; c < cols; c++) {
        const hex = hexStr[c];
        const val = parseInt(hex, 16);
        if (isNaN(val)) continue;

        for (let r = 0; r < 4; r++) {
            const bit = (val >> (3 - r)) & 1;
            const x = c * (size + gap);
            const y = r * (size + gap);

            // Výplň bitu (nuly jsou v light modu světle šedé, v dark modu tmavé)
            ctx.fillStyle = bit ? colorBit1 : colorBit0;
            ctx.fillRect(x, y, size, size);
            
            // Jemná mřížka
            ctx.strokeStyle = isLightMode ? "rgba(0,0,0,0.05)" : "rgba(255,255,255,0.05)";
            ctx.strokeRect(x, y, size, size);
        }

        const tx = c * (size + gap) + size / 2;
        const ty = rows * (size + gap) + 2;

        // --- Fialová barva podle režimu ---
        ctx.fillStyle = textColor;
        ctx.fillText(hex, tx, ty);
    }

    return canvas;
}