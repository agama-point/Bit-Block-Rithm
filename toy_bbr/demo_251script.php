<h3 class="col_ora">P2PK / P2PKH Demo</h2>
Alice → Bob [1] | ESS251 + ASH24<br /><br />
<button id="run">Run Demo</button>
<button id="clear">Clear</button>
<pre id="demoScr" class="log">Click "Run Demo" to start simulation...</pre>

<script>
/* --- Helper Functions --- */
function log(t) { $("#demoScr").append(t + "\n"); }

function H(str) {
    let h = ASH24(str);
    log("  [ Hashing Input: '" + str + "' ]");
    log("  ASH24 dec = " + h);
    log("  ASH24 hex = " + hex24(h));
    log("  Curve mod (h % 251) = " + (h % 251));
    return h; // <- full 24-bit hash, not modulo
}

$("#clear").click(function(){ $("#demoScr").text(""); });

$("#run").click(function(){
    $(".log").text("");

    log("===== ESS251 BITCOIN TRANSACTION DEMO =====");
    log("Hash Engine: ASH24 (24-bit)");
    log("");

    /* [ Setup Elliptic Curve Parameters ] */
    log("[ Setup Elliptic Curve Parameters ]");
    log("Curve: y^2 = x^3 + 7 mod 251");
    log("G (Generator) = " + ECC_PARAMS.G);
    log("n (Order)     = " + ECC_PARAMS.n);
    log("");

    /* [ Key Generation ] */
    log("[ Key Generation ]");
    let a = 111; // Alice private key
    let b = 222; // Bob private key

    let A = scalar_mult(a, ECC_PARAMS.G);
    let B = scalar_mult(b, ECC_PARAMS.G);

    log("Alice: priv_a=" + a + " | pub_A=" + A + " (hex:" + pubkey_to_addr(A) + ")");
    log("Bob:   priv_b=" + b + " | pub_B=" + B + " (hex:" + pubkey_to_addr(B) + ")");
    log("");

    /* [ Alice Creates Transaction ] */
    log("[ Alice Creates Transaction ]");
    let tx1_content = pubkey_to_addr(B);
    log("Transaction Content: " + tx1_content);

    let h1 = H(tx1_content);
    log("");

    /* [ Alice Signs Transaction ] */
    log("[ Alice Signs Transaction ]");
    let sigA = signToy(a, h1, false); // <- full hash as input
    let sigA_hex = sig_to_hexa(sigA);

    log("Signature (r, s): r=" + sigA.r + ", s=" + sigA.s);
    //log("Signature RShex: " + sigA_hex);
    log("Signature RShex: <span class='col_ora'>" + sigA_hex + "</span>");
    log("");

    /* [ Creating Bitcoin-like Scripts ] */
    log("[ Creating Bitcoin-like Scripts ]");
    
    let scriptPubKey_P2PK = {
        pub: B,
        op: "OP_CHECKSIG"
    };

    let scriptSig_P2PK = {
        sig: sigA_hex
    };

    log("scriptPubKey (Lock for Bob): " + JSON.stringify(scriptPubKey_P2PK));
    log("scriptSig (Unlock Alice UTXO): " + JSON.stringify(scriptSig_P2PK));
    log("");

    /* [ Network Verification - Alice's Spend ] */
    log("[ Network Verification - Alice's Spend ]");
    let ok1 = verifyToy(A, h1, sigA, false);

    log("Verifying Alice's signature against pub_A...");
    log("RESULT: VERIFY P2PK = " + (ok1 ? "SUCCESS (true) ✅" : "FAILED (false) ❌"));
    log("");

    /* [ Bob Spends Received Funds ] */
    log("[ Bob Spends Received Funds ]");
    let tx2_content = "Bob->Charlie:0.5";
    log("New Transaction Content: " + tx2_content);

    let h2 = H(tx2_content);
    log("");

    log("Bob signs his new transaction...");
    let sigB = signToy(b, h2, false); // <- full hash
    let sigB_hex = sig_to_hexa(sigB);

    log("Signature (r, s): r=" + sigB.r + ", s=" + sigB.s);
    log("Signature RShex: " + sigB_hex);
    log("");

    /* [ Network Verification - Bob's Spend ] */
    log("[ Network Verification - Bob's Spend ]");
    let ok2 = verifyToy(B, h2, sigB, false);

    log("Verifying Bob's signature against pub_B...");
    log("RESULT: VERIFY Bob Spend = " + (ok2 ? "SUCCESS (true) ✅" : "FAILED (false) ❌"));
    log("");
    log("===== END OF DEMO =====");
});
</script>