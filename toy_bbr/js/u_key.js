/* ====== js/u_key.js ====== */
new p5(pInst => {
    let kSlider;
    
    // Parametry ECC
    const pVal = 251; 
    const a = 0;
    const Gx = 1;
    const Gy = 192;

    pInst.setup = function() {
        // Zkontrolujeme, zda div existuje
        let container = document.getElementById('p5-holder');
        let cnv = pInst.createCanvas(600, 650);
        
        if (container) {
            cnv.parent('p5-holder');
        }

        // Slider
        kSlider = pInst.createSlider(1, pVal, 1);
        // POZOR: kSlider.position je absolutní vůči oknu prohlížeče.
        // Pro testování ho zkusme dát na fixní souřadnici:
        kSlider.position(20, 620);
        kSlider.style('width', '510px');
    };

    pInst.draw = function() {
        pInst.background(20);

        const margin = 50;
        const size = 500;
        const cell = size / (pVal - 1);

        // Mřížka
        pInst.stroke(40, 60, 40);
        pInst.line(margin, margin, margin + size, margin);
        pInst.line(margin, margin + size, margin + size, margin + size);
        pInst.line(margin, margin, margin, margin + size);
        pInst.line(margin + size, margin, margin + size, margin + size);

        let currentX = Gx;
        let currentY = Gy;
        let targetK = kSlider.value();

        let prevScreenX = null;
        let prevScreenY = null;

        for (let i = 1; i <= targetK; i++) {
            const screenX = margin + currentX * cell;
            const screenY = margin + (pVal - 1 - currentY) * cell;

            if (prevScreenX !== null) {
                pInst.stroke(0, 250, 0, 50);
                pInst.line(prevScreenX, prevScreenY, screenX, screenY);
            }

            pInst.noStroke();
            if (i === 1) pInst.fill(0, 255, 0); 
            else if (i === targetK) pInst.fill(255, 0, 0); 
            else pInst.fill(0, 200, 0, 150);

            pInst.circle(screenX, screenY, i === targetK ? 8 : 4);

            prevScreenX = screenX;
            prevScreenY = screenY;

            if (i < targetK) {
                try {
                    // Použijeme window.funkce pro jistotu, že voláme globální verzi
                    if (i === 1) {
                        if (typeof window.point_doubling === "function") {
                            [currentX, currentY] = window.point_doubling(currentX, currentY, a, pVal);
                        }
                    } else {
                        if (typeof window.point_adding === "function") {
                            [currentX, currentY] = window.point_adding(currentX, currentY, Gx, Gy, pVal);
                        }
                    }
                } catch (e) {
                    console.error("Chyba ve výpočtu ECC:", e);
                    break;
                }
            }
        }

        // Info texty
        pInst.fill(0, 255, 0);
        pInst.textSize(16);
        pInst.text(`k = ${targetK} | Point: [${currentX}, ${currentY}]`, 50, 25);
    };
});