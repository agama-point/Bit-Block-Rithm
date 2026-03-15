<h3>Balance Summary by Owner:</h3>
<table class="tab">
    <thead>
        <tr>
            <th>Owner</th>
            <th>Total Balance (Unspent UTXO)</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $summary = $db->query("
            SELECT owner, SUM(value) AS total_balance
            FROM utxo
            WHERE spent = 0
            GROUP BY owner
            ORDER BY total_balance DESC
        ");
        while($row = $summary->fetchArray(SQLITE3_ASSOC)): ?>
        <tr>
            <td class="addr"><?= $row['owner'] ?></td>
            <td class="val"><?= $row['total_balance'] ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<hr />
<h3>Last 30 UTXOs (Coins in Circulation):</h3>
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
    <tbody>
        <?php
        $res = $db->query("SELECT * FROM utxo ORDER BY id DESC LIMIT 30");
        while($row = $res->fetchArray(SQLITE3_ASSOC)): ?>
        <tr class="spent-<?= $row['spent'] ? 'true' : 'false' ?>">
            <td><?= $row['id'] ?></td>

            <td><a href="show_tx.php?txid=<?= urlencode($row['txid']) ?>"><?= htmlspecialchars($row['txid']) ?></a></td>
            <td class="addr"><?= $row['owner']?></td>
            <td class="val"><?= $row['value'] ?></td>
            <td><?= $row['spent'] ? 'Spent' : 'Available' ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
