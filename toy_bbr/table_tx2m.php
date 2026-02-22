<?php
// --- Načtení posledního bloku pro potřeby JavaScriptu ---
$last_block_query = $db->query("
    SELECT id_block, prev_hash, timestamp, tx_root, nonce 
    FROM blockchain 
    ORDER BY id_block DESC 
    LIMIT 1
");

$last_block = $last_block_query ? $last_block_query->fetchArray(SQLITE3_ASSOC) : false;

// Pokud blok neexistuje, nastavíme prázdné hodnoty (např. pro Genesis)
$lb_id    = $last_block ? $last_block['id_block'] : 0;
$lb_prev  = $last_block ? $last_block['prev_hash'] : 0;
$lb_ts    = $last_block ? $last_block['timestamp'] : 0;
$lb_root  = $last_block ? $last_block['tx_root'] : '000000';
$lb_nonce  = $last_block ? $last_block['nonce'] : '99999';

?>

<div id="last-block-data" 
     data-id="<?= $lb_id ?>" 
     data-prev="<?= $lb_prev ?>" 
     data-ts="<?= $lb_ts ?>" 
     data-root="<?= $lb_root ?>" 
     data-nonce="<?= $lb_nonce ?>" 
     style="display:none;">
</div>

<div style="background: #222; color: #eee; padding: 10px; margin-bottom: 10px; border: 1px solid #444;">
    <strong>Last Block Info:</strong> 
    ID: <?= $lb_id ?> | 
    TS: <?= $lb_ts ?> | 
    TX_ROOT: <span style="color: #0f0;"><?= $lb_root ?> | <?= $lb_nonce ?><br />
    :.:<?= $lb_id ?>|<?= $lb_ts ?>|<?= $lb_root ?>:.:
    </span>
</div>

<h3>Last Transactions (mp = 1) | Mempool</h3>
<form id="tx-form">
<table class="tx-table">
    <thead>
        <tr>
            <th>Select</th>
            <th>ID</th>
            <th>TXID</th>
            <th>From</th>
            <th>Prev_txid</th>
            <th>To</th>
            <th>Val 1</th>
            <th>Val 2</th>
            <th>Signature</th>
            <th>Date/Time</th>
            <th>mp</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $res = $db->query("SELECT * FROM transactions WHERE mp=1 ORDER BY id DESC LIMIT 30");
        while($row = $res->fetchArray(SQLITE3_ASSOC)): ?>
        <tr>
            <td><input type="checkbox" name="tx_ids[]" value="<?= $row['txid'] ?>"></td>
            <td><?= $row['id'] ?></td>
            <td style="color:#0f0">
                <a href="show_tx.php?txid=<?= urlencode($row['txid']) ?>">
                    <strong><?= htmlspecialchars($row['txid']) ?></strong>
                </a>
            </td>
            <td class="addr"><?= $row['from_addr'] ?></td>
            <td class="val"><?= $row['prev_txid'] ?></td>
            <td class="addr"><?= $row['to_addr'] ?></td>
            <td class="val"><?= $row['val1'] ?></td>
            <td class="val"><?= $row['val2'] ?></td>
            <td class="sig"><?= $row['sig'] ?></td>
            <td><?= date('ymd | H:i', $row['utxo_time']) ?></td>
            <td><?= $row['mp'] ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<button type="button" id="mine-btn">Mining</button>
</form>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$('#mine-btn').click(function(){

    let selected_txids = [];
    // Sbíráme hodnoty TXID z vybraných checkboxů
    $('input[name="tx_ids[]"]:checked').each(function(){
        selected_txids.push($(this).val());
    });

    if(selected_txids.length === 0){
        alert("Select at least one transaction.");
        return;
    }

    // 1. Získání dat posledního bloku z HTML atributů
    let lb = $('#last-block-data').data();
    
    // 2. Výpočet tx_root (seznam TXID oddělený čárkou)
    // Toto pole se uloží do blockchainu jako "tx_txt"
    let tx_list_string = selected_txids.join(","); 
    let raw_tx  = window.ASH24(tx_list_string);
    let tx_root = window.hex24(raw_tx);

    // 3. Výpočet prev_hash (ze starého bloku)
    let prev_string = lb.id + "|" + lb.prev + "|"+ lb.ts + "|" + lb.root + "|" + lb.nonce;
    let raw_prev    = window.ASH24(prev_string);
    let prev_hash   = window.hex24(raw_prev);

    let nonce = Math.floor(Math.random() * (99999 - 100 + 1)) + 100;    

    // --- POST mining request ---
    $.post('index.php', { 
        action: 'mine_tx', 
        tx_ids: selected_txids, // Posíláme pole TXID
        tx_root: tx_root,
        prev_hash: prev_hash,
        nonce: nonce,
        tx_text: tx_list_string // Přidáno pro uložení seznamu do tabulky blockchain
    }, function(response){
        try {
            let res = (typeof response === "string") ? JSON.parse(response) : response;
            if(res.status === 'ok'){
                alert(res.updated + " transaction(s) mined!\nBlock hash: " + tx_root);
                location.reload();
            } else {
                alert("Error: " + res.msg);
            }
        } catch(e) {
            console.error("JSON Parse error:", response);
            alert("Server returned invalid response.");
        }
    });
});
</script>