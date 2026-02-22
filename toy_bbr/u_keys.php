<?php
//session_start();
//$db = new SQLite3(__DIR__ . "/main.db");
//$nick = $_SESSION['nick'] ?? null;

//if(!$nick){
//    http_response_code(403);
//    exit("Not logged in");
//}

/* ===============================
   NEW FUNCTIONALITY – SAVE K1
   =============================== */
if(isset($_POST['new_k1'])){

    $newK1 = intval($_POST['new_k1']);

    if($newK1 < 1 || $newK1 > 250){
        echo json_encode(["status"=>"error","msg"=>"Value must be 1–250"]);
        exit;
    }

    $stmt = $db->prepare("UPDATE users SET k1 = :k1 WHERE nick = :nick");
    $stmt->bindValue(':k1', $newK1, SQLITE3_INTEGER);
    $stmt->bindValue(':nick', $nick, SQLITE3_TEXT);
    $stmt->execute();

    echo json_encode(["status"=>"ok","k1"=>$newK1]);
    exit;
}
/* =============================== */

$stmt = $db->prepare("SELECT k1, k2, k3 FROM users WHERE nick = :nick");
$stmt->bindValue(':nick', $nick, SQLITE3_TEXT);
$res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

$k1 = $res['k1'] ?? 0;
$k2 = $res['k2'] ?? 0;
$k3 = $res['k3'] ?? 0;

$count = 0;
foreach([$k1, $k2, $k3] as $k) if($k > 0) $count++;
?>

<h1 class="digip">KEYS</h1>

<div class="panel">
    <h3>Private key and the corresponding public key</h3>
    <p>User <?= htmlspecialchars($nick) ?> has active keys: <strong><?= $count ?> / 3</strong></p>

    <?php if($k1 > 0): ?>
    <div style="background: #111; padding: 15px; border-left: 3px solid #0f0; font-family: monospace;">
        
        <div style="margin-bottom: 10px;">
            <span style="color: #888;">PubKey (Hex): </span>
            <span id="pub-addr" style="color: orange; font-size: 1.2em; font-weight: bold;">...</span>
        </div>

        <div style="margin-bottom: 10px;">
            <span style="color: #888;">Public point P (k1 * G): </span>
            <span id="pub-point" style="color: #fff;">[ ?, ? ]</span>
        </div>

        <hr style="border: 0; border-top: 1px dashed #333; margin: 15px 0;">

        <button id="toggle-priv-btn" style="background: #222; color: #f44; border: 1px solid #f44; padding: 5px 10px; cursor: pointer; font-family: monospace;">
            SHOW PRIVATE KEY
        </button>

        <div id="priv-block" style="display: none; margin-top: 15px; padding: 10px; background: #1a0505; border: 1px solid #600;">
            <span style="color: #f44; font-weight: bold;">⚠ PRIVATE KEY (k1):</span><br>
            <span id="k1-val" style="color: #fff; font-size: 1.1em;"><?= $k1 ?></span>
        </div>

    </div>
    <?php else: ?>
        <div class="panel" style="color: #f44; border-color: #f44;">
            The user does not have a PrivateKey k1 set. <br />Generate it using the interactive graphical tool below and then save it.
        </div>
    <?php endif; ?>

    <!-- ===========================
         NEW BLOCK – CHANGE K1
         =========================== -->
    <hr style="border: 0; border-top: 1px dashed #333; margin: 20px 0;">

    <div style="font-family: monospace;">
        <span style="color:#888;">Change k1 (1–250):</span><br>
        <input type="number" id="new-k1" min="1" max="250"
               style="width:100px;padding:5px;background:#111;color:#fff;border:1px solid #555;">
        <button id="save-k1-btn"
               style="background:#222;color:#0f0;border:1px solid #0f0;padding:5px 10px;cursor:pointer;">
            Save the new key
        </button>

        <div id="save-status" style="margin-top:10px;font-size:0.9em;"></div>
    </div>
    <!-- =========================== -->

</div>

<script>
$(function() {

    // 1. Toggle private key
    $("#toggle-priv-btn").on("click", function() {
        const btn = $(this);
        $("#priv-block").slideToggle(200, function() {
            if ($(this).is(":visible")) {
                btn.text("HIDE PRIVATE KEY").css("background", "#400");
            } else {
                btn.text("SHOW PRIVATE KEY").css("background", "#222");
            }
        });
    });

    // 2. ESS251 computation logic
    function recomputePublic(k1val){
        try {
            const P = scalar_mult(k1val, G_POINT);
            if (P) {
                $("#pub-point").text("[" + P[0] + ", " + P[1] + "]");
                const address = pubkey_to_addr(P);
                $("#pub-addr").text("0x" + address.toUpperCase());
            }
        } catch (e) {
            $("#pub-point").html("<span style='color:red;'>Error: " + e.message + "</span>");
        }
    }

    const k1Raw = $("#k1-val").text();
    const k1 = parseInt(k1Raw);
    if (!isNaN(k1) && k1 > 0) {
        recomputePublic(k1);
    }

    // 3. Save new key
    $("#save-k1-btn").on("click", function(){

        const val = parseInt($("#new-k1").val());

        if (isNaN(val) || val < 1 || val > 250) {
            $("#save-status").html("<span style='color:red;'>Value must be between 1 and 250.</span>");
            return;
        }

        if (!confirm("Are you sure you want to overwrite the k1 key? \nYou may lose control over your existing coins.")) return;

        $.post(window.location.href, { new_k1: val }, function(resp){
            try{
                const data = JSON.parse(resp);
                if(data.status === "ok"){
                    $("#save-status").html("<span style='color:#0f0;'>Key - saved</span>");
                    $("#k1-val").text(val);
                    recomputePublic(val);
                } else {
                    $("#save-status").html("<span style='color:red;'>" + data.msg + "</span>");
                    location.reload();
                }
            } catch(e){
                $("#save-status").html("<span style='color:red;'>Server error. <br />Please log out and log in again (or refresh the page).</span>");
            }
        });

    });

});
</script>
<hr />
<div style="display: flex; align-items: flex-start; background: #141414; padding: 20px; border-radius: 8px;">
    
    <div id="p5-holder" style="position: relative; line-height: 0;"></div>

    <div style="display: flex; flex-direction: column; align-items: center; margin-left: 20px; height: 600px;">
        <span style="color: #00ff00; font-family: monospace; margin-bottom: 10px;">MAX</span>
        
        <input type="range" id="ecc-slider" min="1" max="251" value="1" 
               style="appearance: slider-vertical; width: 40px; height: 500px; cursor: pointer; accent-color: #006400;">
        
        <span style="color: #00ff00; font-family: monospace; margin-top: 10px;">MIN</span>
        
        <div style="margin-top: 20px; color: silver; font-family: sans-serif; text-align: center;">
            Private key:<br>
            <span id="key" style="font-size: 24px; color: #0f0; font-weight: bold;">1</span>
        </div>
    </div>
</div>

<script src="js/u_key251.js"></script>
