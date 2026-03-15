<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$mainDbFile = __DIR__ . "/main.db"; 
$id_block = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Základní ošetření vstupu
if ($id_block <= 0) { die("Invalid block ID."); }

try {
    $db = new SQLite3($mainDbFile);

    // 1. Načtení HLAVNÍHO bloku (toho, co je v tabulce dole)
    $stmt = $db->prepare("SELECT * FROM blockchain WHERE id_block = :id LIMIT 1");
    $stmt->bindValue(':id', $id_block, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $block = $result->fetchArray(SQLITE3_ASSOC);

    // Pokud hlavní blok neexistuje, vypíšeme chybu v rámci designu
    if (!$block) {
        $error_msg = "Block #$id_block does not exist in the database.";
    } else {
        // 2. Načtení informací o PŘEDCHOZÍM bloku (pro ten prostřední navigační blok)
        $prev_id = $id_block - 1;
        $stmt_prev = $db->prepare("SELECT id_block, prev_hash, timestamp, tx_root, nonce FROM blockchain WHERE id_block = :prev LIMIT 1");
        $stmt_prev->bindValue(':prev', $prev_id, SQLITE3_INTEGER);
        $res_prev = $stmt_prev->execute();
        $prev_block = $res_prev->fetchArray(SQLITE3_ASSOC);
    }

} catch (Exception $e) { die("DB error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Block #<?= $id_block ?></title>
    <link rel="stylesheet" href="css/bbr.css?v=2">
    <script src="js/jquery.min.js"></script>
    <script src="js/agama_bech32.js"></script>
    <script src="js/ash24.js"></script>
    <style>
        
.block-navigation { 
    display: flex; 
    align-items: stretch; 
    gap: 10px; 
    margin-bottom: 25px;
    width: 100%; /* Zajistí využití celého prostoru kontejneru */
}

.block-info { 
    padding: 10px; 
    box-sizing: border-box;
    /* Odstraněno display: flex a justify-content, aby se text mohl roztáhnout */
    min-width: 0; /* Klíčové pro správné fungování flex-grow u sousedů */
}

.side-block { 
    width: 60px; 
    min-width: 60px; /* Garantuje minimální šířku */
    flex-shrink: 0;  /* Zakáže prohlížeči čtverec zmenšovat */
    text-align: center; 
    font-weight: bold;
    display: flex; /* Pouze pro centrování čísla uvnitř malého čtverce */
    align-items: center;
    justify-content: center;
}

.main-prev-info { 
    flex: 1; /* Zabere 100% zbývajícího místa */
    display: block; /* Text se bude chovat jako standardní blok */
    word-wrap: break-word; /* Pojistka pro extrémně dlouhé texty */
}

.nav-arrow { 
    display: flex; 
    align-items: center; 
    color: var(--color-gre);
    font-weight: bold; 
    font-size: 2em; 
    padding: 0 5px; 
    flex-shrink: 0; 
}

/* Pomocná třída pro řádky uvnitř prostředního bloku, aby držely pohromadě */
.info-line {
    display: block;
    white-space: nowrap; /* Zabrání zalomení uvnitř popisků, pokud je dost místa */
}  
        
    </style>

</head>

<body>
<script>
    if (localStorage.getItem('theme') === 'light') { document.body.classList.add('light-mode'); }
</script>

<div class="container">
    <h1 class="digip">Block Explorer</h1>

    <?php if (isset($error_msg)): ?>
        <div class="block-info" style="border-color: red; color: red;">
            <strong>ERROR:</strong> <?= $error_msg ?>
            <br><a href="index.php?page=blockchain" style="color: #fff;">&larr; Return to list</a>
        </div>
    <?php else: ?>
        
        <div class="block-navigation">
            <div class="box2 block-info side-block">
                <?php if ($id_block > 1): ?>
                    <a href="show_block.php?id=<?= $id_block - 1 ?>" class="block-link">#[-1]</a>
                <?php else: ?>
                    <span class="dim">NULL</span>
                <?php endif; ?>
            </div>

            <div class="nav-arrow">&rarr;</div>

            <div class="box1 block-info main-prev-info">
                <?php if ($prev_block): 
                    $raw_header = $prev_block['id_block'] . '|' . 
                                  $prev_block['prev_hash'] . '|' . 
                                  $prev_block['timestamp'] . '|' . 
                                  ($prev_block['tx_root'] ?: 'NULL') . '|' . 
                                  ($prev_block['nonce'] ?: 'NULL');
                ?>
                    <strong>Previous block: Header #<?= $prev_block['id_block'] ?></strong> | 
                    PrevHash: <span class="col_vio"><?= $prev_block['prev_hash'] ?></span> | 
                    Time: <span class="num-val"><?= $prev_block['timestamp'] ?></span><br />
                    TX_Root: <span class="col_vio"><?= htmlspecialchars($prev_block['tx_root'] ?: 'NULL') ?></span> | 
                    Nonce: <span class="col_gre"><?= htmlspecialchars($prev_block['nonce'] ?: 'NULL') ?></span>
                    
                    <div class="raw-data-box">
                        :.: <span class="col_gre" id="header-to-hash"><?= htmlspecialchars($raw_header) ?></span> 
                        &rarr; <span id="real-time-hash" class="col_vio">...</span>
                    </div>
                <?php else: ?>
                    <div style="text-align:center;" class="dim">GENESIS BLOCK: NO PARENT HEADER DATA</div>
                <?php endif; ?>
            </div>

            <div class="nav-arrow">&rarr;</div>

            <div class="box2 block-info side-block">
                <a href="show_block.php?id=<?= $id_block + 1 ?>" class="block-link">#[+1]</a>
            </div>
        </div> 

        <h2>Block details #<?= $block['id_block'] ?></h2>
<div class="box2">
        <table class="tab">
            <tr><td>Block ID</td><td class="num-val"><?= $block['id_block'] ?></td></tr>
            <tr class="col_vio"><td>Prev Hash (from Parent) :.:</td>
<td class="col_vio"><?= htmlspecialchars($block['prev_hash'] ?: '0000000000000000') ?></td></tr>
            
            <tr class="col_gre"><td>Nonce</td><td><?= $block['nonce'] ?></td></tr>
            <tr>
                <td>Timestamp</td>
                <td class="col_gre">
                    <?= $block['timestamp'] ?> 
                    <span style="color:#777; margin-left:10px;">(<?= date("Y-m-d H:i:s", $block['timestamp']) ?>)</span>
                </td>
            </tr>
            <tr><td>Transactions (IDs)</td><td style="color:#aaa; font-size:0.9em;"><?= htmlspecialchars($block['tx_txt']) ?></td></tr>
            <tr><td>TX Root</td>
<td class="col_vio"><?= htmlspecialchars($block['tx_root']) ?></td></tr>
            <tr>
<td>Block Note</td><td><?= htmlspecialchars($block['note_block']) ?></td></tr>
            <tr><td>K (parameter)</td>
<td><?= htmlspecialchars($block['k']) ?></td></tr>
        </table>
</div>


    <?php endif; ?>

    <div style="margin-top: 30px;">
        <a href="index.php?page=blockchain" style="color: #0f0; text-decoration: none;">&larr; Back to overview</a>
    </div>

    <?php if (file_exists("u_tools.php")) { include "u_tools.php"; } ?>
</div>

<script>
$(document).ready(function() {
    var rawString = $('#header-to-hash').text().trim();
    if (rawString && rawString !== "") {
        try {
            // Výpočet hashe pomocí ash24.js
            var h = hex24(ASH24(rawString));
            if (h.startsWith('0x')) h = h.substring(2);
            $('#real-time-hash').text(h);
        } catch (err) {
            console.error(err);
            $('#real-time-hash').text("err");
        }
    }
});
</script>
</body>
</html>