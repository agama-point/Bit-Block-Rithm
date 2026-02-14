<?php
session_start();
//----------------------------------ajax------------------------
$db = new SQLite3(__DIR__ . "/main.db");

// --- Add this to your AJAX section in index.php ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean();
    $action = $_POST['action'];

    if ($action === "coinbase") {
        $addr = $_POST['addr'];
        $value = 10;
        $utxo_time = time();
        $db->exec("INSERT INTO transactions (txid, sig, from_addr, to_addr, val1, val2, mp, utxo_time) 
                   VALUES (0, NULL, NULL, '$addr', 0, $value, 0, $utxo_time)");
        $lastId = $db->lastInsertRowID();
        $new_txid = $lastId + 1000;
        $db->exec("UPDATE transactions SET txid = $new_txid WHERE rowid = $lastId");
        $db->exec("INSERT INTO utxo (txid, owner, value, spent) VALUES ($new_txid, '$addr', $value, 0)");
        echo "Success: 10 coins mined (TXID: $new_txid) for $addr";
        exit;
    }

    if ($action === "get_utxo") {
        header('Content-Type: application/json');
        $addr = $_POST['addr'];
        $res = $db->query("SELECT * FROM utxo WHERE owner='$addr' AND spent=0 LIMIT 1");
        $row = $res->fetchArray(SQLITE3_ASSOC);
        echo json_encode($row ?: ["error" => "No available UTXO found"]);
        exit;
    }

    if ($action === "send") {
        $from = $_POST['from']; $to = $_POST['to'];
        $val1 = intval($_POST['val1']); $val2 = intval($_POST['val2']);
        $r = $_POST['r']; $s = $_POST['s'];
        $utxo_id = intval($_POST['utxo_id']);
        
        if ($val2 > $val1) { echo "Error: Insufficient funds in selected UTXO."; exit; }

        $db->exec("INSERT INTO transactions (txid, sig, from_addr, to_addr, val1, val2, mp, utxo_time) 
                   VALUES (0, '$r,$s', '$from', '$to', $val1, $val2, 1, ".time().")");
        $lastId = $db->lastInsertRowID();
        $new_txid = $lastId + 1000;
        $db->exec("UPDATE transactions SET txid = $new_txid WHERE rowid = $lastId");
        $db->exec("UPDATE utxo SET spent=1 WHERE id=$utxo_id");
        $db->exec("INSERT INTO utxo (txid, owner, value, spent) VALUES ($new_txid, '$to', $val2, 0)");
        
        $change = $val1 - $val2;
        if ($change > 0) $db->exec("INSERT INTO utxo (txid, owner, value, spent) VALUES ($new_txid, '$from', $change, 0)");

        echo "Transaction broadcasted! TXID: $new_txid. Sent: $val2, Change: $change";
        exit;
    }
}
//--------------------------------------/ajax-------------------------

$nick = $_SESSION['nick'] ?? null;
$nick = $_SESSION['k1'] ?? null;
$nick = $_SESSION['mode'] ?? null;
$nick = $_SESSION['net'] ?? null;
$nick = $_SESSION['minerdelay'] ?? null;


// LOGOUT
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

// povolené podstránky
$allowed_pages = ['home','keys','wallet','mining','blockchain','system','tests'];
$page = $_GET['page'] ?? 'keys';

if (!in_array($page, $allowed_pages)) {
    $page = 'keys';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Wallet</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="css/bbr.css">
<script src="js/jquery.min.js"></script>
<script src="js/agama_bech32.js"></script>
<script src="js/ash24.js"></script>
<script src="js/ess251.js"></script> 
<script src="js/p5.min.js"></script>

</head>

<body>

<div class="header">
  <div><b>B·B·R</b> | Bit·Block·Rithm | Don’t trust, verify — but first, understand. </div>
  <div>
    <?php if(isset($_SESSION['nick'])): ?>
      Logged in as <b><?= htmlspecialchars($_SESSION['nick']) ?></b> |
      <a href="index.php?logout=1">Logout</a>
    <?php else: ?>
      <a href="create_acc.php">create account</a> |
      <a href="#" id="login-toggle">login</a>
    <?php endif; ?>
  </div>
</div>

<?php 
if(isset($_SESSION['nick'])): 
?>

<!-- USER HORIZONTAL MENU -->
<div class="user-menu" style="display:block !important; border: 1px solid green;">
  <a href="index.php?page=home">home</a>
  <a href="index.php?page=keys">keys</a>
  <a href="index.php?page=wallet">wallet</a>
  <a href="index.php?page=mining">mining</a>
  <a href="index.php?page=blockchain">blockchain</a>
  <a href="index.php?page=system">system</a>
  <a href="index.php?page=tests">tests</a>
</div>
<?php 
endif; 
?>

<div class="container">
  <?php if (!isset($_SESSION['nick'])): ?>
  <!-- login panel -->
  <div class="panel" id="login-panel" style="display:none;">
    <h3>Login</h3>
    <form method="post" action="login.php">
      <label>Nick:</label><br>
      <input type="text" name="nick"><br>
      <label>Password:</label><br>
      <input type="password" name="psw"><br><br>
      <button type="submit">Login</button>
      <button type="button" id="login-cancel">Cancel</button>
    </form>
  </div>
  <?php endif; ?>

  <div class="content">
    <div class="panel">
    <?php
      if (isset($_SESSION['nick'])) {
          //echo ":.:";
          include "u_" . $page . ".php";
      } else {
          echo ".:."; 
          include 'u_links.php';
      }
    ?>
    </div>
  </div>
</div>

<script>
$(function(){

    $("#login-toggle").on("click", function(e){
        e.preventDefault();
        $("#login-panel").fadeIn(150);
    });

    $("#login-cancel").on("click", function(){
        $("#login-panel").fadeOut(150);
    });
});
</script>

</body>
</html>
