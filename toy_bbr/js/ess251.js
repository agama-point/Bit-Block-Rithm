// ESS251: Elliptic Signature Scheme for p=251
// Educational ECC toy library

const ESS251_VER = "0.21 | 2026/02";
window.ESS251_VER = ESS251_VER;

const P_MOD = 251;
const A_PARAM = 0;
const B_PARAM = 7;
const G_POINT = [1, 192];
const ORDER_N = 252;

// --- ALWAYS NON-NEGATIVE MODULO ---
function modN(n, m) {
    return ((n % m) + m) % m;
}

// Modular inverse
function inv_mod(x, mod_val) {
    x = modN(x, mod_val);
    for (let i = 1; i < mod_val; i++) {
        if ((x * i) % mod_val === 1) return i;
    }
    return null;
}

// Point doubling
function point_doubling(P, a = A_PARAM, p = P_MOD) {
    if (P === null) return null;
    let [x, y] = P;
    if (y === 0) return null;
    let num = modN(3 * x * x + a, p);
    let den = inv_mod(2 * y, p);
    if (den === null) return null;
    let slope = modN(num * den, p);
    let x3 = modN(slope * slope - 2 * x, p);
    let y3 = modN(slope * (x - x3) - y, p);
    return [x3, y3];
}

// Point addition
function point_adding(P1, P2, p = P_MOD, a = A_PARAM) {
    if (P1 === null) return P2;
    if (P2 === null) return P1;
    let [x1, y1] = P1;
    let [x2, y2] = P2;
    if (x1 === x2 && y1 !== y2) return null;
    if (x1 === x2) return point_doubling(P1, a, p);

    let num = modN(y2 - y1, p);
    let den = inv_mod(modN(x2 - x1, p), p);
    if (den === null) return null;
    let slope = modN(num * den, p);
    let x3 = modN(slope * slope - x1 - x2, p);
    let y3 = modN(slope * (x1 - x3) - y1, p);
    return [x3, y3];
}

// Scalar multiplication
function scalar_mult(k, P, a = A_PARAM, p = P_MOD, n = ORDER_N) {
    let result = null;
    let addend = P;
    k = modN(k, n);
    while (k > 0) {
        if (k & 1) result = point_adding(result, addend, p, a);
        addend = point_doubling(addend, a, p);
        k >>= 1;
    }
    return result;
}

// Sign message hash
function signToy(private_key, msg_hash, debug = false) {
    if (debug) console.log("\n[DEBUG-SIGN] Starting signing process...");

    let k_nonce = modN(msg_hash ^ 0x55, ORDER_N);
    if (k_nonce === 0) k_nonce = 1;

    let R_point = scalar_mult(k_nonce, G_POINT, A_PARAM, P_MOD, ORDER_N);
    let r = R_point[0];
    let e = modN(msg_hash, ORDER_N);
    let s = modN(k_nonce + e * private_key, ORDER_N);

    if (debug) {
        console.log(`[DEBUG-SIGN] Nonce k: ${k_nonce}`);
        console.log(`[DEBUG-SIGN] R-Point (k*G): ${R_point}`);
        console.log(`[DEBUG-SIGN] Signature components: r=${r}, s=${s}`);
    }

    return { r: r, s: s, R_point: R_point };
}

// Verify signature
function verifyToy(public_key_point, msg_hash, signature, debug = false) {
    if (debug) console.log("\n[DEBUG-VERIFY] Starting verification process...");

    let { r, s, R_point } = signature;
    let e = modN(msg_hash, ORDER_N);

    let L = scalar_mult(s, G_POINT).map(v => modN(v, P_MOD));
    let e_Pub = scalar_mult(e, public_key_point).map(v => modN(v, P_MOD));
    let P = point_adding(R_point, e_Pub).map(v => modN(v, P_MOD));

    if (debug) {
        console.log(`[DEBUG-VERIFY] Challenge e: ${e}`);
        console.log(`[DEBUG-VERIFY] L = s*G: [${L}]`);
        console.log(`[DEBUG-VERIFY] P = R + e*PubKey: [${P}]`);
    }

    return L !== null && P !== null && L[0] === P[0] && L[1] === P[1];
}

function sig_to_hexa(signature){
    let rHex = signature.r.toString(16).padStart(2,'0');
    let sHex = signature.s.toString(16).padStart(2,'0');
    return rHex + sHex;
}

// Pubkey hexa
function pubkey_to_addr(pub){
        return pub[0].toString(16).padStart(2,'0') +
               pub[1].toString(16).padStart(2,'0');
    }

function hexa_to_point(hex) {
    if (hex.length !== 4) {
        throw new Error("Hex string must have length 4.");
    }
    const x = parseInt(hex.slice(0, 2), 16);
    const y = parseInt(hex.slice(2, 4), 16);

    if (Number.isNaN(x) || Number.isNaN(y)) {
        throw new Error("Invalid hex string.");
    }
    return [x, y];
}


