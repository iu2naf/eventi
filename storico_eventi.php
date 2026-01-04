<?php
require_once 'config.php';
require_once 'functions.php';

// Verifica se l'utente è loggato
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Verifica se l'utente ha accesso a questa pagina
if (!has_access('storico_eventi')) {
    header('Location: login.php');
    exit;
}

// Parametri di paginazione
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $records_per_page;

// Parametri di ricerca
$search_tipo_evento = $_GET['tipo_evento'] ?? '';
$search_evento = $_GET['evento'] ?? '';

// Costruzione della query con filtri
$where_conditions = [];
$params = [];

if (!empty($search_tipo_evento)) {
    $where_conditions[] = "tipo_evento LIKE :tipo_evento";
    $params[':tipo_evento'] = "%$search_tipo_evento%";
}

if (!empty($search_evento)) {
    $where_conditions[] = "evento LIKE :evento";
    $params[':evento'] = "%$search_evento%";
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Conteggio totale record
try {
    $count_sql = "SELECT COUNT(*) as total FROM storico_eventi $where_sql";
    $stmt = $pdo->prepare($count_sql);
    
    // Bind dei parametri per il conteggio
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $records_per_page);
    
    // Correzione per pagina corrente se supera il totale
    if ($current_page > $total_pages && $total_pages > 0) {
        $current_page = $total_pages;
        $offset = ($current_page - 1) * $records_per_page;
    }
    
} catch(PDOException $e) {
    $total_records = 0;
    $total_pages = 1;
    $error_message = "Errore nel conteggio record: " . $e->getMessage();
}

// Recupero dati con paginazione
try {
    $sql = "SELECT * FROM storico_eventi $where_sql ORDER BY data_evento ASC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    
    // Bind dei parametri di ricerca
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Bind dei parametri di paginazione
    $stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $events = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $events = [];
    $error_message = "Errore nel recupero dati: " . $e->getMessage();
}

// Funzione per costruire l'URL di paginazione mantenendo i filtri
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return 'storico_eventi.php?' . http_build_query($params);
}

// Funzione helper per le classi dei badge dei tipi evento
function getTipoEventoBadgeClass($tipo_evento) {
    switch($tipo_evento) {
        case 'Sportivo': return 'bg-success';
        case 'Manifestazione': return 'bg-primary';
        case 'Festa': return 'bg-warning';
        default: return 'bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storico Eventi - <?php echo APP_NAME; ?></title>
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
                    <li><a href="rifiutati.php">
                        <i class="fas fa-times-circle me-2"></i>Rifiutati
                    </a></li>
                    <li><a href="storico_eventi.php" class="active">
                        <i class="fas fa-history me-2"></i>Storico
                    </a></li>
                    <?php if ($_SESSION['user_role'] === 'Admin'): ?>
                    <li><a href="users.php">
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
            <h1><i class="fas fa-history me-2"></i>Storico Eventi</h1>
            <p class="text-muted">Archivio completo di tutti gli eventi svolti dall'associazione</p>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Box di Ricerca -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-search me-2"></i>Ricerca Eventi
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="storico_eventi.php" class="row g-3">
                    <div class="col-md-6">
                        <label for="tipo_evento" class="form-label">Tipo Evento</label>
                        <select id="tipo_evento" name="tipo_evento" class="form-control">
                            <option value="">Tutti i tipi</option>
                            <option value="Sportivo" <?php echo $search_tipo_evento === 'Sportivo' ? 'selected' : ''; ?>>Sportivo</option>
                            <option value="Manifestazione" <?php echo $search_tipo_evento === 'Manifestazione' ? 'selected' : ''; ?>>Manifestazione</option>
                            <option value="Festa" <?php echo $search_tipo_evento === 'Festa' ? 'selected' : ''; ?>>Festa</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="evento" class="form-label">Nome Evento</label>
                        <input type="text" id="evento" name="evento" class="form-control" 
                               placeholder="Cerca per nome evento..." value="<?php echo htmlspecialchars($search_evento); ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Cerca
                        </button>
                        <a href="storico_eventi.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-undo me-2"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Informazioni risultati -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <strong>Totale eventi: <?php echo $total_records; ?></strong>
                <?php if (!empty($search_tipo_evento) || !empty($search_evento)): ?>
                    <span class="text-muted ms-2">(filtrati)</span>
                <?php endif; ?>
            </div>
            <?php if ($total_pages > 1): ?>
            <div class="text-muted">
                Pagina <?php echo $current_page; ?> di <?php echo $total_pages; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Tabella Eventi -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list-alt me-2"></i>Eventi Storici
                    <span class="badge bg-primary ms-2"><?php echo count($events); ?> eventi in questa pagina</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 compact-table">
                        <thead class="table-light">
                            <tr>
                                <th width="120">Tipo Evento</th>
                                <th>Evento</th>
                                <th width="150">Località</th>
                                <th width="120">Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td>
                                        <span class="badge <?php echo getTipoEventoBadgeClass($event['tipo_evento']); ?>">
                                            <?php echo htmlspecialchars($event['tipo_evento']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="event-info">
                                            <strong class="event-title"><?php echo htmlspecialchars($event['evento']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="localita"><?php echo htmlspecialchars($event['localita']); ?></span>
                                    </td>
                                    <td>
                                        <span class="event-date"><?php echo formatDate($event['data_evento']); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($events)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">
                                                <?php echo ($total_records > 0) ? 
                                                    'Nessun evento trovato in questa pagina' : 
                                                    'Nessun evento trovato'; 
                                                ?>
                                            </p>
                                            <?php if (!empty($search_tipo_evento) || !empty($search_evento)): ?>
                                                <a href="storico_eventi.php" class="btn btn-primary mt-2">
                                                    <i class="fas fa-undo me-2"></i>Rimuovi filtri
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Paginazione -->
            <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Paginazione eventi">
                    <ul class="pagination justify-content-center mb-0">
                        <!-- Prima pagina -->
                        <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPaginationUrl(1); ?>" aria-label="Prima pagina">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        
                        <!-- Pagina precedente -->
                        <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPaginationUrl($current_page - 1); ?>" aria-label="Pagina precedente">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        
                        <!-- Pagine numeriche -->
                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo buildPaginationUrl($i); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Pagina successiva -->
                        <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPaginationUrl($current_page + 1); ?>" aria-label="Pagina successiva">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        
                        <!-- Ultima pagina -->
                        <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPaginationUrl($total_pages); ?>" aria-label="Ultima pagina">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>
