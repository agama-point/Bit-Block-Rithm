// --- GLOBÁLNÍ PROMĚNNÉ ---
var toggRun1, toggFull, toggPal;
var homeBtn, pinBtn, jacobBtn, oscsphBtn, gearsBtn, c64sBtn, go10Btn;
var radioCol, sl1, t0;
var allWidgets = [];

// Funkce pro změnu stylu - VOLANÁ ZVENKU (z HTML)
function setColorStyle(cs) {
    if (typeof colorsDarkGreenMode === 'undefined') return; // pojistka

    if (cs === "dark")  currentColors = colorsDarkGreenMode;
    if (cs === "light") currentColors = colorsLightBlaWhiMode;
    if (cs === "color") currentColors = colorsColorMode;
    
    // Nastavení RadioGroupu, aby odpovídal stavu z lišty
    if (radioCol) {
        if (cs === "dark") radioCol.buttons[0].checked = true;
        if (cs === "light") radioCol.buttons[1].checked = true;
        if (cs === "color") radioCol.buttons[2].checked = true;
        // Ostatní odškrtneme
        radioCol.buttons.forEach(b => { if(b.value !== cs) b.checked = false; });
    }

    // Aktualizace barev p5.js widgetů
    allWidgets.forEach(w => {
        if (w && w.changeColorStyle) w.changeColorStyle();
    });
}

// Prototypy pro GUI
if (typeof RadioButton !== 'undefined') {
    RadioButton.prototype.changeColorStyle = function() {
        this.strokeColor = color(currentColors[2]);
        this.fillColor   = color(currentColors[1]);
        this.activeColor = color(currentColors[3]);
        this.labelColor  = color(currentColors[3]);
    };
}

// --- SETUP SKELETONU ---
function skeletonSetup() {
    t0 = new Template();
    let x0L = 220;
    let ry = t0.yC + 50;

    homeBtn   = new ButtonBox(t0.btnX0, 20, t0.btnW, t0.btnH, "Home");
    oscsphBtn = new ButtonBox(t0.btnX0, 20 + t0.btnD, t0.btnW, t0.btnH, "osc.sphere");
    gearsBtn  = new ButtonBox(t0.btnX0, 20 + t0.btnD*3, t0.btnW, t0.btnH, "gears");

    toggRun1 = new CheckBox(t0.xC + x0L, t0.yC - 200, t0.btnH, true);
    toggRun1.textLabel("RUN1");
    toggFull = new CheckBox(t0.xC + x0L, t0.yC - 150, t0.btnH, false);
    toggFull.textLabel("Screen size");
    toggPal  = new CheckBox(t0.xC + x0L, t0.yC - 100, t0.btnH, false);
    toggPal.textLabel("Palette");

    // RadioCol necháme jen jako vizuální info, nebudeme ho "mačkat" v mousePressed
    radioCol = new RadioGroup();
    radioCol.addButton(t0.xC + x0L + 25, ry,      25, "dark green", true, "dark")
            .addButton(t0.xC + x0L + 25, ry + 35, 25, "light B&W", false, "light")
            .addButton(t0.xC + x0L + 25, ry + 70, 25, "colorfull", false, "color");

    sl1 = new SimpleLabel(t0.xC + 200, t0.h - 50, p5.prototype.VERSION);

    allWidgets = [t0, toggRun1, toggFull, toggPal, radioCol, sl1, homeBtn, oscsphBtn, gearsBtn];

    // Výchozí nastavení barvy podle body class (pokud existuje)
    let initial = document.body.classList.contains('light-mode') ? 'light' : 'dark';
    setColorStyle(initial);
}

function skeletonDraw() {
    allWidgets.forEach(w => { if (w && w.draw) w.draw(); });
}

function skeletonMousePressed() {
    // Ovládání checkboxů
    if (toggRun1 && toggRun1.pressed()) { /* run logic */ }
    if (toggFull && toggFull.pressed()) { fullscreen(!fullscreen()); applyScreenMode(); }

    // DŮLEŽITÉ: Zde jsme ODSTRANILI radioCol.pressed(), 
    // aby klikání do canvasu neměnilo barvy zpětně.

    if (homeBtn && homeBtn.pressed()) window.location.href = "index.html";
    if (gearsBtn && gearsBtn.pressed()) window.location.href = "gears3x3.html";
}