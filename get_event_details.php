<?php
require_once 'config.php';
require_once 'functions.php';

// Verifica se l'utente è loggato
if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Utente non autenticato']);
    exit;
}

// Verifica se è stata richiesta la ricerca
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);
    
    try {
        $event = get_event_by_id($event_id);
        
        if ($event) {
            header('Content-Type: application/json');
            echo json_encode($event);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Evento non trovato']);
        }
    } catch(PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Errore nel recupero dei dati: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Richiesta non valida']);
}
?>
