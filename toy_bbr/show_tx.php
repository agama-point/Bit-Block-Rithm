<?php
$mainDbFile = __DIR__ . "/main.db";   // uprav dle své DB
$txid = $_GET['txid'] ?? '';

if ($txid === '') {
    die("Chybí txid.");
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
        die("Transakce nenalezena.");
    }

} catch (Exception $e) {
    die("Chyba DB: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<title>Transaction <?= htmlspecialchars($row['txid']) ?></title>
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

/* Styl pro ladící log */
#log {
    background: #000;
    color: #0f0;
    padding: 10px;
    border: 1px dashed #0f0;
    margin-top: 20px;
    white-space: pre-wrap;
    font-size: 0.85em;
}
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
<h2>Detailní rozbor a validace transakce</h2>

<div class="explain">
<?php

echo "<h3>1) Základní parsování dat</h3>";
echo "<b>Interní ID (rowid):</b> {$row['id']}<br>";
echo "<b>TXID:</b> {$row['txid']}<br>";
echo "<b>Typ (mp):</b> {$row['mp']} ";

if((int)$row['mp'] === 0){
    echo "<span class='dim'>(0 = coinbase)</span><br>";
}else{
    echo "<span class='dim'>(1 = běžná transakce)</span><br>";
}

echo "<b>Od:</b> ".htmlspecialchars($row['from_addr'])."<br>";
echo "<b>Komu:</b> ".htmlspecialchars($row['to_addr'])."<br>";
echo "<b>Vstupní hodnota (val1):</b> {$row['val1']}<br>";
echo "<b>Odeslaná částka (val2):</b> {$row['val2']}<br>";
echo "<b>Změna:</b> ".((int)$row['val1']-(int)$row['val2'])."<br>";
echo "<b>Timestamp:</b> ".date("Y-m-d H:i:s",(int)$row['utxo_time'])."<br>";
?>
</div>

<div class="explain">
<?php
echo "<h3>2) Strukturální kontrola</h3>";
$struct_ok = true;

if((int)$row['mp'] === 0){
    echo "Coinbase musí mít:<br>- from_addr = NULL<br>- val1 = 0<br><br>";
    if($row['from_addr'] !== NULL){ echo "<span class='bad'>✗ from_addr není NULL</span><br>"; $struct_ok = false; }
    if((int)$row['val1'] !== 0){ echo "<span class='bad'>✗ val1 není 0</span><br>"; $struct_ok = false; }
    if($struct_ok){ echo "<span class='ok'>✓ Strukturálně správná coinbase</span>"; }
}else{
    echo "Běžná transakce musí splňovat:<br>- val2 ≤ val1<br>- existující vstupní UTXO<br><br>";
    if((int)$row['val2'] <= (int)$row['val1']){ echo "<span class='ok'>✓ val2 ≤ val1</span><br>"; } 
    else { echo "<span class='bad'>✗ val2 > val1</span><br>"; $struct_ok = false; }

    $stmt2 = $db->prepare("SELECT COUNT(*) as cnt FROM utxo WHERE owner = :owner AND value = :value");
    $stmt2->bindValue(':owner',$row['from_addr'],SQLITE3_TEXT);
    $stmt2->bindValue(':value',$row['val1'],SQLITE3_INTEGER);
    $cnt = $stmt2->execute()->fetchArray(SQLITE3_ASSOC)['cnt'];

    if($cnt > 0){ echo "<span class='ok'>✓ Existuje odpovídající UTXO</span><br>"; }
    else{ echo "<span class='bad'>✗ Nenalezen vstupní UTXO</span><br>"; $struct_ok = false; }

    if($struct_ok){ echo "<br><span class='ok'>✓ Strukturálně validní</span>"; }
}
?>
</div>

<?php
if($row['sig']){
    list($r,$s) = explode(",",$row['sig']);
    $msg = $row['from_addr']."|".$row['to_addr']."|".$row['val2'];
?>

<div class="explain">
    <h3>3) Kryptografická část</h3>
    </code>
    <div id="cryptoDetail"></div>
    <div style="margin-top: 20px; color: #888;">Debug Log:</div>
    <pre id="log"></pre>
</div>

<script>
(function(){
    function log(txt){ $("#log").append(txt + "\n"); }

    $(document).ready(function(){
        $("#log").text(""); // Vyčistit log

log("r=<?= $r ?> , s=<?= $s ?>");
log("----- KEYS -----");
let priv = 111;
log("Funkce: scalar_mult(priv, G_POINT)");
log("Vstup:");
log("  priv = " + priv);
log("  G_POINT = [" + G_POINT + "]");
let pub = scalar_mult(priv, G_POINT);
       
log("----- HASH -----");
let msg = "<?= $msg ?>";
let h_raw = ASH24(msg);
let hash_hex  = hex24(h_raw);

log("Zpráva: " + msg);
log("h_raw: " + h_raw);
log("hash_hex: " + hash_hex);

log("----- SIGN -----");
log("Funkce: signToy(priv, h_raw)");
log("Vstup:");
log("  priv = " + priv);
log("  h_raw = " + h_raw);

let sigT = signToy(priv, h_raw);

log("Výstup objektu:");
log("  sigT.R_point = [" + sigT.R_point + "]");
log("  sigT.r = " + sigT.r);
log("  sigT.s = " + sigT.s);

log("\nMezikroky ověřitelné ručně:");

let L = scalar_mult(sigT.s, G_POINT);
log("  L = scalar_mult(s, G) = [" + L + "]");

let e_Pub = scalar_mult(h_raw % ORDER_N, pub);
log("  e*Pub = scalar_mult(h mod n, Pub) = [" + e_Pub + "]");

let P = point_adding(sigT.R_point, e_Pub);
log("  P = R + e*Pub = [" + P + "]");

log("----- VERIFY -----");
log("Funkce: verifyToy(pub, h_raw, sig)");
log("Vstup:");
log("  pub = [" + pub + "]");
log("  h_raw = " + h_raw);
log("  sig = { r:" + sigT.r + ", s:" + sigT.s + " }");

let valid = verifyToy(pub, h_raw, sigT);

log("Výstup:");
log("  valid = " + valid);

log("\nProč je podpis validní?");
log("  Podpis je validní, pokud platí:");
log("     s*G = R + e*Pub");
log("  což znamená, že podpis mohl vytvořit pouze držitel privátního klíče.");
log("  Zde:");
log("     L = [" + L + "]");
log("     P = [" + P + "]");

if(valid){
    log("\n  => L == P  →  Podpis je VALIDNÍ ✅");
} else {
    log("\n  => L != P  →  Podpis je NEPLATNÝ ❌");
}     

let sig2 = { 
  r: parseInt('<?= $r ?>'), 
  s: parseInt('<?= $s ?>'),
 // Rekonstrukce bodu R pro interní potřeby verifyToy
 R_point: scalar_mult(parseInt('<?= $r ?>'), G_POINT) 
};
        
//let pub = addr_to_pubkey("<?= $row['from_addr'] ?>");
log("Veřejný klíč: [" + pub + "]");

let ok = verifyToy(pub, hash, sig2);
log("Výsledek verifyToy: " + ok);

let html = '';
html += '<br><b>ASH24 hash:</b> ' + hash + '<br>';
html += '<b>Public key z adresy:</b> (' + pub[0] + ',' + pub[1] + ')<br>';

html += '<br><b>Princip verifikace:</b><br>';
html += '1) Spočítáme w = s⁻¹ mod n<br>';
html += '2) u1 = hash * w mod n<br>';
html += '3) u2 = r * w mod n<br>';
html += '4) Bod R = u1·G + u2·Pub<br>';
html += '5) Validní pokud R.x mod n = r<br><br>';

        if(ok){
            html += '<span class="ok">✓ Podpis je kryptograficky VALIDNÍ</span>';
        }else{
            html += '<span class="bad">✗ Podpis je NEVALIDNÍ</span>';
        }

        document.getElementById('cryptoDetail').innerHTML = html;
    });
})();
</script>
<?php } ?>

</body>
</html>