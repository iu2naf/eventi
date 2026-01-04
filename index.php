<?php
require_once 'config.php';
require_once 'functions.php';

// Verifica se l'utente è loggato
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Verifica i permessi
if (!has_access('dashboard')) {
    die("Accesso negato. Non hai i permessi per visualizzare questa pagina.");
}

// OTTIENI I DATI PER LA DASHBOARD
$active_events = get_active_events();
$events_without_attachment = get_events_without_attachment();
$closed_events_count = get_closed_events_count();
$rejected_events_count = get_rejected_events_count();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
<nav>
    <div class="logo">
        <img src="paisola.png" alt="Pubblica Assistenza LaMia Soccorso OdV" class="logo-img">
    </div>
    <ul>
        <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a></li>
        <li><a href="eventi.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'eventi.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-plus me-2"></i>Eventi
        </a></li>
        <li><a href="chiusi.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'chiusi.php' ? 'active' : ''; ?>">
            <i class="fas fa-check-circle me-2"></i>Chiusi
        </a></li>
        <li><a href="rifiutati.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'rifiutati.php' ? 'active' : ''; ?>">
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
            <h1><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
            <p class="text-muted">Benvenuto, <strong><?php echo $_SESSION['username']; ?></strong>!</p>
        </div>
        
        <!-- Statistiche veloci -->
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <i class="fas fa-calendar-plus fa-2x text-primary mb-3"></i>
                <h3>Eventi Attivi</h3>
                <div class="count"><?php echo count($active_events); ?></div>
                <p>Eventi in gestione</p>
            </div>
            
            <div class="dashboard-card">
                <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                <h3>Da Allegare</h3>
                <div class="count"><?php echo count($events_without_attachment); ?></div>
                <p>Eventi in attesa</p>
            </div>
            
            <div class="dashboard-card">
                <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                <h3>Eventi Chiusi</h3>
                <div class="count"><?php echo $closed_events_count; ?></div>
                <p>Completati con successo</p>
            </div>
            
            <div class="dashboard-card">
                <i class="fas fa-times-circle fa-2x text-danger mb-3"></i>
                <h3>Eventi Rifiutati</h3>
                <div class="count"><?php echo $rejected_events_count; ?></div>
                <p>Non approvati</p>
            </div>
        </div>
        
<!-- Azioni rapide -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Azioni Rapide</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 col-6 mb-3">
                <a href="eventi.php" class="btn btn-primary w-100">
                    <i class="fas fa-plus-circle me-2"></i>Nuovo Evento
                </a>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <a href="eventi.php#situazione-eventi" class="btn btn-outline-primary w-100">
                    <i class="fas fa-list me-2"></i>Gestisci Eventi
                </a>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <a href="chiusi.php" class="btn btn-outline-primary w-100">
                    <i class="fas fa-check me-2"></i>Eventi Chiusi
                </a>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <a href="rifiutati.php" class="btn btn-outline-primary w-100">
                    <i class="fas fa-times me-2"></i>Eventi Rifiutati
                </a>
            </div>
        </div>
    </div>
</div>

        <!-- Eventi recenti -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clock me-2"></i>Eventi Recenti
                            <span class="badge bg-primary ms-2"><?php echo min(5, count($active_events)); ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 compact-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Evento</th>
                                        <th width="100">Data</th>
                                        <th width="80">Stato</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $recent_events = array_slice($active_events, 0, 5);
                                    foreach ($recent_events as $event): 
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="event-info">
                                                    <strong class="event-title"><?php echo $event['evento']; ?></strong>
                                                    <div class="event-note"><?php echo $event['localita']; ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="event-date"><?php echo formatDate($event['data_evento']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge badge-sm bg-success">Attivo</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($recent_events)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-3">
                                                <div class="empty-state">
                                                    <i class="fas fa-calendar-times text-muted mb-2"></i>
                                                    <p class="text-muted mb-0">Nessun evento attivo</p>
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
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Da Completare
                            <span class="badge bg-warning ms-2"><?php echo count($events_without_attachment); ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 compact-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Codice</th>
                                        <th>Evento</th>
                                        <th width="100">Stato</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($events_without_attachment, 0, 5) as $event): ?>
                                        <?php $event_details = get_event_by_id($event['id']); ?>
                                        <tr>
                                            <td>
                                                <span class="event-code"><?php echo $event['codice_evento']; ?></span>
                                            </td>
                                            <td>
                                                <div class="event-info">
                                                    <strong class="event-title"><?php echo $event_details ? $event_details['evento'] : 'N/A'; ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-sm bg-warning">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>Da Allegare
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($events_without_attachment)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-3">
                                                <div class="empty-state">
                                                    <i class="fas fa-check-circle text-success mb-2"></i>
                                                    <p class="text-muted mb-0">Tutti gli eventi sono completi</p>
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
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>
