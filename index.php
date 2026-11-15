<?php
include_once __DIR__ . '/auth.php';

if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: home.php");
    exit;
}

$errorMsg = "";
if (isset($_GET['redirect']) && $_GET['redirect'] === 'home') {
    $errorMsg = "Sessione scaduta o accesso non autorizzato. Effettua nuovamente il login.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (!controllaRateLimit($sxConn, $ip)) {
        $errorMsg = "Troppi tentativi falliti. Riprova tra 15 minuti.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $ricordami = isset($_POST['ricordami']);

        if ($username === '' || $password === '') {
            $errorMsg = "Inserisci username e password.";
        } else {
            $stmt = $sxConn->prepare("SELECT id, username, password, livello FROM login WHERE username = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                $loginValido = false;
                if ($user) {
                    $storedHash = $user['password'];
                    if (password_verify($password, $storedHash)) {
                        $loginValido = true;
                    } elseif (strlen($storedHash) === 32 && hash_equals($storedHash, md5($password))) {
                        $loginValido = true;
                        $nuovoHash = password_hash($password, PASSWORD_DEFAULT);
                        $upd = $sxConn->prepare("UPDATE login SET password = ? WHERE id = ?");
                        $upd->bind_param("si", $nuovoHash, $user['id']);
                        $upd->execute();
                        $upd->close();
                    }
                }

                if ($loginValido) {
                    resetTentativi($sxConn, $ip);
                    session_regenerate_id(true);
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['livello'] = (int)$user['livello'];

                    if ($ricordami) {
                        creaTokenPersistente($sxConn, (int)$user['id']);
                    }

                    header("Location: home.php");
                    exit;
                } else {
                    registraTentativoFallito($sxConn, $ip);
                    $errorMsg = "Username o Password errati.";
                }
            } else {
                $errorMsg = "Errore tecnico di connessione al database.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <title>Accedi - ShuttleX</title>
    <meta name="robots" content="noindex, nofollow">
    <?php include_once __DIR__ . '/styles/header-include.php'; ?>
</head>
<body>
    <header class="app-topbar">
        <div class="container topbar-inner">
            <a href="home.php" class="brand-logo">
                <span class="brand-badge"><i class="fa-solid fa-bus-simple" aria-hidden="true"></i> SX</span>
                <span>ShuttleX</span>
            </a>
            <span class="status-meta"><?= date('d/m/Y') ?></span>
        </div>
    </header>

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

                <div class="field-group" style="margin-bottom: 14px;">
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

                <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px; font-size: 0.9rem; cursor: pointer;">
                    <input type="checkbox" name="ricordami" checked style="width: 18px; height: 18px;">
                    Resta connesso su questo dispositivo (30 giorni)
                </label>

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

        <section class="disclaimer-box" aria-labelledby="disclaimer-heading">
            <h2 id="disclaimer-heading">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Informazioni e Condizioni d'Uso
            </h2>
            <p style="margin: 0 0 8px;">
                Questo portale è un'iniziativa indipendente sviluppata a <strong>puro scopo informativo</strong> e senza fini di lucro per agevolare la consultazione degli orari della navetta. <strong>Non riveste carattere di ufficialità.</strong>
            </p>
            <ul class="disclaimer-list">
                <li>L'accesso agli orari è limitato tramite autenticazione per tutelare la riservatezza del servizio di trasporto privato.</li>
                <li>Le credenziali vengono rilasciate su richiesta all'assistenza.</li>
                <li>Gli orari, pur aggiornati regolarmente, potrebbero differire dalle disposizioni dell'amministrazione in tempo reale.</li>
            </ul>
        </section>
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