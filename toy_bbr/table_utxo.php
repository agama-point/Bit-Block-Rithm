<?php
// table_utxo.php
$db = new SQLite3(__DIR__ . "/main.db");

$current_k1 = $_SESSION['k1'] ?? null;
$nick = $_SESSION['nick'] ?? 'Guest';
?>

<div class="utxo-container">
    <h3>Your Unspent Coins (UTXO)</h3>
    <p>
       User: <b><?= htmlspecialchars($nick) ?></b> | 
       My address: <span id="my-wallet-addr" class="wallet-addr">deriving...</span>
    </p>

    <table class="tx-table utxo-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>TXID</th>
                <th>Owner (Addr)</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody id="utxo-table-body">
            <?php
            $res = $db->query("SELECT * FROM utxo ORDER BY id DESC LIMIT 20");
            $found_any = false;

            while($row = $res->fetchArray(SQLITE3_ASSOC)){
                $found_any = true;

                $spent_class = $row['spent'] ? 'spent-true' : 'spent-false';
                $status_label = $row['spent'] ? 'SPENT' : 'UNSPENT';

                echo "<tr class='utxo-row $spent_class' data-owner='{$row['owner']}' style='display:none;'>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['txid']}</td>";
                echo "<td><code>{$row['owner']}</code></td>";
                echo "<td><b>{$row['value']}</b></td>";
                echo "<td class='status'>$status_label</td>";
                echo "</tr>";
            }

            if (!$found_any) {
                echo "<tr><td colspan='5' class='empty-msg'>The UTXO database is empty.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <p id="no-utxo-msg" class="empty-msg" style="display:none;">
        No UTXOs were found for your address.
    </p>
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
