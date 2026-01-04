<?php
require_once 'config.php';
require_once 'functions.php';

// Verifica se l'utente è loggato
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Verifica se l'utente ha accesso a questa pagina
if (!has_access('rifiutati')) {
    header('Location: login.php');
    exit;
}

// Ottieni tutti gli eventi rifiutati
$rejected_events = get_rejected_events();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventi Rifiutati - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">
                    <img src="logo.png" alt="Pubblica Assistenza LaMia Soccorso OdV" class="logo-img">
                </div>
                <ul>
                    <li><a href="index.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a></li>
                    <li><a href="eventi.php">
                        <i class="fas fa-calendar-plus me-2"></i>Eventi
                    </a></li>
                    <li><a href="chiusi.php">
                        <i class="fas fa-check-circle me-2"></i>Chiusi
                    </a></li>
                    <li><a href="rifiutati.php" class="active">
                        <i class="fas fa-times-circle me-2"></i>Rifiutati
                    </a></li>
                    <li><a href="storico_eventi.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'storico_eventi.php' ? 'active' : ''; ?>">
                    <i class="fas fa-history me-2"></i>Storico
                    </a></li>
                <?php if ($_SESSION['user_role'] === 'Admin'): ?>
                    <li><a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users me-2"></i>Utenti
                    </a></li>
                <?php endif; ?>
                </ul>
                <div class="user-info">
                    <span><?php echo $_SESSION['username']; ?></span>
                    <span class="user-role"><?php echo $_SESSION['user_role']; ?></span>
                    <a href="logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <!-- Header della pagina -->
        <div class="page-header">
            <h1><i class="fas fa-times-circle me-2"></i>Eventi Rifiutati</h1>
            <p class="text-muted">Eventi non approvati</p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list-alt me-2"></i>Eventi Rifiutati
                    <span class="badge bg-danger ms-2"><?php echo count($rejected_events); ?></span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 compact-table">
                        <thead class="table-light">
                            <tr>
                                <th width="120">Rifiuto il</th>
                                <th>Evento</th>
                                <th width="120">Località</th>
                                <th width="100">Data</th>
                                <th width="200">Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rejected_events as $event): ?>
                                <tr>
                                    <td>
                                        <span class="event-date"><?php echo formatDate($event['data_rifiuto']); ?></span>
                                    </td>
                                    <td>
                                        <div class="event-info">
                                            <strong class="event-title"><?php echo $event['evento']; ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="localita"><?php echo $event['localita']; ?></span>
                                    </td>
                                    <td>
                                        <span class="event-date"><?php echo formatDate($event['data_evento']); ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($event['note'])): ?>
                                            <span class="event-note"><?php echo $event['note']; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Nessuna nota</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($rejected_events)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="fas fa-times-circle fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">Nessun evento rifiutato trovato</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>
