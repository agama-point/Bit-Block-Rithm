<h1 class="digip">SYSTEM | TOOLS</h1>

<div class="flex-wrap">
<div class="flex-left">
   <div class="panel">
   <?php 
   include "tool_ash24.php"; ?>
   </div>
</div>

<div class="flex-right">
   <div class="panel">
   <?php 
   include "tool_ash32.php"; ?>
   </div>
</div>
</div>

<div class="box2">
<?php 
   include "tool_ecc251add.php"; 
   include "demo_251sign.php"; 
   include "demo_251script.php"; ?>
</div>

<div class="flex-wrap">
<div class="flex-left">
   <div class="panel">
   <?php 
   include "table_utxo_all.php";  ?>
   </div>
</div>

<div class="flex-right">
   <div class="panel">
   <?php 
   include "show_sys.php";?>
   </div>
</div>
</div>

<?php 
include "db_info.php"; 
?>
