<?php
session_start();

/* ===== NASTAVENÍ ===== */
$ADMIN_PASSWORD = 'ssb22';

/* ===== ODHLAŠENÍ ===== */
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: spravce_bbr.php');
    exit;
}

/* ===== ZPRACOVÁNÍ PŘIHLÁŠENÍ ===== */
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
        header('Location: spravce_bbr.php');
        exit;
    } else {
        $error = 'Nesprávné heslo';
    }
}

/* ===== KONTROLA PŘÍSTUPU ===== */
$is_admin = !empty($_SESSION['admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>spravce</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="css/bbr.css">
<script src="js/p5.min.js"></script>
</head>

<body>

<div class="header">
  <div><b>spravce</b> | <a href = "index.php">home</a> |</div>
  <div>
    <?php if ($is_admin): ?>
        <a href="?logout=1">logout</a>
    <?php else: ?>
        <span>login required</span>
    <?php endif; ?>
  </div>
</div>

<div class="container">
  <div class="content">

<?php if (!$is_admin): ?>

    <!-- ===== LOGIN FORM ===== -->
    <div class="panel" style="max-width:300px;margin:40px auto;">
        <form method="post">
            <label>Admin password</label><br>
            <input type="password" name="password" autofocus>
            <br><br>
            <button type="submit">login</button>
            <?php if ($error): ?>
                <div style="color:#f66;margin-top:10px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
        </form>
    </div>

<?php else: ?>

    <!-- ===== ADMIN CONTENT ===== -->
    <div class="panel">
<a href="init.php">init?</a><br/>

<a href="simple_ecc251.html">simple_ecc251.html</a> skoky na křivce 251<br/>
<a href="test_ash24.html">test_ash24.html</a> hash24<br/>
<a href="tests_examples/all251.html">all251</a> .:.<br/>
<a href="test_sign251.html">test_sign251.html</a> podepsání a ověření zprávy<br/>
<a href="test_sign251_16.html">test_sign251_16.html</a> podepsání a ověření - loop 16x<br/>
<a href="test_tx.php">test_tx</a> první tx flow<br/> 
<a href="tx_playground.php">tx_playground.php</a>ab / ba<br/>

      
<?php 
if ($is_admin): 
?>
<?php
include "db_info.php";
include 'table_users.php'; 
include "table_tx.php"; 
?>
<?php endif; ?>

    </div>

<?php endif; ?>

  </div>
</div>

</body>
</html>
