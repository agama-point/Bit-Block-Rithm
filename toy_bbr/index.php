<?php
session_start();
include "index_api.php"; // ajax

// LOGOUT
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

$current_k1 = $_SESSION['k1'] ?? null;

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
    <link rel="stylesheet" href="css/bbr.css?v=2">
    <script src="js/jquery.min.js"></script>
    <script src="js/agama_bech32.js"></script>
    <script src="js/ash24.js?v=0.11"></script>
    <script src="js/ash32.js?v=0.10"></script>
    <script src="js/ess251.js?v=0.33"></script>
    <script src="js/qrcode.js"></script> 
    <script src="js/p5.min.js"></script>
</head>

<body>
<script>
    if (localStorage.getItem('theme') === 'light') {
        document.body.classList.add('light-mode');
    }
</script>

<div class="header">
  <div><b>B·B·R</b> | Bit·Block·Rithm | Don’t trust, verify. </div>
  <div>
    <button id="theme-toggle" style="padding: 2px 10px; cursor: pointer; vertical-align: middle; min-width: 80px;">
        </button>
    |
    <?php if(isset($_SESSION['nick'])): ?>
      Logged in as <b><?= htmlspecialchars($_SESSION['nick']) ?></b> |
      <a href="index.php?logout=1">Logout</a>
    <?php else: ?>
      <a href="create_acc.php">create account</a> |
      <a href="#" id="login-toggle">login</a>
    <?php endif; ?>
  </div>
</div>

<?php if(isset($_SESSION['nick'])): ?>
<div class="user-menu" style="display:block !important; border: 1px solid green;">
  &nbsp;<a href="index.php?page=home"> home</a> |&nbsp;
  &nbsp;<a href="index.php?page=keys"> keys</a> |&nbsp;
  &nbsp;<a href="index.php?page=wallet"> wallet</a> |&nbsp;
  &nbsp;<a href="index.php?page=mining"> mining</a> |&nbsp;
  &nbsp;<a href="index.php?page=blockchain"> blockchain</a> |&nbsp;
  &nbsp;<a href="index.php?page=system"> system</a> |&nbsp; 
  &nbsp;<a href="index.php?page=tests"> tests</a> 
</div>
<?php endif; ?>

<div class="container">
  <?php if (!isset($_SESSION['nick'])): ?>
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
    function updateThemeButton() {
        if ($('body').hasClass('light-mode')) {
            $("#theme-toggle").html("☾ Dark");
        } else {
            $("#theme-toggle").html("☼ Light");
        }
    }
    
    updateThemeButton();

    // Login panel logic
    $("#login-toggle").on("click", function(e){
        e.preventDefault();
        $("#login-panel").fadeIn(150);
    });

    $("#login-cancel").on("click", function(){ $("#login-panel").fadeOut(150); });

    // Theme Switcher logic
    $("#theme-toggle").on("click", function() {
        $('body').toggleClass('light-mode');
        
        if ($('body').hasClass('light-mode')) {
            localStorage.setItem('theme', 'light');
        } else {
            localStorage.setItem('theme', 'dark');
        }    
        
        updateThemeButton();
    });
});
</script>

</body>
</html>