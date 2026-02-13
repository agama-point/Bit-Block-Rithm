/* ====== js/u_key251.js ====== */
new p5(pInst => {
    const P_MOD = 251;
    const G_POINT = [1, 192];
    const A_PARAM = 0;
    
    let targetK = 1;

    pInst.setup = function() {
        let cnv = pInst.createCanvas(600, 600);
        cnv.parent('p5-holder');

        const sliderEl = document.getElementById('ecc-slider');
        const inputEl = document.getElementById('new-k1'); // Váš číselný input
        const keySpan = document.getElementById('key');   // Žluté číslo u slideru

        if (sliderEl) {
            sliderEl.addEventListener('input', (e) => {
                targetK = parseInt(e.target.value);
                
                // 1. Aktualizace žlutého čísla u slideru
                if (keySpan) keySpan.innerText = targetK;
                
                // 2. Aktualizace inputu pro uložení do DB
                if (inputEl) inputEl.value = targetK;
            });
            
            // Počáteční synchronizace (pokud už je něco v DB, nastavíme slider)
            if (inputEl && inputEl.value > 0) {
                targetK = parseInt(inputEl.value);
                sliderEl.value = targetK;
                if (keySpan) keySpan.innerText = targetK;
            } else {
                targetK = parseInt(sliderEl.value);
            }
        }
    };

    pInst.draw = function() {
        pInst.background(20);

        const margin = 50;
        const size = 500;
        const cell = size / (P_MOD - 1);

        // Mřížka
        pInst.stroke(40, 60, 40);
        pInst.strokeWeight(1);
        pInst.line(margin, margin, margin + size, margin);
        pInst.line(margin, margin + size, margin + size, margin + size);
        pInst.line(margin, margin, margin, margin + size);
        pInst.line(margin + size, margin, margin + size, margin + size);

        let currentPoint = [G_POINT[0], G_POINT[1]];
        let prevScreenX = null;
        let prevScreenY = null;

        for (let i = 1; i <= targetK; i++) {
            if (currentPoint === null) break;

            const screenX = margin + currentPoint[0] * cell;
            const screenY = margin + (P_MOD - 1 - currentPoint[1]) * cell;

            if (prevScreenX !== null) {
                pInst.stroke(0, 250, 0, 150);
                pInst.line(prevScreenX, prevScreenY, screenX, screenY);
            }

            pInst.noStroke();
            if (i === 1) pInst.fill(0, 255, 0); 
            else if (i === targetK) pInst.fill(255, 0, 0); 
            else pInst.fill(0, 200, 0, 150);

            pInst.circle(screenX, screenY, (i === targetK) ? 10 : 5);

            prevScreenX = screenX;
            prevScreenY = screenY;

            if (i < targetK) {
                try {
                    if (i === 1) {
                        currentPoint = window.point_doubling(currentPoint, A_PARAM, P_MOD);
                    } else {
                        currentPoint = window.point_adding(currentPoint, G_POINT, P_MOD, A_PARAM);
                    }
                } catch (e) { break; }
            }
        }

        // Info v plátně
        pInst.fill(0, 255, 0);
        pInst.textSize(16);
        pInst.textAlign(pInst.LEFT, pInst.TOP);
        let coordText = currentPoint ? `[${currentPoint[0]}, ${currentPoint[1]}]` : "Inf";
        pInst.text(`Public Key K = k * G: ${coordText}`, margin, 15);
    };
});