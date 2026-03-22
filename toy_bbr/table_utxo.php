<?php
// table_utxo.php

$db = new SQLite3(__DIR__ . "/main.db");

$current_k1 = $_SESSION['k1'] ?? null;
$nick = $_SESSION['nick'] ?? 'Guest';

$rows = [];
$balance = 0;


// načti UTXO

$res = $db->query("
    SELECT *
    FROM utxo
    ORDER BY id DESC
    LIMIT 70
");

while ($row = $res->fetchArray(SQLITE3_ASSOC)) {

    $rows[] = $row;

}
?>

<div class="box2">

<div class="utxo-container">

<h3 class="col1">Your Unspent Coins (UTXO)</h3>

<p>
User:
<b><?= htmlspecialchars($nick) ?></b>
|
My address:
<span id="my-wallet-addr"
      class="wallet-addr">
deriving...
</span>
</p>

<table class="tab">

<thead>
<tr>
<th>ID</th>
<th>TXID</th>
<th>Owner</th>
<th>Value</th>
<th>Status</th>
</tr>
</thead>

<tbody id="utxo-table-body">

<?php

$found_any = false;

foreach ($rows as $row) {

    $found_any = true;

    $spent_class =
        $row['spent']
        ? 'spent-true'
        : 'spent-false';

    $status_label =
        $row['spent']
        ? 'SPENT'
        : 'UNSPENT';

    $txid =
        htmlspecialchars($row['txid']);

    $link =
        urlencode($row['txid']);

    echo "<tr
        class='utxo-row $spent_class'
        data-owner='{$row['owner']}'
        data-value='{$row['value']}'
        data-spent='{$row['spent']}'
        style='display:none;'>";

    echo "<td>{$row['id']}</td>";

    echo "<td>
    <a href='show_tx.php?txid=$link'>
    $txid
    </a>
    </td>";

    echo "<td class='hex'>
    {$row['owner']}
    </td>";

    echo "<td class='val'>
    <b>{$row['value']}</b>
    </td>";

    echo "<td class='status'>
    $status_label
    </td>";

    echo "</tr>";
}

if (!$found_any) {

    echo "
    <tr>
    <td colspan='5'
    class='empty-msg'>
    The UTXO database is empty.
    </td>
    </tr>
    ";
}

?>

</tbody>

</table>


<p id="no-utxo-msg"
class="empty-msg"
style="display:none;">
No UTXOs were found for your address.
</p>

</div>



<script>

$(function() {

let myPriv =
<?= intval($current_k1 ?? 0) ?>;

if (myPriv !== 0) {

    let myPub =
        scalar_mult(
            myPriv,
            G_POINT
        );

    let myAddr =
        pubkey_to_addr(
            myPub
        );

    $("#my-wallet-addr")
        .text(myAddr);


    let foundCount = 0;

    let balance = 0;


    $(".utxo-row").each(function() {

        let owner =
            $(this).data("owner");

        let val =
            parseInt(
                $(this).data("value")
            );

        let spent =
            parseInt(
                $(this).data("spent")
            );


        if (owner == myAddr) {

            $(this).show();

            foundCount++;

            if (spent === 0) {

                balance += val;

            }

        }

    });


    $("#balance-val")
        .text(balance);


    if (foundCount === 0) {

        $("#no-utxo-msg").show();

    }

}
else {

    $("#my-wallet-addr")
        .text("k1 missing")
        .css("color","red");

}

});

</script>

</div>

<br>