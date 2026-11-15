<?php
include_once 'database.php';
include_once 'auth.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: home.php");
    exit;
}

$errorMsg = "";
if (isset($_GET['redirect']) && $_GET['redirect'] === 'home') {
    $errorMsg = "Sessione scaduta o accesso non autorizzato.<br>Effettua nuovamente il login.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errorMsg = "Inserisci username e password.";
    } else {
        $sql = "SELECT username, password, livello FROM login WHERE username = ? LIMIT 1";
        if ($stmt = $sxConn->prepare($sql)) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($user = $res->fetch_assoc()) {
                $storedHash = $user['password'];
                $loginValido = false;

                if (password_verify($password, $storedHash)) {
                    $loginValido = true;
                } elseif (strlen($storedHash) === 32 && hash_equals($storedHash, md5($password))) {
                    $loginValido = true;
                    $nuovoHash = password_hash($password, PASSWORD_DEFAULT);
                    if ($upd = $sxConn->prepare("UPDATE login SET password = ? WHERE username = ?")) {
                        $upd->bind_param("ss", $nuovoHash, $user['username']);
                        $upd->execute();
                        $upd->close();
                    }
                }

                if ($loginValido) {
                    session_regenerate_id(true);
                    $_SESSION['logged_in'] = true;
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['livello'] = (int)$user['livello'];
                    header("Location: home.php");
                    exit;
                }
            }
            $errorMsg = "Username o Password errati.";
            $stmt->close();
        } else {
            $errorMsg = "Errore tecnico di connessione al database.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <title>Login - ShuttleX</title>
    <link rel="manifest" href="manifest.json">
    <?php include_once 'styles/header-include.php'; ?>
</head>
<body>
    <nav>
        <div class="pure-g" style="align-items: center;">
            <div class="pure-u-1-2">
                <a href="index.php" style="text-decoration: none;">
                    <h2 class="c-margin-not" style="margin-top:0;">SX</h2>
                </a>
            </div>
            <div class="pure-u-1-2" style="text-align: right;">
                <p class="c-margin-not" style="color: #666; margin-top:0;">
                    <?= date('d/m/Y') ?>
                </p>
            </div>
        </div>
    </nav>

    <?php include_once 'manutenzione.php'; ?>

    <div class="w3-container" style="max-width: 500px; margin: auto; padding-top: 32px;">
        <div class="w3-panel w3-card-4 w3-white w3-round-large w3-padding-16">
            <h3 class="w3-center c-primary">
                <i class="fa-solid fa-user-lock"></i> Area Riservata
            </h3>
            <hr>
            <?php if (!empty($errorMsg)): ?>
                <div class="w3-panel w3-red w3-display-container w3-round w3-animate-opacity">
                    <span onclick="this.parentElement.style.display='none'" class="w3-button w3-red w3-large w3-display-topright">&times;</span>
                    <p><?= $errorMsg ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" class="w3-container">
                <div class="w3-row w3-margin-bottom">
                    <label class="w3-text-grey"><b>Username</b></label>
                    <div class="w3-col s1 w3-center w3-padding-small">
                        <i class="fa-solid fa-user w3-large w3-text-grey"></i>
                    </div>
                    <div class="w3-col s11">
                        <input class="w3-input w3-border w3-round" type="text" name="username" required autocomplete="username" placeholder="Inserisci il tuo username">
                    </div>
                </div>

                <div class="w3-row w3-margin-bottom">
                    <label class="w3-text-grey"><b>Password</b></label>
                    <div class="w3-col s1 w3-center w3-padding-small">
                        <i class="fa-solid fa-key w3-large w3-text-grey"></i>
                    </div>
                    <div class="w3-col s11">
                        <input class="w3-input w3-border w3-round" type="password" name="password" required autocomplete="current-password" placeholder="Inserisci la tua password">
                    </div>
                </div>

                <p class="w3-center w3-padding-16">
                    <button class="w3-button w3-block w3-blue w3-round w3-hover-light-blue w3-card-2" type="submit">
                        ACCEDI
                    </button>
                </p>

                <h4 class="w3-center c-primary">
                    <i class="fa-regular fa-envelope"></i>
                    <a href="mailto:<?= htmlspecialchars($mailAssistenza) ?>?subject=Richiesta%20credenziali%20ShuttleX">
                        Richiedi Credenziali / Assistenza
                    </a>
                </h4>
            </form>
        </div>

        <?php include_once 'components/disclaimer-box.php'; ?>
    </div>
</body>
</html>