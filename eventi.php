<?php
require_once 'config.php';
require_once 'functions.php';

// Verifica se l'utente è loggato
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Verifica se l'utente ha accesso a questa pagina
if (!has_access('eventi')) {
    header('Location: login.php');
    exit;
}

// DEBUG: Verifica connessione database
try {
    $pdo->query("SELECT 1");
} catch(PDOException $e) {
    $error_message = "Errore connessione database: " . $e->getMessage();
}

// DEBUG: Verifica se le azioni vengono ricevute
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST ricevuto: " . print_r($_POST, true));
}

// PROCESSAMENTO DELLE AZIONI PRINCIPALI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    
    if ($event_id > 0) {
        switch($_POST['action']) {
            case 'close_event':
                if (close_event($event_id)) {
                    $success_message = "Evento chiuso con successo";
                } else {
                    $error_message = "Errore durante la chiusura dell'evento";
                }
                break;
                
            case 'reject_event':
                $note = $_POST['note'] ?? '';
                if (reject_event($event_id, $note)) {
                    $success_message = "Evento rifiutato con successo";
                } else {
                    $error_message = "Errore durante il rifiuto dell'evento";
                }
                break;
                
            case 'delete_event':
                if (delete_event($event_id)) {
                    $success_message = "Evento cancellato con successo";
                } else {
                    $error_message = "Errore durante la cancellazione dell'evento";
                }
                break;
                
            case 'upload_attachment':
                if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                    $file_path = upload_file($_FILES['attachment']);
                    if ($file_path && update_event_attachment($event_id, $file_path)) {
                        $success_message = "Allegato caricato con successo";
                    } else {
                        $error_message = "Errore durante il caricamento dell'allegato";
                    }
                } else {
                    $error_message = "Seleziona un file da caricare";
                }
                break;
        }
    } else {
        $error_message = "ID evento non valido";
    }
}

// Processa il form di inserimento evento SEPARATAMENTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'insert_event') {
    // Validazione dei campi obbligatori
    $required_fields = ['tipo_evento', 'evento', 'localita', 'data_evento', 'ora_dalle', 'ora_alle'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $error_message = "Il campo " . ucfirst(str_replace('_', ' ', $field)) . " è obbligatorio.";
            break;
        }
    }
    
    if (!isset($error_message)) {
        $event_data = [
            'tipo_evento' => $_POST['tipo_evento'] ?? '',
            'evento' => $_POST['evento'] ?? '',
            'localita' => $_POST['localita'] ?? '',
            'data_evento' => $_POST['data_evento'] ?? '',
            'ora_dalle' => $_POST['ora_dalle'] ?? '',
            'ora_alle' => $_POST['ora_alle'] ?? '',
            'calendario' => $_POST['calendario'] ?? 'NO',
            'preventivo' => $_POST['preventivo'] ?? 'NO',
            'accettazione' => $_POST['accettazione'] ?? 'NO',
            'games_inserito' => $_POST['games_inserito'] ?? 'NO', // Specifico per la Regione Lombardia
            'games_completo' => $_POST['games_completo'] ?? 'NO', // Specifico per la Regione Lombardia
            'allegato5' => $_POST['allegato5'] ?? 'NO', // Specifico per l'associazione di appartenenza
            'note' => $_POST['note'] ?? '',
            'stato' => $_POST['stato'] ?? 'attesa'
        ];
        
        // DEBUG: Verifica i dati ricevuti
        error_log("Dati evento ricevuti: " . print_r($event_data, true));

        // Gestione dell'upload del file PDF
        if (isset($_FILES['allegato5_pdf']) && $_FILES['allegato5_pdf']['error'] === UPLOAD_ERR_OK) {
            $file_path = upload_file($_FILES['allegato5_pdf']);
            if ($file_path) {
                $event_data['allegato5_pdf'] = $file_path;
            } else {
                $error_message = "Errore durante l'upload del file PDF.";
            }
        } else {
            $event_data['allegato5_pdf'] = null;
        }
        
        if (!isset($error_message)) {
            $codice_evento = insert_event($event_data);
            
            if ($codice_evento) {
                $success_message = "Evento inserito con successo. Codice evento: $codice_evento";
                // Pulisce i campi del form dopo l'inserimento
                echo '<script>setTimeout(function(){ document.querySelector("form.needs-validation").reset(); updateCodiceEvento(); updateCharCounter(); }, 1000);</script>';
            } else {
                $error_message = "Errore durante l'inserimento dell'evento. Controlla i log per maggiori dettagli.";
            }
        }
    }
}

// Ottieni tutti gli eventi attivi
$events = get_active_events();

// Ottieni gli eventi senza allegato
$events_without_attachment = get_events_without_attachment();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Eventi - <?php echo APP_NAME; ?></title>
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
                    <li><a href="index.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a></li>
                    <li><a href="eventi.php" class="active">
                        <i class="fas fa-calendar-plus me-2"></i>Eventi
                    </a></li>
                    <li><a href="chiusi.php">
                        <i class="fas fa-check-circle me-2"></i>Chiusi
                    </a></li>
                    <li><a href="rifiutati.php">
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
            <h1><i class="fas fa-calendar-alt me-2"></i>Gestione Eventi</h1>
            <p class="text-muted">Gestisci e monitora tutti gli eventi del sistema</p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Tabs migliorate -->
        <div class="tabs">
            <div class="tabs">
    <div class="tab-nav">
        <button class="active" data-tab="inserimento-eventi">
            <i class="fas fa-plus-circle me-2"></i>Nuovo Evento
        </button>
        <button data-tab="situazione-eventi">
            <i class="fas fa-list-alt me-2"></i>Eventi Attivi
            <span class="badge bg-primary ms-1"><?php echo count($events); ?></span>
        </button>
        <button data-tab="allega">
            <i class="fas fa-paperclip me-2"></i>Da Allegare
            <span class="badge bg-warning ms-1"><?php echo count($events_without_attachment); ?></span>
        </button>
    </div>
            
            <!-- Tab 1: Inserimento Eventi -->
            <div id="inserimento-eventi" class="tab-content active">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-plus-circle me-2"></i>Inserimento Nuovo Evento
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="eventi.php" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="insert_event">
                            
                            <!-- Sezione: Informazioni Generali -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Informazioni Generali
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="tipo_evento" class="form-label">Tipo Evento *</label>
                                            <select id="tipo_evento" name="tipo_evento" class="form-control" required>
                                                <option value="">Seleziona...</option>
                                                <option value="Sportivo">Sportivo</option>
                                                <option value="Manifestazione">Manifestazione</option>
                                                <option value="Festa">Festa</option>
                                            </select>
                                            <div class="invalid-feedback">Seleziona un tipo di evento.</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="codice_evento" class="form-label">Codice Evento</label>
                                            <input type="text" id="codice_evento" name="codice_evento" class="form-control" readonly>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="evento" class="form-label">Evento *</label>
                                            <input type="text" id="evento" name="evento" class="form-control" required>
                                            <div class="invalid-feedback">Inserisci il nome dell'evento.</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="localita" class="form-label">Località *</label>
                                            <input type="text" id="localita" name="localita" class="form-control" required>
                                            <div class="invalid-feedback">Inserisci la località.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sezione: Date e Orari -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-calendar-alt me-2"></i>Date e Orari
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="data_inserimento" class="form-label">Data Inserimento</label>
                                            <input type="text" id="data_inserimento" name="data_inserimento" class="form-control" value="<?php echo date('d/m/Y'); ?>" readonly>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="data_evento" class="form-label">Data Evento *</label>
                                            <input type="date" id="data_evento" name="data_evento" class="form-control" required>
                                            <div class="invalid-feedback">Inserisci la data dell'evento.</div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label for="ora_dalle" class="form-label">Dalle Ore *</label>
                                            <input type="time" id="ora_dalle" name="ora_dalle" class="form-control" required>
                                            <div class="invalid-feedback">Inserisci l'ora di inizio.</div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="ora_alle" class="form-label">Alle Ore *</label>
                                            <input type="time" id="ora_alle" name="ora_alle" class="form-control" required>
                                            <div class="invalid-feedback">Inserisci l'ora di fine.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sezione: Stato dell'Evento -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-check-circle me-2"></i>Stato dell'Evento
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-2 mb-3">
                                            <label for="calendario" class="form-label">Calendario *</label>
                                            <select id="calendario" name="calendario" class="form-control" required>
                                                <option value="">Seleziona...</option>
                                                <option value="SI">SI</option>
                                                <option value="NO">NO</option>
                                                <option value="CONC">CONC</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label for="preventivo" class="form-label">Preventivo *</label>
                                            <select id="preventivo" name="preventivo" class="form-control" required>
                                                <option value="">Seleziona...</option>
                                                <option value="SI">SI</option>
                                                <option value="NO">NO</option>
                                                <option value="CONC">CONC</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label for="accettazione" class="form-label">Accettazione *</label>
                                            <select id="accettazione" name="accettazione" class="form-control" required>
                                                <option value="">Seleziona...</option>
                                                <option value="SI">SI</option>
                                                <option value="NO">NO</option>
                                                <option value="CONC">CONC</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label for="games_inserito" class="form-label">Games Inserito *</label>
                                            <select id="games_inserito" name="games_inserito" class="form-control" required>
                                                <option value="">Seleziona...</option>
                                                <option value="SI">SI</option>
                                                <option value="NO">NO</option>
                                                <option value="CONC">CONC</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label for="games_completo" class="form-label">Games Completo *</label>
                                            <select id="games_completo" name="games_completo" class="form-control" required>
                                                <option value="">Seleziona...</option>
                                                <option value="SI">SI</option>
                                                <option value="NO">NO</option>
                                                <option value="CONC">CONC</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label for="allegato5" class="form-label">Allegato 5 *</label>
                                            <select id="allegato5" name="allegato5" class="form-control" required>
                                                <option value="">Seleziona...</option>
                                                <option value="SI">SI</option>
                                                <option value="NO">NO</option>
                                                <option value="CONC">CONC</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sezione: Allegati e Note -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-paperclip me-2"></i>Allegati e Note
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="allegato5_pdf" class="form-label">Allegato 5 PDF</label>
                                            <input type="file" id="allegato5_pdf" name="allegato5_pdf" class="form-control" accept=".pdf">
                                            <div class="form-text">Solo file PDF consentiti (max 5MB)</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="stato" class="form-label">Stato *</label>
                                            <select id="stato" name="stato" class="form-control" required>
                                                <option value="">Seleziona...</option>
                                                <option value="attesa">Attesa</option>
                                                <option value="chiuso">Chiuso</option>
                                                <option value="rifiutato">Rifiutato</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <label for="note" class="form-label">Note</label>
                                            <textarea id="note" name="note" class="form-control" rows="4" maxlength="255" placeholder="Inserisci note aggiuntive..."></textarea>
                                            <div class="character-count mt-1">
                                                <span id="char-counter">0</span>/255 caratteri
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pulsanti di Azione -->
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Salva Evento
                                    </button>
                                    <button type="reset" class="btn btn-outline-secondary ms-2">
                                        <i class="fas fa-undo me-2"></i>Annulla
                                    </button>
                                    <button type="button" id="export-excel" class="btn btn-success ms-2">
                                        <i class="fas fa-file-excel me-2"></i>Esporta in Excel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Tab 2: Situazione Eventi -->
            <div id="situazione-eventi" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list-alt me-2"></i>Eventi Attivi
                            <span class="badge bg-primary ms-2"><?php echo count($events); ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 compact-table">
                                <thead class="table-light">
                                    <tr>
                                        <th width="120">Codice</th>
                                        <th>Evento</th>
                                        <th width="120">Località</th>
                                        <th width="100">Data</th>
                                        <th width="80" class="text-center">Stato</th>
                                        <th width="180" class="text-center">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($events as $event): ?>
                                        <?php 
                                        $fields = [
                                            $event['calendario'],
                                            $event['preventivo'], 
                                            $event['accettazione'],
                                            $event['games_inserito'],
                                            $event['games_completo']
                                        ];
                                        
                                        $no_count = array_count_values($fields)['NO'] ?? 0;
                                        $si_count = array_count_values($fields)['SI'] ?? 0;
                                        $conc_count = array_count_values($fields)['CONC'] ?? 0;
                                        
                                        $status_class = '';
                                        $status_icon = '';
                                        if ($no_count > 0) {
                                            $status_class = 'status-danger';
                                            $status_icon = 'fas fa-times-circle';
                                        } elseif ($si_count == 5) {
                                            $status_class = 'status-success';
                                            $status_icon = 'fas fa-check-circle';
                                        } elseif ($conc_count > 0) {
                                            $status_class = 'status-warning';
                                            $status_icon = 'fas fa-exclamation-circle';
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="event-code <?php echo $status_class; ?>">
                                                    <?php echo $event['codice_evento']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="event-info">
                                                    <strong class="event-title"><?php echo $event['evento']; ?></strong>
                                                    <?php if (!empty($event['note'])): ?>
                                                        <div class="event-note"><?php echo substr($event['note'], 0, 50); ?><?php echo strlen($event['note']) > 50 ? '...' : ''; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="localita"><?php echo $event['localita']; ?></span>
                                            </td>
                                            <td>
                                                <span class="event-date"><?php echo formatDate($event['data_evento']); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <i class="<?php echo $status_icon; ?> <?php echo $status_class; ?> fa-lg" title="<?php echo $si_count; ?> SI, <?php echo $no_count; ?> NO, <?php echo $conc_count; ?> CONC"></i>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm compact-actions">
                                                    <button type="button" class="btn btn-outline-success" 
                                                        onclick="confirmAction('close', <?php echo $event['id']; ?>, '<?php echo addslashes($event['evento']); ?>')"
                                                        title="Chiudi Evento">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger"
                                                        onclick="confirmAction('reject', <?php echo $event['id']; ?>, '<?php echo addslashes($event['evento']); ?>')"
                                                        title="Rifiuta Evento">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        onclick="confirmAction('delete', <?php echo $event['id']; ?>, '<?php echo addslashes($event['evento']); ?>')"
                                                        title="Cancella Evento">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary"
                                                        onclick="showEventDetails(<?php echo $event['id']; ?>)"
                                                        title="Dettagli Evento">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($events)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="empty-state">
                                                    <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                                                    <p class="text-muted mb-0">Nessun evento attivo trovato</p>
                                                    <a href="#inserimento-eventi" class="btn btn-primary mt-2" onclick="switchTab('inserimento-eventi')">
                                                        <i class="fas fa-plus me-2"></i>Crea il primo evento
                                                    </a>
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
            
            <!-- Tab 3: Da Allegare -->
            <div id="allega" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-paperclip me-2"></i>Eventi in Attesa di Allegato
                            <span class="badge bg-warning ms-2"><?php echo count($events_without_attachment); ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 compact-table">
                                <thead class="table-light">
                                    <tr>
                                        <th width="120">Codice</th>
                                        <th>Evento</th>
                                        <th width="120">Località</th>
                                        <th width="100">Data</th>
                                        <th width="100" class="text-center">Stato</th>
                                        <th width="100" class="text-center">Azione</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($events_without_attachment as $event): ?>
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
                                                <span class="localita"><?php echo $event_details ? $event_details['localita'] : 'N/A'; ?></span>
                                            </td>
                                            <td>
                                                  <span class="event-date"><?php echo $event_details ? formatDate($event_details['data_evento']) : 'N/A'; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-warning badge-sm">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>Da Allegare
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-primary btn-sm" onclick="uploadAttachment(<?php echo $event['id']; ?>)">
                                                    <i class="fas fa-upload me-1"></i>Allega
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($events_without_attachment)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="empty-state">
                                                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                                    <p class="text-muted mb-0">Tutti gli eventi hanno un allegato</p>
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
