<h1 class="digip">MINING</h1>

<div class="flex-wrap">
<div class="flex-left">
   <div class="panel">
   <?php 
   include "u_coinbase.php"; ?>
   </div>
</div>

<div class="flex-right">
   <div class="panel">
   <?php 
   include "show_miner.php"; ?>
   </div>
</div>
</div>



<?php 
include "u_mining_demo.php";
include "table_utxo.php"; 
include "table_tx2m.php";
?>