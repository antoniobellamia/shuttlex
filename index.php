<?php
include_once 'database.php';
include_once 'auth.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: home.php");
    exit;
}

$errorMsg = "";
if (isset($_GET['redirect']) && $_GET['redirect'] === 'home') {
    $errorMsg = "Sessione scaduta o accesso non autorizzato. Effettua nuovamente il login.";
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
    <title>Accedi - ShuttleX</title>
    <meta name="robots" content="noindex, nofollow">
    <?php include_once 'styles/header-include.php'; ?>
</head>
<body>
    <header class="app-header">
        <div class="container">
            <div class="topbar" style="margin-bottom: 0;">
                <a href="index.php" class="brand-logo">
                    <i class="fa-solid fa-bus-simple" aria-hidden="true"></i>
                    <span>SX <span class="sub">ShuttleX</span></span>
                </a>
                <span class="status-meta"><?= date('d/m/Y') ?></span>
            </div>
        </div>
    </header>

    <?php include_once 'manutenzione.php'; ?>

    <main id="main-content" class="container" style="max-width: 480px;">
        <section class="card" aria-labelledby="login-heading">
            <h1 id="login-heading" style="margin: 0 0 6px; font-size: 1.35rem; color: var(--brand-dark);">
                <i class="fa-solid fa-user-lock" aria-hidden="true"></i> Area Riservata
            </h1>
            <p style="margin: 0 0 18px; color: var(--text-muted); font-size: 0.9rem;">
                Accedi per consultare gli orari aggiornati della navetta CampusX.
            </p>

            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
                <div class="field-group" style="margin-bottom: 14px;">
                    <label for="username" class="field-label">Username</label>
                    <input id="username" class="form-control" type="text" name="username" required autocomplete="username" placeholder="Inserisci il tuo username">
                </div>

                <div class="field-group" style="margin-bottom: 18px;">
                    <label for="password" class="field-label">Password</label>
                    <div style="position: relative;">
                        <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password" placeholder="Inserisci la tua password" style="padding-right: 46px;">
                        <button type="button"
                                onclick="togglePass()"
                                aria-label="Mostra o nascondi password"
                                style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; width: 38px; height: 38px;">
                            <i id="eyeIcon" class="fa-regular fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <button class="btn btn-primary" type="submit" style="width: 100%; font-size: 1rem;">
                    Accedi <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
            </form>

            <div style="text-align: center; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border);">
                <a href="mailto:<?= htmlspecialchars($mailAssistenza) ?>?subject=Richiesta%20credenziali%20ShuttleX"
                   style="color: var(--brand-dark); font-weight: 600; font-size: 0.9rem; text-decoration: none;">
                    <i class="fa-regular fa-envelope" aria-hidden="true"></i> Non hai le credenziali? Richiedile qui
                </a>
            </div>
        </section>

        <?php include_once 'components/disclaimer-box.php'; ?>
    </main>

    <script>
    function togglePass() {
        const inp = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        const show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        icon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    }
    </script>
</body>
</html>