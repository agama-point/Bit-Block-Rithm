<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$mainDbFile = __DIR__ . "/main.db";
$txid = $_GET['txid'] ?? '';

if ($txid === '') { die("Missing txid."); }

try {
    $db = new SQLite3($mainDbFile);
    $stmt = $db->prepare("SELECT * FROM transactions WHERE txid = :txid LIMIT 1");
    $stmt->bindValue(':txid', $txid, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    if (!$row) { die("Transaction not found."); }
} catch (Exception $e) { die("DB error: " . $e->getMessage()); }

$msg_payload = $row['from_addr']."|".$row['prev_txid']."|".$row['to_addr']."|".$row['val2'];
$sig_hex = $row['sig'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TX Analysis - Cryptographic Detail</title>
    <link rel="stylesheet" href="css/bbr.css">
    <script src="js/jquery.min.js"></script>
    <script src='js/ash24.js'></script>
    <script src='js/ess251.js'></script>
    <style>
        body { background-color: #0a0a0a; color: #eee; font-family: 'Courier New', monospace; padding: 20px; font-size: 1em; margin: 0; overflow: hidden; }
        
        
        .container { display: flex; gap: 20px; height: calc(100vh - 100px); }
        
        /* Left panel now has its own scrollbar if content overflows */
        .left-panel { flex: 0 0 580px; display: flex; flex-direction: column; gap: 10px; overflow-y: auto; padding-right: 5px; }
        .right-panel { flex: 1; display: flex; flex-direction: column; }

        .data-box { background: #111; padding: 12px; border-radius: 4px; border: 1px solid #222; line-height: 1.4; margin-bottom: 10px; }
        .val-hl { color: #00ccff; font-weight: bold; }
        
        .explain { background: #111; padding: 15px; border-radius: 5px; border: 1px solid #222; }
        h3 { color: #00ff99; margin: 0 0 15px 0; font-size: 1em; text-transform: uppercase; }

        .row { display: flex; align-items: center; margin-bottom: 8px; gap: 10px; }
        .row label { width: 110px; color: #ccc; font-weight: bold; font-size: 1em; }
        .row input { 
            background: #1a1a1a; border: 1px solid #444; color: #00ff99; 
            padding: 6px 10px; flex: 1; font-family: monospace; font-size: 1em; outline: none;
        }
        .row input:focus { border-color: #00ff99; }
        .row .info { color: #aaa; font-size: 1em; min-width: 140px; }

        #log { 
            background: #000; color: #00ff41; padding: 15px; 
            font-size: 1em; border-radius: 5px; border: 1px solid #333; 
            white-space: pre-wrap; line-height: 1.4; flex-grow: 1; 
            overflow-y: auto;
        }
        
        .btn-action { 
            background: #00ff99; color: #000; border: none; padding: 10px 20px; 
            cursor: pointer; font-weight: bold; text-transform: uppercase; font-size: 0.9em; margin-top: 5px;
        }
        .btn-action:hover { background: #00cc7a; }
        .btn-sign { background: #00ccff; }
        .btn-clear { background: #333; color: #ccc; margin-left: 10px; }

        .status-badge { font-weight: bold; padding: 4px 12px; margin-left: 10px; border-radius: 2px; }
        .ok { border: 1px solid #00ff99; color: #00ff99; }
        .bad { border: 1px solid #ff4136; color: #ff4136; }
    </style>
</head>
<body>

<h1 class="digip">TX Analysis | ID <?= htmlspecialchars($row['txid']) ?></h1>

<div class="container">
    <div class="left-panel">
        <div class="data-box">
            TXID: <?= $row['txid'] ?> | Type: <?= (int)$row['mp'] === 0 ? "coinbase" : "mempool" ?><br>
            From: <span class="val-hl"><?= htmlspecialchars($row['from_addr']) ?></span> | To: <span class="val-hl"><?= htmlspecialchars($row['to_addr']) ?></span><br>
            In: <?= $row['val1'] ?> | Out: <?= $row['val2'] ?> | Change: <?= (int)$row['val1']-(int)$row['val2'] ?>
        </div>

        <div class="explain">
            <h3>1) Verify Section</h3>
            <div class="row">
                <label>Message</label>
                <input type="text" id="in_msg" value="<?= htmlspecialchars($msg_payload) ?>">
                <div id="res_hash" class="info"></div>
            </div>
            <div class="row">
                <label>PubKey</label>
                <input type="text" id="in_addr" value="<?= htmlspecialchars($row['from_addr']) ?>">
                <div id="res_point" class="info"></div>
            </div>
            <div class="row">
                <label>Signature</label>
                <input type="text" id="in_sig" value="<?= htmlspecialchars($sig_hex) ?>">
                <div id="res_rs" class="info"></div>
            </div>
            <button class="btn-action" onclick="doVerify()">Verify Signature</button>
            <span id="status_box"></span>
        </div>

        <div class="explain">
            <h3>2) Sign Section</h3>
            <div class="row">
                <label>PrivKey</label>
                <input type="number" id="in_priv" value="123">
                <div class="info">Deterministic signing</div>
            </div>
            <button class="btn-action btn-sign" onclick="doSign()">Sign Message</button>
            <button class="btn-action btn-clear" onclick="$('#log').text('')">Clear Log</button>
        </div>

        <div class="explain" style="margin-top: 10px; border-color: #333;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; color: #00ccff; font-size: 0.9em;">CRYPTOGRAPHIC SPECIFICATION (ESS251)</h3>
                <button id="btn_toggle_exp" class="btn-action" style="padding: 5px 15px; font-size: 0.7em; margin: 0;">SHOW DETAILS</button>
            </div>

            <div id="exp_content" style="display: none; margin-top: 15px; border-top: 1px solid #222; padding-top: 15px; color: #aaa; line-height: 1.6;">
                <p>
                    This system utilizes <strong>ESS251</strong>, an educational "toy" Elliptic Curve Signature Scheme operating over the finite field $GF(251)$. 
                    Unlike standard ECDSA, this implementation is based on a simplified <strong>Schnorr-like signature</strong> logic.
                </p>
                
                

                <p>
                    <strong>Deterministic Nonce:</strong> The nonce $k$ is derived as 
                    <code style="color: #00ff99;">k = (hash XOR 0x55) mod N</code>. 
                    This ensures consistent signatures and allows for $R$-point reconstruction by the verifier without explicit coordinate transmission.
                </p>

                <p style="font-size: 0.9em; color: #777; border-left: 2px solid #00ccff; padding-left: 10px;">
                    Curve Parameters:<br>
                    Equation: $y^2 = x^3 + 7 \pmod{251}$<br>
                    Generator $G$: $[1, 192]$ | Order $n$: $252$
                </p>
            </div>
        </div>
    </div>

    <div class="right-panel">
        <div id="log"></div>
    </div>
</div>

<script>
function log(txt) { 
    let logEl = document.getElementById("log");
    logEl.innerText += txt + "\n"; 
    logEl.scrollTop = logEl.scrollHeight; 
}

function parseSig(hex) {
    if (hex.length < 4) return { r: 0, s: 0 };
    return { r: parseInt(hex.substring(0, 2), 16), s: parseInt(hex.substring(2, 4), 16) };
}

function updateVisuals() {
    let msg = $("#in_msg").val();
    let h_raw = ASH24(msg);
    $("#res_hash").text(hex24(h_raw) + " (" + h_raw + ")");
    try {
        $("#res_point").text("[" + hexa_to_point($("#in_addr").val()) + "]");
    } catch(e) { $("#res_point").text("[err]"); }
    let sObj = parseSig($("#in_sig").val());
    $("#res_rs").text("[" + sObj.r + "," + sObj.s + "]");
}

function doVerify() {
    $("#status_box").html("");
    let msg = $("#in_msg").val();
    let addr = $("#in_addr").val();
    let sigHex = $("#in_sig").val();

    try {
        let h_raw = ASH24(msg);
        let pubPoint = hexa_to_point(addr);
        let sParts = parseSig(sigHex);

        let k_nonce = modN(h_raw ^ 0x55, ORDER_N);
        if (k_nonce === 0) k_nonce = 1;
        let R_point = scalar_mult(k_nonce, G_POINT);
        let sigObj = { r: sParts.r, s: sParts.s, R_point: R_point };

        log("----- VERIFICATION PROCESS -----");
        log("Message: " + msg);
        log("h_raw: " + h_raw);
        log("PubKey: [" + pubPoint + "]");
        log("Signature (r,s): {" + sigObj.r + ", " + sigObj.s + "}");
        log("Reconstructed R_point: [" + sigObj.R_point + "]");
        log("");
        log("Function: verifyToy(pub, h_raw, sig)");

        let valid = verifyToy(pubPoint, h_raw, sigObj);

        let e = modN(h_raw, ORDER_N);
        let L = scalar_mult(sigObj.s, G_POINT).map(v => modN(v, P_MOD));
        let e_Pub = scalar_mult(e, pubPoint).map(v => modN(v, P_MOD));
        let P = point_adding(sigObj.R_point, e_Pub).map(v => modN(v, P_MOD));

        log("Step 1: Challenge e = hash mod n = " + e);
        log("Step 2: L = s * G = [" + L + "]");
        log("Step 3: P = R + e * PubKey = [" + P + "]");
        log("Output: valid = " + valid);

        if (valid) {
            log("  => L == P  →  Signature is VALID ✅");
            $("#status_box").html('<span class="status-badge ok">VALID ✅</span>');
        } else {
            log("  => L != P  →  Signature is INVALID ❌");
            $("#status_box").html('<span class="status-badge bad">INVALID ❌</span>');
        }
        log("---------------------------------\n");
    } catch(e) { log("!! Error: " + e.message); }
}

function doSign() {
    let msg = $("#in_msg").val();
    let priv = parseInt($("#in_priv").val(), 10);
    if (isNaN(priv)) { log("ERROR: Invalid Private Key."); return; }

    let h_raw = ASH24(msg);
    log("----- SIGNING PROCESS -----");
    log("Message: " + msg + " | Hash: " + h_raw);
    
    let sigObj = signToy(priv, h_raw);
    let sigHex = sig_to_hexa(sigObj);
    let newPubPoint = scalar_mult(priv, G_POINT);
    let newPubHex = pubkey_to_addr(newPubPoint);
    
    log("Result: r = " + sigObj.r + ", s = " + sigObj.s);
    log("Hex Signature: " + sigHex);
    log("Derived PubKey Hex: " + newPubHex);
    log("---------------------------------\n");

    $("#in_sig").val(sigHex);
    $("#in_addr").val(newPubHex);
    updateVisuals();
}

$(document).ready(function() {
    updateVisuals();
    $("#in_msg, #in_addr, #in_sig").on('input', updateVisuals);

    $("#btn_toggle_exp").click(function() {
        const content = $("#exp_content");
        if (content.is(":visible")) {
            content.slideUp();
            $(this).text("SHOW DETAILS");
        } else {
            content.slideDown();
            $(this).text("HIDE DETAILS");
        }
    });
});
</script>

</body>
</html>