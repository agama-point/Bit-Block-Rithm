<h3>Posledních 10 bloků</h3>

<table class="tx-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Prev Hash</th>
            <th>TX Root</th>
            <th>Nonce</th>
            <th>Timestamp</th>
            <th>TX Text</th>
            <th>TX Note</th>
            <th>k</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $res = $db->query("SELECT * FROM blockchain ORDER BY id_block DESC LIMIT 10");
        while($row = $res->fetchArray(SQLITE3_ASSOC)):
        ?>
        <tr>
            <td>
                <a href="show_block.php?id=<?= $row['id_block'] ?>" style="color:#fff; font-weight:bold;">
                    #<?= $row['id_block'] ?>
                </a>
            </td>

            <td style="color:#0f0">
                <?= htmlspecialchars($row['prev_hash']) ?>
            </td>

            <td style="color:#0ff">
                <?= htmlspecialchars($row['tx_root']) ?>
            </td>

            <td class="val">
                <?= $row['nonce'] ?>
            </td>

            <td>
                <?= date('ymd | H:i', $row['timestamp']) ?>
            </td>

            <td style="font-size: 0.85em; max-width: 150px; overflow: hidden; text-overflow: ellipsis;">
                <?= htmlspecialchars($row['tx_txt']) ?>
            </td>

            <td>
                <?= htmlspecialchars($row['note_block']) ?>
            </td>

            <td>
                <?= htmlspecialchars($row['k']) ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>