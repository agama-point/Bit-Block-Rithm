<h1 class="digip col3">BLOCKCHAIN</h1>
<div class="grad_line"></div>

<div class="h-center">
<button id="toggleBtn">Show detail</button><br />
</div>
<div class="h-center">
<img id="mySvg" src="svg/bbr_block.svg" alt="Blockchain" width="800" style="display:none;">
</div>

<script>
$(document).ready(function() {
  $('#toggleBtn').click(function() {
    if ($('#mySvg').is(':visible')) {
      $('#mySvg').slideUp(1000);
      $('#toggleBtn').text('Show detail');
    } else {
      $('#mySvg').slideDown(500);
      $('#toggleBtn').text('Hide detail');
    }
  });
});
</script>
<br /><br />

<div class="grad_line"></div>

<?php 
include "table_block.php"; 

include "chart_tx.php";

include "table_tx.php"; 
?>
