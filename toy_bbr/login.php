<?php
// login.php
// EDUCATIONAL VERSION – plain text passwords

session_start();

$mainDbFile = __DIR__ . "/main.db";
$logDbFile  = __DIR__ . "/pristupy.db";

$msg = "";

try {
    $db = new SQLite3($mainDbFile);
    $logDb = new SQLite3($logDbFile);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $nick = trim($_POST['nick'] ?? "");
        $psw  = $_POST['psw'] ?? "";

        if ($nick === "" || $psw === "") {
            $msg = "Nick and password are required.";
        } else {

            $stmt = $db->prepare("
                SELECT psw, k1 FROM users
                WHERE nick = :nick
            ");
            $stmt->bindValue(':nick', $nick, SQLITE3_TEXT);
            $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

            // ⚠ PLAIN TEXT PASSWORD COMPARISON – INTENTIONALLY INSECURE
            if ($row && $psw === $row['psw']) {

                // ===== LOG =====
                $uid  = time();
                $ip   = $_SERVER['REMOTE_ADDR'] ?? "";
                $note = $nick;
                $ver  = "LogIn";
                $x1   = 0;
                $created = time();

                $stmt2 = $logDb->prepare("
                    INSERT INTO logs (uid, ip, note, ver, x1, created_at)
                    VALUES (:uid, :ip, :note, :ver, :x1, :created)
                ");

                $stmt2->bindValue(':uid', $uid, SQLITE3_INTEGER);
                $stmt2->bindValue(':ip', $ip, SQLITE3_TEXT);
                $stmt2->bindValue(':note', $note, SQLITE3_TEXT);
                $stmt2->bindValue(':ver', $ver, SQLITE3_TEXT);
                $stmt2->bindValue(':x1', $x1, SQLITE3_INTEGER);
                $stmt2->bindValue(':created', $created, SQLITE3_INTEGER);

                $stmt2->execute();
                // ===== END LOG =====


                // ÚSPĚŠNÉ PŘIHLÁŠENÍ
                $_SESSION['nick'] = $nick;
                $_SESSION['k1'] = $row['k1']; 

                header("Location: index.php");
                exit;

            } else {
                $msg = "Invalid nick or password.";
            }
        }
    }

} catch (Exception $e) {
    $msg = "Database error.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="css/bbr.css?ver=1.3">
</head>

<body>

<div class="header" style="text-align: center;">
  <div><b>B·B·R</b> | Login Portal</div>
  <div>
    <a href="index.php">home</a> |
    <a href="create_acc.php">create account</a>
  </div>
</div>

<br />
<div class="box2">

  <h2>Login</h2>

  <p class="warning">
    ⚠ Educational mode: passwords are stored and compared in plain text.
  </p>

  <?php if ($msg): ?>
    <p style="color: #f44;"><?= htmlspecialchars($msg) ?></p>
  <?php endif; ?>

  <form method="post">
    <label>Nick</label><br>
    <input type="text" name="nick" autofocus><br><br>

    <label>Password</label><br>
    <input type="password" name="psw"><br><br>

    <button type="submit">Login</button>
  </form>

</div>

</body>
</html>