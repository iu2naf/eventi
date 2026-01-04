<?php
// Funzione per verificare le credenziali di login
function verify_login($username, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM login WHERE username = :username LIMIT 1");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        // Controlla se l'utente esiste e se la password è corretta
        if ($user && password_verify($password, $user['password'])) {
            // Restituisce l'array dell'utente invece di true
            return $user;
        }
        
        return false;
    } catch(PDOException $e) {
        // Logga l'errore invece di mostrarlo
        error_log("Errore login: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica se l'utente è loggato
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verifica se l'utente ha accesso alla pagina specificata
 */
function has_access($page) {
    if (!is_logged_in()) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'] ?? '';
    
    // Definisce i permessi per ogni ruolo
    $permissions = [
    'Admin' => ['index', 'dashboard', 'eventi', 'chiusi', 'rifiutati', 'users', 'storico_eventi'],
    'Centralinista' => ['index', 'dashboard', 'eventi', 'chiusi', 'rifiutati', 'storico_eventi'],
    'LeadCent' => ['index', 'dashboard', 'eventi', 'chiusi', 'rifiutati', 'storico_eventi']
];
    
    // Se il ruolo non è definito, nega l'accesso
    if (!isset($permissions[$user_role])) {
        return false;
    }
    
    // Log per vedere cosa sta succedendo
    error_log("User role: $user_role, Page: $page, Permissions: " . implode(',', $permissions[$user_role]));
    
    // Verifica se la pagina è nei permessi del ruolo
    return in_array($page, $permissions[$user_role]);
}

/**
 * Reindirizza alla dashboard se l'utente non ha i permessi necessari
 */
function require_permission($page) {
    if (!has_access($page)) {
        // Se non è loggato, va al login, altrimenti alla dashboard
        if (!is_logged_in()) {
            header('Location: login.php');
        } else {
            header('Location: index.php');
        }
        exit;
    }
}

// Genera il codice evento
function generate_event_code($tipo_evento) {
    global $pdo;
    
    // Determina il prefisso in base al tipo evento
    $prefix = '';
    switch($tipo_evento) {
        case 'Sportivo':
            $prefix = 'SPO';
            break;
        case 'Manifestazione':
            $prefix = 'MAN';
            break;
        case 'Festa':
            $prefix = 'FES';
            break;
        default:
            $prefix = 'XXX';
    }
    
    // Ottieni il mese e l'anno correnti
    $month = date('m');
    $year = date('y');
    
    // Conta quanti eventi dello stesso tipo esistono già per questo mese/anno
    try {
        $current_year = date('Y');
        $current_month = date('m');
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attivi 
                              WHERE tipo_evento = :tipo_evento 
                              AND MONTH(data_inserimento) = :month 
                              AND YEAR(data_inserimento) = :year");
        
        // Usa variabili separate invece di passare direttamente i valori
        $stmt->bindParam(':tipo_evento', $tipo_evento, PDO::PARAM_STR);
        $stmt->bindParam(':month', $current_month, PDO::PARAM_STR);
        $stmt->bindParam(':year', $current_year, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch();
        $count = $result['count'] + 1;
        
        // Formatta il numero progressivo con 2 cifre
        $progressive = str_pad($count, 2, '0', STR_PAD_LEFT);
        
        return $prefix . '-' . $month . $year . '/' . $progressive;
    } catch(PDOException $e) {
        error_log("Errore generazione codice evento: " . $e->getMessage());
        // Fallback in caso di errore
        return $prefix . '-' . $month . $year . '/01';
    }
}

// Funzione per inserire un nuovo evento
function insert_event($data) {
    global $pdo;
    
    try {
        // Genera il codice evento
        $data['codice_evento'] = generate_event_code($data['tipo_evento']);
        
        // DEBUG: Log dei dati
        error_log("Tentativo inserimento evento: " . print_r($data, true));
        
        $stmt = $pdo->prepare("INSERT INTO attivi 
            (codice_evento, tipo_evento, evento, localita, data_inserimento, data_evento, 
            ora_dalle, ora_alle, calendario, preventivo, accettazione, games_inserito, 
            games_completo, allegato5, allegato5_pdf, note, stato) 
            VALUES 
            (:codice_evento, :tipo_evento, :evento, :localita, NOW(), :data_evento, 
            :ora_dalle, :ora_alle, :calendario, :preventivo, :accettazione, :games_inserito, 
            :games_completo, :allegato5, :allegato5_pdf, :note, :stato)");
        
        // Converti la data nel formato corretto per MySQL (YYYY-MM-DD)
        $data_evento_mysql = date('Y-m-d', strtotime(str_replace('/', '-', $data['data_evento'])));
        
        // Bind dei parametri
        $stmt->bindParam(':codice_evento', $data['codice_evento']);
        $stmt->bindParam(':tipo_evento', $data['tipo_evento']);
        $stmt->bindParam(':evento', $data['evento']);
        $stmt->bindParam(':localita', $data['localita']);
        $stmt->bindParam(':data_evento', $data_evento_mysql);
        $stmt->bindParam(':ora_dalle', $data['ora_dalle']);
        $stmt->bindParam(':ora_alle', $data['ora_alle']);
        $stmt->bindParam(':calendario', $data['calendario']);
        $stmt->bindParam(':preventivo', $data['preventivo']);
        $stmt->bindParam(':accettazione', $data['accettazione']);
        $stmt->bindParam(':games_inserito', $data['games_inserito']);
        $stmt->bindParam(':games_completo', $data['games_completo']);
        $stmt->bindParam(':allegato5', $data['allegato5']);
        $stmt->bindParam(':allegato5_pdf', $data['allegato5_pdf']);
        $stmt->bindParam(':note', $data['note']);
        $stmt->bindParam(':stato', $data['stato']);
        
        $result = $stmt->execute();
        
        if ($result) {
            error_log("Evento inserito con successo: " . $data['codice_evento']);
            return $data['codice_evento'];
        } else {
            error_log("Errore nell'esecuzione della query di inserimento");
            return false;
        }
    } catch(PDOException $e) {
        error_log("Errore PDO nell'inserimento evento: " . $e->getMessage());
        return false;
    }
}

// Funzione per ottenere tutti gli eventi attivi
function get_active_events() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM attivi ORDER BY data_evento ASC");
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Funzione per ottenere il conteggio degli eventi chiusi
function get_closed_events_count() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM chiusi");
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['count'];
    } catch(PDOException $e) {
        error_log("Errore nel conteggio eventi chiusi: " . $e->getMessage());
        return 0;
    }
}

// Funzione per ottenere il conteggio degli eventi rifiutati
function get_rejected_events_count() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM rifiutati");
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['count'];
    } catch(PDOException $e) {
        error_log("Errore nel conteggio eventi rifiutati: " . $e->getMessage());
        return 0;
    }
}

// Funzione per ottenere tutti gli eventi chiusi
function get_closed_events() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM chiusi ORDER BY data_chiusura DESC");
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Funzione per ottenere tutti gli eventi rifiutati
function get_rejected_events() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM rifiutati ORDER BY data_rifiuto DESC");
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Funzione semplificata per chiudere un evento
function close_event($id) {
    global $pdo;
    
    try {
        // Ottieni i dati dell'evento
        $stmt = $pdo->prepare("SELECT * FROM attivi WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $event = $stmt->fetch();
        
        if (!$event) {
            error_log("Evento non trovato per chiusura: ID $id");
            return false;
        }
        
        // Inserisci nella tabella chiusi
        $stmt = $pdo->prepare("INSERT INTO chiusi (data_chiusura, data_evento, evento, localita) VALUES (NOW(), :data_evento, :evento, :localita)");
        $stmt->bindParam(':data_evento', $event['data_evento']);
        $stmt->bindParam(':evento', $event['evento']);
        $stmt->bindParam(':localita', $event['localita']);
        
        if (!$stmt->execute()) {
            error_log("Errore inserimento in chiusi per evento: " . $event['id']);
            return false;
        }
        
        // Cancella dalla tabella attivi
        $stmt = $pdo->prepare("DELETE FROM attivi WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            error_log("Evento chiuso con successo: ID $id");
            return true;
        } else {
            error_log("Errore cancellazione da attivi per evento: ID $id");
            return false;
        }
        
    } catch(PDOException $e) {
        error_log("Errore PDO nella chiusura evento ID $id: " . $e->getMessage());
        return false;
    }
}
        
// Funzione semplificata per cancellare un evento
function delete_event($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM attivi WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $rowCount = $stmt->rowCount();
            if ($rowCount > 0) {
                error_log("Evento cancellato con successo: ID $id");
                return true;
            } else {
                error_log("Nessun evento trovato per cancellazione: ID $id");
                return false;
            }
        } else {
            error_log("Errore esecuzione query cancellazione: ID $id");
            return false;
        }
    } catch(PDOException $e) {
        error_log("Errore PDO nella cancellazione evento ID $id: " . $e->getMessage());
        return false;
    }
}

// Funzione semplificata per rifiutare un evento
function reject_event($id, $note = '') {
    global $pdo;
    
    try {
        // Ottieni i dati dell'evento
        $stmt = $pdo->prepare("SELECT * FROM attivi WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $event = $stmt->fetch();
        
        if (!$event) {
            error_log("Evento non trovato per rifiuto: ID $id");
            return false;
        }
        
        // Inserisci nella tabella rifiutati
        $stmt = $pdo->prepare("INSERT INTO rifiutati (evento, localita, data_evento, data_rifiuto, note) VALUES (:evento, :localita, :data_evento, NOW(), :note)");
        $stmt->bindParam(':evento', $event['evento']);
        $stmt->bindParam(':localita', $event['localita']);
        $stmt->bindParam(':data_evento', $event['data_evento']);
        $stmt->bindParam(':note', $note);
        
        if (!$stmt->execute()) {
            error_log("Errore inserimento in rifiutati per evento: " . $event['id']);
            return false;
        }
        
        // Cancella dalla tabella attivi
        $stmt = $pdo->prepare("DELETE FROM attivi WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            error_log("Evento rifiutato con successo: ID $id - Note: $note");
            return true;
        } else {
            error_log("Errore cancellazione da attivi per evento rifiutato: ID $id");
            return false;
        }
        
    } catch(PDOException $e) {
        error_log("Errore PDO nel rifiuto evento ID $id: " . $e->getMessage());
        return false;
    }
}

// Funzione per ottenere gli eventi senza allegato
function get_events_without_attachment() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, codice_evento FROM attivi WHERE (allegato5_pdf IS NULL OR allegato5_pdf = '') ORDER BY codice_evento ASC");
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

// Funzione per aggiornare l'allegato di un evento
function update_event_attachment($id, $file_path) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE attivi SET allegato5_pdf = :file_path WHERE id = :id");
        $stmt->bindParam(':file_path', $file_path);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzione per gestire l'upload di un file
function upload_file($file, $target_dir = "uploads/") {
    // Crea la directory se non esiste
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
            error_log("Impossibile creare la directory: " . $target_dir);
            return false;
        }
    }
    
    // Verifica che la directory sia scrivibile
    if (!is_writable($target_dir)) {
        error_log("Directory non scrivibile: " . $target_dir);
        return false;
    }
    
    // Genera un nome file univoco
    $file_name = basename($file["name"]);
    $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $unique_name = uniqid() . '.' . $file_type;
    $target_file = $target_dir . $unique_name;
    
    // Verifica il tipo di file (solo PDF in questo caso)
    if ($file_type != "pdf") {
        error_log("Tipo file non consentito: " . $file_type);
        return false;
    }
    
    // Verifica le dimensioni del file (max 5MB)
    if ($file["size"] > 5 * 1024 * 1024) {
        error_log("File troppo grande: " . $file["size"] . " bytes");
        return false;
    }
    
    // Sposta il file nella directory di destinazione
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    } else {
        error_log("Errore nel move_uploaded_file. Error code: " . $file['error']);
        return false;
    }
}

// Funzione per ottenere un evento per ID
function get_event_by_id($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM attivi WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}

/**
 * Formatta una data in formato italiano (DD/MM/YYYY)
 * Rimuove l'ora se presente
 */
function formatDate($date_string) {
    if (empty($date_string) || $date_string == '0000-00-00' || $date_string == '0000-00-00 00:00:00') {
        return 'N/D';
    }
    
    // Se la data contiene uno spazio, prendi solo la parte della data (prima dello spazio)
    if (strpos($date_string, ' ') !== false) {
        $date_string = explode(' ', $date_string)[0];
    }
    
    // Prova diversi formati di data
    $formats = ['Y-m-d', 'Y-m-d H:i:s', 'd/m/Y'];
    
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $date_string);
        if ($date) {
            return $date->format('d/m/Y');
        }
    }
    
    // Se il formato non è riconosciuto, restituisci la stringa originale
    return $date_string;
}

/**
 * Genera una password sicura per l'uso nel sistema
 * Da utilizzare per la creazione di nuovi account utente
 */
function generateUserPassword($length = 12) {
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $special = '!@#$%&*+-=?';
    
    $allChars = $uppercase . $lowercase . $numbers . $special;
    $password = '';
    
    // Garantisce almeno un carattere di ogni tipo
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];
    
    // Riempie il resto con caratteri casuali
    for ($i = 4; $i < $length; $i++) {
        $password .= $allChars[random_int(0, strlen($allChars) - 1)];
    }
    
    return str_shuffle($password);
}

/**
 * Ottiene tutti gli utenti
 */
function get_all_users() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, ruolo FROM login ORDER BY ruolo, username");
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Errore nel recupero utenti: " . $e->getMessage());
        return [];
    }
}

/**
 * Crea un nuovo utente
 */
function create_user($username, $password, $ruolo, $email = '') {
    global $pdo;
    
    try {
        // Verifica se l'username esiste già
        $stmt = $pdo->prepare("SELECT id FROM login WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            error_log("Username già esistente: " . $username);
            return false;
        }
        
        // Hash della password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO login (username, password, ruolo, email) VALUES (:username, :password, :ruolo, :email)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':ruolo', $ruolo);
        $stmt->bindParam(':email', $email);
        
        if ($stmt->execute()) {
            error_log("Utente creato: " . $username . " - Ruolo: " . $ruolo);
            return true;
        }
        
        return false;
    } catch(PDOException $e) {
        error_log("Errore nella creazione utente: " . $e->getMessage());
        return false;
    }
}

/**
 * Cancella un utente
 */
function delete_user($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM login WHERE id = :id");
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $rowCount = $stmt->rowCount();
            if ($rowCount > 0) {
                error_log("Utente cancellato: ID $user_id");
                return true;
            }
        }
        
        return false;
    } catch(PDOException $e) {
        error_log("Errore nella cancellazione utente ID $user_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Cambia la password di un utente
 */
function change_user_password($user_id, $new_password) {
    global $pdo;
    
    try {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE login SET password = :password WHERE id = :id");
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            error_log("Password cambiata per utente ID: $user_id");
            return true;
        }
        
        return false;
    } catch(PDOException $e) {
        error_log("Errore nel cambio password utente ID $user_id: " . $e->getMessage());
        return false;
    }
}
?>
