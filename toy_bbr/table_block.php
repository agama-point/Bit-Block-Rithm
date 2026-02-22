<h3>Blockchain | Last 16 blocks</h3>

<table class="tx-table blockchain-table">
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
        $res = $db->query("SELECT * FROM blockchain ORDER BY id_block DESC LIMIT 16");
        while($row = $res->fetchArray(SQLITE3_ASSOC)):
        ?>
        <tr>
            <td>
                <a href="show_block.php?id=<?= $row['id_block'] ?>" class="block-id">
                    #<?= $row['id_block'] ?>
                </a>
            </td>

            <td class="hash-prev"><?= htmlspecialchars($row['prev_hash']) ?></td>
            <td class="hash-root"><?= htmlspecialchars($row['tx_root']) ?></td>
            <td class="val"><?= $row['nonce'] ?></td>
            <td><?= date('ymd | H:i', $row['timestamp']) ?></td>

            <td class="tx-text">
                <?php 
                $tx_raw = trim($row['tx_txt']);
                if (!empty($tx_raw)) {

                    $tx_array = explode(',', $tx_raw);
                    $links = [];

                    foreach ($tx_array as $tx_id) {
                        $tx_id = trim($tx_id);

                        if (is_numeric($tx_id)) {
                            $links[] = '<a href="show_tx.php?txid=' . urlencode($tx_id) . '" class="tx-link">' . htmlspecialchars($tx_id) . '</a>';
                        } else {
                            $links[] = htmlspecialchars($tx_id);
                        }
                    }

                    echo implode(', ', $links);
                } else {
                    echo '<span class="empty">empty</span>';
                }
                ?>
            </td>

            <td><?= htmlspecialchars($row['note_block']) ?></td>
            <td><?= htmlspecialchars($row['k']) ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
