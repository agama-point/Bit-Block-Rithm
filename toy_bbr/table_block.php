<h3>Blockchain | Last 16 blocks</h3>

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
        $res = $db->query("SELECT * FROM blockchain ORDER BY id_block DESC LIMIT 16");
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

            <td style="font-size: 0.85em; max-width: 250px; overflow: hidden; text-overflow: ellipsis;">
                <?php 
                $tx_raw = trim($row['tx_txt']);
                if (!empty($tx_raw)) {
                    // Rozdělíme text podle čárky
                    $tx_array = explode(',', $tx_raw);
                    $links = [];

                    foreach ($tx_array as $tx_id) {
                        $tx_id = trim($tx_id); // Odstranění mezer
                        if (is_numeric($tx_id)) {
                            // Vytvoření odkazu pro každé ID transakce
                            $links[] = '<a href="show_tx.php?txid=' . urlencode($tx_id) . '" style="color:#0cf; text-decoration:none;">' . htmlspecialchars($tx_id) . '</a>';
                        } else {
                            $links[] = htmlspecialchars($tx_id);
                        }
                    }
                    // Výpis odkazů oddělených čárkou a mezerou
                    echo implode(', ', $links);
                } else {
                    echo '<span style="color:#666;">empty</span>';
                }
                ?>
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