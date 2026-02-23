// js/ash32.js
// ASH32 = Agama simple hash 32 bit

const ASH32_VER = "0.1 | 2026/02";


(function () {

    // Pomocná funkce pro rotaci vlevo u 8-bitového čísla
    function rol8(x, r) {
        return ((x << r) | (x >>> (8 - r))) & 0xFF;
    }

    function ASH32(input) {
        let data;

        // --- Konverze vstupu na bajty ---
        if (typeof input === "string") {
            data = Array.from(input, c => c.charCodeAt(0) & 0xFF);
        } else if (input instanceof Uint8Array) {
            data = Array.from(input);
        } else if (Array.isArray(input)) {
            data = input.slice();
        } else {
            throw new Error("Unsupported input type");
        }

        // --- Padding (doplnění na násobek 4 bajtů / 32 bitů) ---
        const originalLen = data.length;
        data.push(0x80); // Přidání 1 bitu (formou bajtu)

        // Potřebujeme místo pro délku (4 bajty na konci)
        // Zarovnáme tak, aby (data.length + 4) % 4 == 0
        while ((data.length + 4) % 4 !== 0) {
            data.push(0x00);
        }

        // Přidání délky původního vstupu (32-bit big endian)
        data.push((originalLen >>> 24) & 0xFF);
        data.push((originalLen >>> 16) & 0xFF);
        data.push((originalLen >>> 8) & 0xFF);
        data.push(originalLen & 0xFF);

        // --- Inicializační vektory (IV) ---
        const IV = [0x6a, 0xbb, 0x3c, 0xa5, 0x51, 0x9b, 0x05, 0x1f];
        
        // Inicializace registrů
        let A = IV[0];
        let B = IV[1];
        let C = IV[2];
        let D = IV[3];

        // --- Hlavní smyčka přes 32-bitové bloky ---
        for (let i = 0; i < data.length; i += 4) {
            const m0 = data[i];
            const m1 = data[i + 1];
            const m2 = data[i + 2];
            const m3 = data[i + 3];

            // Mixování vstupu do registrů
            A ^= m0;
            B ^= m1;
            C ^= m2;
            D ^= m3;

            // Transformační kroky (zatím necháváme 16 průchodů)
            for (let j = 0; j < 16; j++) {
                // Nelineární mixování a rotace
                A = (A + IV[j % IV.length]) & 0xFF;
                B ^= rol8(D, 3);
                C ^= rol8(A, 2);
                D = (D + B) & 0xFF;

                // Lavinový efekt - vzájemné ovlivnění
                A ^= B;
                B ^= C;
                C ^= D;
                D ^= A;

                // Permutace registrů (posun)
                [A, B, C, D] = [B, C, D, A];
            }
        }

        // --- Finální složení do 32-bitového čísla ---
        // Používáme >>> 0 pro vynucení unsigned 32-bit integeru
        return ((A << 24) | (B << 16) | (C << 8) | D) >>> 0;
    }

    // Pomocné formátovací funkce
    function hex32(v) {
        return (v >>> 0).toString(16).padStart(8, "0");
    }

    function bin32(v) {
        return (v >>> 0).toString(2).padStart(32, "0");
    }

    window.ASH32 = ASH32;
    window.hex32 = hex32;
    window.bin32 = bin32;
    window.ASH32_VER = ASH32_VER;
})();