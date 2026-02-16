<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$mainDbFile = __DIR__ . "/main.db";   // adjust to your DB
$txid = $_GET['txid'] ?? '';

if ($txid === '') {
    die("Missing txid.");
}

try {
    $db = new SQLite3($mainDbFile);

    $stmt = $db->prepare("
        SELECT *
        FROM transactions
        WHERE txid = :txid
        LIMIT 1
    ");

    $stmt->bindValue(':txid', $txid, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    if (!$row) {
        die("Transaction not found.");
    }

} catch (Exception $e) {
    die("DB error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Transaction <?= htmlspecialchars($row['txid']) ?></title>
<link rel="stylesheet" href="css/bbr.css">
<script src="js/jquery.min.js"></script>
<script src='js/ash24.js'></script>
<script src='js/ess251.js'></script>
<style>
body { font-family: monospace; background:#111; color:#0f0; padding: 20px; }
table { border-collapse: collapse; }
td { padding:6px 12px; border:1px solid #333; }
td:first-child { font-weight:bold; color:#fff; }

.explain { background:#181818; padding:15px; border:1px solid #333; margin-bottom:15px; }
.ok { color:#0f0; }
.bad { color:#f44; }
.dim { color:#888; }

</style>
</head>
<body>

<h1>Transaction <?= htmlspecialchars($row['txid']) ?></h1>

<table>
<tr><td>id</td><td><?= $row['id'] ?></td></tr>
<tr><td>txid</td><td><?= htmlspecialchars($row['txid']) ?></td></tr>
<tr><td>sig</td><td><?= htmlspecialchars($row['sig']) ?></td></tr>
<tr><td>from_addr</td><td><?= htmlspecialchars($row['from_addr']) ?></td></tr>
<tr><td>to_addr</td><td><?= htmlspecialchars($row['to_addr']) ?></td></tr>
<tr><td>val1</td><td><?= $row['val1'] ?></td></tr>
<tr><td>val2</td><td><?= $row['val2'] ?></td></tr>
<tr><td>mp</td><td><?= $row['mp'] ?></td></tr>
<tr><td>utxo_time</td>
    <td>
        <?php
        if ($row['utxo_time']) {
            echo date("Y-m-d H:i:s", $row['utxo_time']);
        } else {
            echo "-";
        }
        ?>
    </td>
</tr>
</table>

<hr>
<hr>
<h2>Transaction Detailed Analysis and Validation</h2>

<div class="explain">
<?php
echo "<h3>1) Basic Data Parsing</h3>";
echo "<b>Internal ID (rowid):</b> {$row['id']}<br>";
echo "<b>TXID:</b> {$row['txid']}<br>";
echo "<b>Type (mp):</b> {$row['mp']} ";

if((int)$row['mp'] === 0){
    echo "<span class='dim'>(0 = coinbase)</span><br>";
}else{
    echo "<span class='dim'>(1 = regular transaction)</span><br>";
}

echo "<b>From:</b> ".htmlspecialchars($row['from_addr'])."<br>";
echo "<b>To:</b> ".htmlspecialchars($row['to_addr'])."<br>";
echo "<b>Input value (val1):</b> {$row['val1']}<br>";
echo "<b>Sent amount (val2):</b> {$row['val2']}<br>";
echo "<b>Change:</b> ".((int)$row['val1']-(int)$row['val2'])."<br>";
echo "<b>Timestamp:</b> ".date("Y-m-d H:i:s",(int)$row['utxo_time'])."<br>";
?>
</div>

<div class="explain">
<?php
echo "<h3>2) Structural Check</h3>";
$struct_ok = true;

if((int)$row['mp'] === 0){
    echo "Coinbase must have:<br>- from_addr = NULL<br>- val1 = 0<br><br>";
    if($row['from_addr'] !== NULL){ echo "<span class='bad'>✗ from_addr is not NULL</span><br>"; $struct_ok = false; }
    if((int)$row['val1'] !== 0){ echo "<span class='bad'>✗ val1 is not 0</span><br>"; $struct_ok = false; }
    if($struct_ok){ echo "<span class='ok'>✓ Structurally valid coinbase</span>"; }
}else{
    echo "Regular transaction must satisfy:<br>- val2 ≤ val1<br>- existing input UTXO<br><br>";
    if((int)$row['val2'] <= (int)$row['val1']){ echo "<span class='ok'>✓ val2 ≤ val1</span><br>"; } 
    else { echo "<span class='bad'>✗ val2 > val1</span><br>"; $struct_ok = false; }

    $stmt2 = $db->prepare("SELECT COUNT(*) as cnt FROM utxo WHERE owner = :owner AND value = :value");
    $stmt2->bindValue(':owner',$row['from_addr'],SQLITE3_TEXT);
    $stmt2->bindValue(':value',$row['val1'],SQLITE3_INTEGER);
    $cnt = $stmt2->execute()->fetchArray(SQLITE3_ASSOC)['cnt'];

    if($cnt > 0){ echo "<span class='ok'>✓ Matching UTXO exists</span><br>"; }
    else{ echo "<span class='bad'>✗ Input UTXO not found</span><br>"; $struct_ok = false; }

    if($struct_ok){ echo "<br><span class='ok'>✓ Structurally valid</span>"; }
}
?>
</div>

<?php
if($row['sig']){
    list($r,$s) = explode(",",$row['sig']);
    $msg = $row['from_addr']."|".$row['prev_txid']."|".$row['to_addr']."|".$row['val2'];
    $from_adr = $row['from_addr'];
?>

<div class="explain">
    <h3>3) Cryptographic Section</h3>
    <div id="cryptoDetail"></div>
    <div style="margin-top: 20px; color: #888;">Debug Log:</div>
    <pre id="log"></pre>
</div>

<script>
(function(){
    function log(txt){ $("#log").append(txt + "\n"); }

    $(document).ready(function(){
        $("#log").text(""); // Clear log

        log("===== SESSION DEBUG =====");
        <?php
        $nick = $_SESSION['nick'] ?? null;
        $k1 = $_SESSION['k1'] ?? 111;
        $mdel = $_SESSION['minerdelay'] ?? null;
        
        echo "log('SESSION[nick]: ' + " . json_encode($nick) . ");\n";
        echo "log('SESSION[k1]: ' + " . json_encode($k1) . ");\n";
        echo "log('SESSION[minerdelay]: ' + " . json_encode($mdel) . ");\n";
        ?>
        log("=========================\n");

        log("r=<?= $r ?> , s=<?= $s ?>");
        //log("from_addr: <? $from_adr ?>");
        log("From Address (DB): <?= htmlspecialchars($row['from_addr']) ?>");
        
        log("----- KEYS -----");
        //let from_pub = hexa_to_point(<?= htmlspecialchars($row['from_addr']) ?>);
        let from_pub = hexa_to_point("<?= htmlspecialchars($row['from_addr']) ?>");
        log("From Pub (reconstructed) hexa_to_point() -> [" + from_pub + "]");
        //log("hexa_to_point -> "+ from_pub);

        
        let priv = <?= json_encode($k1) ?>; 
        
        log("Function: scalar_mult(priv, G_POINT)");
        log("Input:");
        log("  priv [session_k1] = " + priv);
        log("  G_POINT = [" + G_POINT + "]");
        let pub = scalar_mult(priv, G_POINT);
        log("  pub = scalar_mult(priv, G_POINT) -> " + pub);
        let pubKeyAddr = pubkey_to_addr(pub);
        log("  pubkey_to_addr(pub) ->" + pubKeyAddr);

        log("----- HASH -----");
        let msg = "<?= $msg ?>";
        let h_raw = ASH24(msg);
        let hash_hex  = hex24(h_raw);

        log("Message: <span class=\"b\">" + msg + "</span>");
        log("h_raw: " + h_raw + " | hash_hex: " + hash_hex);

        log("----- SIGN -----");
        log("Function: signToy(priv, h_raw)");
        log("Input: " + "  priv = " + priv + " | h_raw = " + h_raw);   

        let sigT = signToy(priv, h_raw);

        log("Output: R_point[" + sigT.R_point + "]" + " | sigT.r = " + sigT.r + " | sigT.s = " + sigT.s);

        let L = scalar_mult(sigT.s, G_POINT);
        LB = "<span class=\"b\">" + L + "</span>";
        log("  Lp = scalar_mult(s, G) = [" + LB + "]");

        let e_Pub = scalar_mult(h_raw % ORDER_N, pub);
        log("  e*Pub = scalar_mult(h mod n, Pub) = [" + e_Pub + "]");

        let P = point_adding(sigT.R_point, e_Pub);
        PB = "<span class=\"b\">" + P + "</span>";

        log("  Rp = R + e*Pub = [" + PB + "]");

        log("----- VERIFY -----");
        log("Function: verifyToy(pub, h_raw, sig)");
        log("Input:" + "  pub = [" + pub + "]" + " | h_raw = " + h_raw + " | sig = { r:" + sigT.r + ", s:" + sigT.s + " }");

        let valid = verifyToy(pub, h_raw, sigT);

        log("Output:" + "  valid = " + valid);

        if(valid){
            log("\n  => L == P  →  Signature is VALID ✅");
        } else {
            log("\n  => L != P  →  Signature is INVALID ❌");
        }      

        log(" ");
        log("============sig2=========\n");
        let sig2 = { 
          r: parseInt('<?= $r ?>'), 
          s: parseInt('<?= $s ?>'),
          R_point: scalar_mult(parseInt('<?= $r ?>'), G_POINT) 
        };
        
        log("Public key: [" + pub + "]");

        let hash = hash_hex;
        let ok = verifyToy(pub, h_raw, sig2);
        log("verifyToy(pub, h_raw, sig2)");
        log("verifyToy sig2 result test: " + ok);




log(" ");
        log("============ CRYPTO TESTS 2 =========\n");

        // TEST 1: VALID (Using the correct public key)
        log("TEST 1: Verification with correct Public Key");
        log("  Target PubKey - pub: [" + from_pub + "]");
        log("  h_raw: " + h_raw);
        log("sigT: R_point[" + sigT.R_point + "]" + " | sigT.r = " + sigT.r + " | sigT.s = " + sigT.s);
        log("[<span class=\"b\">" + sigT.r +","+sigT.s + "</span>]");

        let test1_res = verifyToy(from_pub, h_raw, sigT);
        log("verifyToy(from_pub, h_raw, sigT)"); 
        log("  Result: " + (test1_res ? "✅ VALID" : "❌ INVALID"));

        log(" ");

        // TEST 2: INVALID (Using a random public key)
        let random_priv = 777; 
        let random_pub = scalar_mult(random_priv, G_POINT);
        log("TEST 2: Verification with random Public Key");
        log("  Random PubKey: [" + random_pub + "]");
        let test2_res = verifyToy(random_pub, h_raw, sigT);
        log("  Result: " + (test2_res ? "✅ VALID" : "❌ INVALID"));

        log("\n========================================");




        let html = '';
        html += '<br><b>ASH24 hash:</b> ' + hash + '<br>';
        html += '<b>Public key from address:</b> (' + pub[0] + ',' + pub[1] + ')<br>';

        html += '<br><b>Verification principle:</b><br>';
        html += '1) Compute w = s⁻¹ mod n<br>';
        html += '2) u1 = hash * w mod n<br>';
        html += '3) u2 = r * w mod n<br>';
        html += '4) Point R = u1·G + u2·Pub<br>';
        html += '5) Valid if R.x mod n = r<br><br>';

        if(ok){
            html += '<span class="ok">✓ Signature is cryptographically VALID</span>';
        }else{
            html += '<span class="bad">✗ Signature is INVALID</span>';
        }

        document.getElementById('cryptoDetail').innerHTML = html;
    });
})();
</script>
<?php } ?>

</body>
</html>
