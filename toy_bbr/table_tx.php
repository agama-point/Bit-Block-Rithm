<h3>Last 20 Transactions</h3>
<table class="tab">
    <thead>
        <tr>
            <th>ID</th>
            <th>TXID</th>
            <th>From</th>
            <th>Prev_txid</th>
            <th>To</th>
            <th>Val 1</th>
            <th>Val 2</th>
            <th>Signature</th>
            <th>date_time</th>
            <th>mp</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $res = $db->query("SELECT * FROM transactions ORDER BY id DESC LIMIT 20");
        while($row = $res->fetchArray(SQLITE3_ASSOC)): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            
         <td>
         <a href="show_tx.php?txid=<?= urlencode($row['txid']) ?>">
         <strong><?= htmlspecialchars($row['txid']) ?></strong></a></td>

            <td class="hex"><?= $row['from_addr'] ?></td>

        <td>
         <a href="show_tx.php?txid=<?= urlencode($row['prev_txid']) ?>">
         <strong><?= htmlspecialchars($row['prev_txid']) ?></strong></a></td>

            <td class="hex"><?= $row['to_addr'] ?></td>
            <td class="val"><?= $row['val1'] ?></td>
            <td class="val"><?= $row['val2'] ?></td>
            <td class="sig"><?= $row['sig']?></td>
            <td><?= date('ymd | H:i', $row['utxo_time']) ?></td>
            <td><?= $row['mp']?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>