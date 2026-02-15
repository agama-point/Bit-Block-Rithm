<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$mainDbFile = __DIR__ . "/main.db"; // Ujisti se, že cesta odpovídá tvé DB
$id_block = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_block <= 0) {
    die("Neplatné ID bloku.");
}

try {
    $db = new SQLite3($mainDbFile);

    // 1. Načtení aktuálního bloku
    $stmt = $db->prepare("SELECT * FROM blockchain WHERE id_block = :id LIMIT 1");
    $stmt->bindValue(':id', $id_block, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $block = $result->fetchArray(SQLITE3_ASSOC);

    if (!$block) {
        die("Blok nebyl nalezen.");
    }

    // 2. Načtení informací o PŘEDCHOZÍM bloku (pro "Last Block Info")
    $prev_id = $id_block - 1;
    $stmt_prev = $db->prepare("SELECT id_block, timestamp, tx_root FROM blockchain WHERE id_block = :prev LIMIT 1");
    $stmt_prev->bindValue(':prev', $prev_id, SQLITE3_INTEGER);
    $res_prev = $stmt_prev->execute();
    $prev_block = $res_prev->fetchArray(SQLITE3_ASSOC);

} catch (Exception $e) {
    die("Chyba DB: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Block #<?= $block['id_block'] ?></title>
    <style>
        body { font-family: 'Courier New', monospace; background:#111; color:#0f0; padding: 20px; line-height: 1.5; }
        .container { max-width: 900px; margin: 0 auto; }
        
        table { border-collapse: collapse; width: 100%; margin-top: 20px; background: #181818; }
        td { padding: 12px; border: 1px solid #333; }
        td:first-child { width: 180px; font-weight: bold; color: #fff; background: #222; }
        
        .last-block-info { 
            background: #002200; 
            border: 1px solid #0f0; 
            padding: 15px; 
            margin-bottom: 20px; 
            font-size: 0.9em;
        }
        
        .hash-val { color: #0ff; word-break: break-all; }
        .timestamp { color: #ff0; }
        h1, h2 { border-bottom: 1px solid #333; padding-bottom: 10px; }
        .tx-list { color: #aaa; font-size: 0.9em; }
    </style>
</head>
<body>

<div class="container">
    <h1>Block Explorer</h1>

    <div class="last-block-info">
        <strong>Last Block Info (Parent):</strong><br>
        <?php if ($prev_block): ?>
            ID: <span class="timestamp"><?= $prev_block['id_block'] ?></span> | 
            timestamp: <span class="timestamp"><?= $prev_block['timestamp'] ?></span> | 
            TX_ROOT: <span class="hash-val"><?= htmlspecialchars($prev_block['tx_root'] ?: 'NULL') ?>

:.:<?= $prev_block['id_block']?>|<?= $prev_block['timestamp'] ?>|<?= htmlspecialchars($prev_block['tx_root'] ?: 'NULL') ?>
</span>
        <?php else: ?>
            <span class="dim">Tento blok je Genesis (první blok), nemá předchůdce.</span>
        <?php endif; ?>
    </div>

    <h2>Block details #<?= $block['id_block'] ?></h2>

    <table>
        <tr>
            <td>ID bloku</td>
            <td><?= $block['id_block'] ?></td>
        </tr>
        <tr>
            <td>Prev Hash</td>
            <td class="hash-val"><?= htmlspecialchars($block['prev_hash'] ?: '0000000000000000') ?></td>
        </tr>
        <tr>
            <td>TX Root (Hash)</td>
            <td class="hash-val"><?= htmlspecialchars($block['tx_root']) ?></td>
        </tr>
        <tr>
            <td>Nonce</td>
            <td><?= $block['nonce'] ?></td>
        </tr>
        <tr>
            <td>Timestamp</td>
            <td class="timestamp">
                <?= $block['timestamp'] ?> 
                <span style="color:#666; font-size:0.8em; margin-left:10px;">
                    (<?= date("Y-m-d H:i:s", $block['timestamp']) ?>)
                </span>
            </td>
        </tr>
        <tr>
            <td>Seznam TX (ID)</td>
            <td class="tx-list"><?= htmlspecialchars($block['tx_txt']) ?></td>
        </tr>
        <tr>
            <td>Note Block</td>
            <td><?= htmlspecialchars($block['note_block']) ?></td>
        </tr>
        <tr>
            <td>K (parametr)</td>
            <td><?= htmlspecialchars($block['k']) ?></td>
        </tr>
    </table>

    <div style="margin-top: 30px;">
        <a href="index.php?page=blockchain" style="color: #0f0; text-decoration: none;">&larr; Back to overview</a>
    </div>
</div>

</body>
</html>