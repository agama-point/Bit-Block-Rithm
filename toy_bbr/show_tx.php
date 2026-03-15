<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$mainDbFile = __DIR__ . "/main.db";
$txid = $_GET['txid'] ?? '';

if ($txid === '') { die("Missing txid."); }

try {
    $db = new SQLite3($mainDbFile);
    
    // 1) Načtení aktuální transakce pro analýzu
    $stmt = $db->prepare("SELECT * FROM transactions WHERE txid = :txid LIMIT 1");
    $stmt->bindValue(':txid', $txid, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    if (!$row) { die("Transaction not found."); }

    // 2) Načtení řetězce transakcí (Walk backwards)
    $chain = [];
    $current = $txid;
    while ($current !== '' && $current !== null) {
        $stmtC = $db->prepare("SELECT * FROM transactions WHERE txid = :txid LIMIT 1");
        $stmtC->bindValue(':txid', $current, SQLITE3_INTEGER);
        $resC = $stmtC->execute();
        $rowC = $resC->fetchArray(SQLITE3_ASSOC);
        if (!$rowC) break;
        $chain[] = $rowC;
        $current = $rowC['prev_txid'];
    }

} catch (Exception $e) { die("DB error: " . $e->getMessage()); }

$msg_payload = $row['from_addr']."|".$row['prev_txid']."|".$row['to_addr']."|".$row['val2'];
$sig_hex = $row['sig'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TX Analysis & History</title>
    <link rel="stylesheet" href="css/bbr.css?v=2">
    <script src="js/jquery.min.js"></script>
    <script src='js/ash24.js'></script>
    <script src='js/ess251.js?v=0.31'></script>
    <style>
        .container { display: flex; gap: 20px; height: calc(100vh - 100px); }
        .left-panel { flex: 0 1 650px; display: flex; flex-direction: column; gap: 10px; overflow-y: auto; padding-right: 10px; }
        .right-panel { flex: 1; display: flex; flex-direction: column; min-width: 350px;}

        .data-box { line-height: 1.4; margin-bottom: 10px; }
        .val-hl { color: #00ccff; font-weight: bold; }
        
        .row { display: flex; align-items: center; margin-bottom: 8px; gap: 10px; }
        .row label { width: 100px; color: #ccc; font-weight: bold; font-size: 0.9em; }
        .row input { padding: 6px 10px; width: 125px; outline: none; }      
        
        .row input:focus { border-color: #00ff99; }
        .row .info { color: #aaa; font-size: 0.8em; width: 100px; }        
        
        .status-badge { font-weight: bold; padding: 4px 12px; margin-left: 10px; border-radius: 2px; }
        .ok { border: 1px solid #00ff99; color: #00ff99; }
        .bad { border: 1px solid #ff4136; color: #ff4136; }

    </style>
</head>
<body>
<script>
    if (localStorage.getItem('theme') === 'light') { document.body.classList.add('light-mode'); }
</script>

<h1 class="digip">TX Analysis | ID <?= htmlspecialchars($row['txid']) ?></h1>

<div class="container">
    <div class="left-panel">
        <div class="data-box">
            <input type="hidden" id="val_in" value="<?= (int)$row['val1'] ?>">
            <input type="hidden" id="val_out" value="<?= (int)$row['val2'] ?>">
            <input type="hidden" id="val_change" value="<?= (int)$row['val1']-(int)$row['val2'] ?>">

            PREV_TXID: 
            <a href="?txid=<?= urlencode($row['prev_txid']) ?>">
            <strong><?= htmlspecialchars($row['prev_txid']) ?></strong></a>
            | TXID: <?= $row['txid'] ?> 
            | Type: <?= empty($row['prev_txid']) ? "coinbase" : "P2PK" ?>
            <br>
            From: <span class="col_vio"><?= htmlspecialchars($row['from_addr']) ?></span> | To: <span class="col_vio"><?= htmlspecialchars($row['to_addr']) ?></span><br>
            In: <?= $row['val1'] ?> | Out: <span class="col_gre"><?= $row['val2'] ?></span> | Change: <?= (int)$row['val1']-(int)$row['val2'] ?> | 
            <span class="col_gre"><?= date('ymd | H:i', $row['utxo_time']) ?></span>
        </div>
    

        <div class="box1">
            <h3 class="col_ora">Verify Section</h3>
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

        <div class="box1">
            <h3 class="col_ora">Sign Section</h3>
            <div class="row">
                <label>PrivKey</label>
                <input type="number" id="in_priv" value="123">
                <div class="info">Deterministic signing</div>
            </div>
            <button class="btn-action btn-signx" onclick="doSign()">Sign Message</button>
            <button class="btn-action btn-clearx" onclick="$('#log').text('')">Clear Log</button>
        </div>


        <div class="box2">
            <h3 class="col_ora">Transaction History (Chain)</h3>
            <table class="tab">
                <tr>
                    <th>TXID</th>
                    <th>From</th>
                    <th>To</th>
                    <th>In</th>
                    <th>Out</th>
                    <th>Datetime</th>
                </tr>
                <?php foreach ($chain as $c_row): ?>
                <tr class="<?= ($c_row['txid'] == $row['txid']) ? 'current-row' : '' ?>">
                    <td><a href="?txid=<?= urlencode($c_row['txid']) ?>"><?= htmlspecialchars($c_row['txid']) ?></a></td>
                    <td class="col_vio"><?= htmlspecialchars(substr($c_row['from_addr'], 0, 8)) ?></td>
                    <td class="col_vio"><?= htmlspecialchars(substr($c_row['to_addr'], 0, 8)) ?></td>
                    <td><?= (int)$c_row['val1'] ?></td>
                    <td><?= (int)$c_row['val2'] ?></td>
                    <td><?= date('ymd H:i', $c_row['utxo_time']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>




        <div class="box2" style="border-color: #333;">
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

     <hr />
   <a href="tests_examples/simple_ecc251ec.html">tests_examples/simple_ecc251ec.html</a>
   <a href="tests_examples/simple_ecc251add.html">tests_examples/simple_ecc251add.html</a>
   <a href="https://github.com/agama-point/Bit-Block-Rithm/blob/main/toy_bbr/js/ess251.js">ess251.js</a>


            </div>
        </div>
    </div>

    <div class="right-panel">
        <pre id="log" class="log"></pre>
    </div>
</div>

<script>
/* JS funkce zůstávají přesně podle původního zadání pro zachování logování */
function log(txt) { 
    let logEl = document.getElementById("log");
    logEl.innerHTML += txt + "\n"; 
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
      let h_hex = hex24(h_raw);
      let pubPoint = hexa_to_point(addr);
      let sParts = parseSig(sigHex);

      let k_nonce = modN(h_raw ^ 0x55, ORDER_N);
      if (k_nonce === 0) k_nonce = 1;
      let R_point = scalar_mult(k_nonce, G_POINT);
      let sigObj = { r: sParts.r, s: sParts.s, R_point: R_point };
      let sigHex2 = sig_to_hexa(sigObj);

      log("ESS251_VER: " + ESS251_VER);
      log(` -> Curve: y^2 = x^3 + ${ECC_PARAMS.b} mod ${ECC_PARAMS.p} | [${ECC_PARAMS.G[0]}, ${ECC_PARAMS.G[1]}]`);
      log("<span class='col_ora'>----- VERIFICATION PROCESS -----</span>");
      log("scriptPubKey (Lock recipient UTXO): {pub:" + addr + ",op:OP_CHECKSIG}");
      log("scriptSig (Unlock sender UTXO): {sig:" + sigHex2 + "}");
      log("s * G = R + e * Q");
      log("LEFT = scalar_mult(s, G_POINT)");
      log("RIGHT = point_adding(R_point, e_Pub)");
      log("---------  Balance:  ------------");

      let vin = $("#val_in").val();
      let vout = $("#val_out").val();
      let vchg = $("#val_change").val();
      log("  In    ⮕| " + vin + " | Out ⮕ <span class='col_ora'>" + vout + "</span>");
      log(" Change ⬅| " + vchg + " |");
      log("--------------------------------");
      log("Message: " + msg);
      log("hash_raw: " + h_raw + " | hash_hex: " + h_hex);
      log("<span class='col_ora'>PubKey:</span> "+ addr + " -> [<span class='col_ora'>" + pubPoint + "</span>]");
      log("→ Signature (r,<span class='col_ora'>s</span>): {" + sigObj.r + ", <span class='col_ora'>" + sigObj.s + "</span>} " + sigHex2);
      log("  <span class='col_ora'>R</span> (Nonce_point): [" + sigObj.R_point + "]");
      log("→ Function: verifyToy(pub, h_raw, sig)");

      let valid = verifyToy(pubPoint, h_raw, sigObj);
      let e = modN(h_raw, ORDER_N);
      log("Step 1: Challenge <span class='col_ora'>e</span> = hash mod n = <span class='col_ora'>" + e +"</span>");

      const safeMap = (pt) => pt ? `[${pt[0]}, ${pt[1]}]` : "INF (Point at Infinity)";
      let L = scalar_mult(sigObj.s, G_POINT);
      let e_Pub = scalar_mult(e, pubPoint);
      let P = point_adding(sigObj.R_point, e_Pub);
              
      log("Step 2: <span class='col_ora'>LEFT = s * G</span> = " + safeMap(L));
      log("  e: " + e + " | PubKey: " + pubPoint)
      log("Step 3: <span class='col_ora'>RIGHT = R + e * PubKey</span> = " + safeMap(P));
      log("⬅ [" + sigObj.R_point + "] + [" + e_Pub + "]");

      log("Output: valid = " + valid);

      if (valid) {
        log("  => LEFT == RIGHT  →  Signature is VALID ✅");
         $("#status_box").html('<span class="status-badge ok">VALID ✅</span>');
        } else {
         log("  => LEFT != RIGHT  →  Signature is INVALID ❌");
         $("#status_box").html('<span class="status-badge bad">INVALID ❌</span>');
        }
      log("---------------------------------\n");
    } catch(e) { log("!! Error: " + e.message);}
}

function doSign() {
    let msg = $("#in_msg").val();
    let priv = parseInt($("#in_priv").val(), 10);
    if (isNaN(priv)) { log("ERROR: Invalid Private Key."); return; }

    let h_raw = ASH24(msg);
    log("<span class='col_ora'>-------- SIGNING PROCESS --------</span>");
    log("Nonce | MODE = random | MODE = deterministic ⮕");
    log("  k = (msg_hash ^ 0x55) % ORDER_N | <span class='col_ora'> k * G = R</span>");
    log("s = k + e * <span class='col_ora'>q</span>  ⮕  s * G = k * G + e * <span class='col_ora'>q * G</span> ⮕  s * G = R + e * <span class='col_ora'>Q</span>");
    log("Linear combination binds the signature to the private key");                        
    log("Message: " + msg + " | Hash: " + h_raw);
    
    let sigObj = signToy(priv, h_raw);
    let sigHex = sig_to_hexa(sigObj);
    let newPubPoint = scalar_mult(priv, G_POINT);
    let newPubHex = pubkey_to_addr(newPubPoint);
    
    log("Result: r = " + sigObj.r + ", s = " + sigObj.s);
    log("Hex Signature: <span class='col_ora'>" + sigHex + "</span>");
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