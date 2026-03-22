<h1 class="digip col3">SYSTEM | TOOLS</h1>
<div class="grad_line"></div>

<div class="flex-wrap">
<div class="flex-left">   
   <?php 
   include "tool_ash24.php"; ?>   
</div>

<div class="flex-right">  
   <?php 
   include "tool_ash32.php"; ?>  
</div>
</div>

<div class="box2">
<?php 
   include "tool_ecc251add.php"; 
   include "demo_251sign.php"; 
   include "demo_251script.php"; ?>
</div>
<br />

<div class="flex-wrap">
<div class="flex-left">
   <div class="box1">
   <?php 
   include "table_utxo_all.php";  ?>
   </div>
</div>

<div class="flex-right">
   
   <?php 
   include "show_sys.php";?>
   
</div>
</div>

<?php 
//include "chart_tx.php";
include "db_info.php"; 
?>
