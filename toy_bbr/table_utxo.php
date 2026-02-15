<?php
// table_utxo.php
$db = new SQLite3(__DIR__ . "/main.db");

$current_k1 = $_SESSION['k1'] ?? null;
$nick = $_SESSION['nick'] ?? 'Guest';
?>

<div class="utxo-container" style="margin-top: 20px;">
    <h3>Your Unspent Coins (UTXO)</h3>
    <p>User: <b><?= htmlspecialchars($nick) ?></b> | 
       My address: <span id="my-wallet-addr" style="color: #0f0; font-weight: bold;">deriving...</span>
    </p>

    <table border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse; background: #000; color: #0f0; border-color: #444;">
        <thead>
            <tr style="background: #222;">
                <th>ID</th>
                <th>TXID</th>
                <th>Owner (Addr)</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody id="utxo-table-body">
            <?php
            // Load all UTXOs, JavaScript will filter by your address
            $res = $db->query("SELECT * FROM utxo ORDER BY id DESC LIMIT 20");
            $found_any = false;

            while($row = $res->fetchArray(SQLITE3_ASSOC)){
                $found_any = true;
                // Add data-owner attribute for easy JS filtering
                $spent_label = $row['spent'] ? '<span style="color:red">SPENT</span>' : '<span style="color:#0f0">UNSPENT</span>';
                $row_style = $row['spent'] ? 'opacity: 0.5;' : '';
                
                echo "<tr class='utxo-row' data-owner='{$row['owner']}' style='display:none; $row_style'>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['txid']}</td>";
                echo "<td><code>{$row['owner']}</code></td>";
                echo "<td><b>{$row['value']}</b></td>";
                echo "<td>$spent_label</td>";
                echo "</tr>";
            }

            if (!$found_any) {
                echo "<tr><td colspan='5' style='text-align:center'>The UTXO database is empty.</td></tr>";
            }
            ?>
        </tbody>
    </table>
    <p id="no-utxo-msg" style="display:none; color: #888;">No UTXOs were found for your address.</p>
</div>

<script>
$(function() {
    // 1. Derive address from k1 (same as in mining)
    let myPriv = <?= intval($current_k1 ?? 0) ?>;
    
    if (myPriv !== 0) {
        let myPub = scalar_mult(myPriv, G_POINT);
        let myAddr = pubkey_to_addr(myPub);
        
        $("#my-wallet-addr").text(myAddr);

        // 2. Filter table – show only rows belonging to my address
        let foundCount = 0;
        $(".utxo-row").each(function() {
            if ($(this).data("owner") == myAddr) {
                $(this).show();
                foundCount++;
            }
        });

        if (foundCount === 0) {
            $("#no-utxo-msg").show();
        }
    } else {
        $("#my-wallet-addr").text("k1 missing – cannot lookup UTXOs").css("color", "red");
    }
});
</script>
