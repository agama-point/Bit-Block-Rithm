<script>
if (localStorage.getItem('theme') === 'light') { document.body.classList.add('light-mode'); }
</script>

<script>
$(function(){
    function updateThemeButton() {
        if ($('body').hasClass('light-mode')) { $("#theme-toggle").html("☾ Dark");
        } else {  $("#theme-toggle").html("☼ Light"); }
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



<div class="header">
  <div><b class="col_ora">B·B·R</b> | Bit·Block·Rithm | Don’t trust, verify. </div>
  <div>
    <button id="theme-toggle" style="padding: 2px 10px; cursor: pointer; vertical-align: middle; min-width: 80px;">
        </button>
    |
    <?php if(isset($_SESSION['nick'])): ?>
      Logged in as <b class="col_ora"><?= htmlspecialchars($_SESSION['nick']) ?></b> |
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