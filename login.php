<?php
require_once 'config.php';
require_once 'functions.php';

// Se l'utente è già loggato, reindirizza alla dashboard
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

// Processa il form di login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Per favore inserisci username e password.';
    } else {
        $user = verify_login($username, $password);

    if ($user) {
    error_log("Login successo: " . print_r($user, true));
} else {
    error_log("Login fallito per username: " . $username);
}

        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['ruolo'];
            $_SESSION['user_email'] = $user['email'] ?? '';
            
            // Reindirizzamento assoluto
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            header("Location: $protocol://$host$path/index.php");
            exit;
        } else {
            $error = 'Username o password non validi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <!-- Header con logo e nome organizzazione -->
            <div class="organization-header">
                <div class="organization-logo">
                    <!-- Sostituisci con il percorso del tuo logo -->
                    <img src="logo.png" alt="Pubblica Assistenza LaMia Soccorso OdV" class="logo-img">
                </div>
                <div class="organization-name">
                    <h2>P.A. LaMia Soccorso OdV</h2>
                </div>
            </div>
            
            <div class="login-header">
                <h3>Accedi al Sistema</h3>
                <p class="text-muted">Inserisci le tue credenziali per accedere</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="post">
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user me-2"></i>Username
                    </label>
                    <input type="text" id="username" name="username" class="form-control" required autofocus placeholder="Inserisci il tuo username">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Password
                    </label>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="Inserisci la tua password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt me-2"></i>Accedi
                </button>
            </form>
            
            <div class="login-footer">
                <p class="text-muted">&copy; <?php echo date(
                    "Y",
                ); ?> Gestione Eventi. Tutti i diritti riservati.</p>
            </div>
        </div>
    </div>
</body>
</html>
