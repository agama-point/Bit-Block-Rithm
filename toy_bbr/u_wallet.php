<h1 class="digip">WALLET</h1>


<div class="flex-wrap">
<div class="flex-left">
   <div class="panel">
   <?php 
   include "u_send.php"; ?>
   </div>
</div>

<div class="flex-right">
   <div class="panel">
   <?php 
   include "u_recieve.php"; ?>
   </div>
</div>
</div>


<?php 

include "table_utxo.php";

include "table_tx.php"; 

?>




