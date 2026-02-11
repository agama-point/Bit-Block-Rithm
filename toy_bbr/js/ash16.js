// js/ash16.js
// mini Merkle–Damgård
// ASH16 = Agama simple hash 16 bit

(function () {

    function rol8(x, r) {
        return ((x << r) | (x >>> (8 - r))) & 0xff;
    }

    function padData(data) {
        const originalLen = data.length;

        let padded = Array.from(data);
        padded.push(0x80);

        while ((padded.length + 2) % 2 !== 0) {
            padded.push(0x00);
        }

        padded.push((originalLen >>> 8) & 0xff);
        padded.push(originalLen & 0xff);

        return padded;
    }

    function ASH16(input, debug = false) {
        let data;

        if (typeof input === "string") {
            data = Array.from(input, c => c.charCodeAt(0) & 0xff);
        } else if (typeof input === "number") {
            data = [];
            let v = input >>> 0;
            if (v === 0) data = [0];
            while (v > 0) {
                data.unshift(v & 0xff);
                v >>>= 8;
            }
        } else if (Array.isArray(input)) {
            data = input.slice();
        } else {
            throw new Error("Unsupported input type");
        }

        const IV8 = [
            0x6a, 0xbb, 0x3c, 0xa5,
            0x51, 0x9b, 0x05, 0x1f
        ];

        data = padData(data);

        let A = IV8[0];
        let B = IV8[1];
        let C = IV8[2];

        for (let blockIndex = 0; blockIndex < data.length; blockIndex += 2) {
            const m0 = data[blockIndex];
            const m1 = data[blockIndex + 1];

            A ^= m0;
            B ^= m1;

            for (let i = 0; i < 7; i++) {
                A ^= IV8[(i + blockIndex) % IV8.length];
                B ^= rol8(C, 2);
                C ^= rol8(A, 3);

                A ^= B;
                B ^= C;
                C ^= A;

                [A, B, C] = [B, C, A];
            }
        }

        return ((A << 8) | B) & 0xffff;
    }

    function bin16(v) {
        return v.toString(2).padStart(16, "0");
    }

    function hex16(v) {
        return "0x" + v.toString(16).padStart(4, "0");
    }

    // expose globally
    window.ASH16 = ASH16;
    window.bin16 = bin16;
    window.hex16 = hex16;

})();
