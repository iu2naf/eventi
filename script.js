// Funzione per gestire le tabs
function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-nav button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.getAttribute('data-tab');
            
            // Rimuovi la classe active da tutti i bottoni e contenuti
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Aggiungi la classe active al bottone cliccato e al contenuto corrispondente
            button.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
}

// Chiudi il modal cliccando fuori dal contenuto
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.remove();
    }
});

// Funzione per generare il codice evento
function generateCodiceEvento(tipoEvento) {
    const today = new Date();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const year = String(today.getFullYear()).substring(2);
    
    let prefix = '';
    switch(tipoEvento) {
        case 'Sportivo':
            prefix = 'SPO';
            break;
        case 'Manifestazione':
            prefix = 'MAN';
            break;
        case 'Festa':
            prefix = 'FES';
            break;
        default:
            prefix = 'XXX';
    }
    
    // Per ora usiamo un numero progressivo fittizio, questo dovrebbe essere recuperato dal database
    const progressive = '01';
    
    return `${prefix}-${month}${year}/${progressive}`;
}

// Funzione per aggiornare il codice evento quando cambia il tipo evento
function updateCodiceEvento() {
    const tipoEvento = document.getElementById('tipo_evento').value;
    const codiceEvento = document.getElementById('codice_evento');
    
    if (tipoEvento && codiceEvento) {
        codiceEvento.value = generateCodiceEvento(tipoEvento);
    }
}

// Funzione per contare i caratteri nel campo note
function updateCharCounter() {
    const noteField = document.getElementById('note');
    const counter = document.getElementById('char-counter');
    
    if (noteField && counter) {
        const maxLength = 255;
        const currentLength = noteField.value.length;
        
        counter.textContent = `${currentLength}/${maxLength}`;
        
        if (currentLength > maxLength) {
            noteField.value = noteField.value.substring(0, maxLength);
            counter.textContent = `${maxLength}/${maxLength}`;
        }
    }
}

// Funzione per inviare azioni
function submitAction(action, eventId, note = '') {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'eventi.php';
    form.style.display = 'none';
    
    // Aggiungi i campi necessari
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;
    form.appendChild(actionInput);
    
    const eventIdInput = document.createElement('input');
    eventIdInput.type = 'hidden';
    eventIdInput.name = 'event_id';
    eventIdInput.value = eventId;
    form.appendChild(eventIdInput);
    
    // Aggiungi le note se presenti
    if (note) {
        const noteInput = document.createElement('input');
        noteInput.type = 'hidden';
        noteInput.name = 'note';
        noteInput.value = note;
        form.appendChild(noteInput);
    }
    
    // Aggiungi il form al documento e invialo
    document.body.appendChild(form);
    form.submit();
}

// Funzione per inviare il rifiuto con note
function submitReject(eventId) {
    const modal = document.querySelector('.modal-overlay');
    if (!modal) return;
    
    const note = modal.querySelector('#reject-note').value;
    
    // Chiudi il modal
    modal.remove();
    
    // Invia l'azione
    submitAction('reject_event', eventId, note);
}

// Funzione per confermare le azioni
function confirmAction(action, eventId, eventName) {
    let message = '';
    
    switch(action) {
        case 'close':
            message = `Sei sicuro di voler chiudere l'evento "${eventName}"?`;
            if (confirm(message)) {
                submitAction('close_event', eventId);
            }
            break;
        case 'reject':
            message = `Sei sicuro di voler rifiutare l'evento "${eventName}"?`;
            showRejectModal(eventName, eventId);
            break;
        case 'delete':
            message = `⚠️ ATTENZIONE: Sei sicuro di voler CANCELLARE l'evento "${eventName}"?`;
            if (confirm(message)) {
                submitAction('delete_event', eventId);
            }
            break;
    }
}

// Funzione per mostrare il modal di rifiuto con campo note
function showRejectModal(eventName, eventId) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        padding: 20px;
    `;
    
    modal.innerHTML = `
        <div class="modal-content" style="
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        ">
            <div class="modal-header" style="
                display: flex;
                justify-content: between;
                align-items: center;
                margin-bottom: 1.5rem;
                border-bottom: 2px solid #f1f3f4;
                padding-bottom: 1rem;
            ">
                <h3 style="margin: 0; color: #e74c3c;">
                    <i class="fas fa-exclamation-triangle me-2"></i>Rifiuta Evento
                </h3>
                <button onclick="this.closest('.modal-overlay').remove()" style="
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: #6c757d;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 1rem;">Stai per rifiutare l'evento: <strong>"${eventName}"</strong></p>
                <div class="form-group">
                    <label for="reject-note" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        Note (opzionale):
                    </label>
                    <textarea 
                        id="reject-note" 
                        style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 6px; resize: vertical; min-height: 100px;"
                        placeholder="Inserisci il motivo del rifiuto..."
                        maxlength="255"
                    ></textarea>
                    <div style="text-align: right; margin-top: 0.25rem; font-size: 0.8rem; color: #6c757d;">
                        <span id="reject-note-counter">0</span>/255 caratteri
                    </div>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 1.5rem;">
                    <button onclick="this.closest('.modal-overlay').remove()" style="
                        padding: 10px 20px;
                        border: 1px solid #6c757d;
                        background: white;
                        color: #6c757d;
                        border-radius: 6px;
                        cursor: pointer;
                    ">Annulla</button>
                    <button onclick="submitReject(${eventId})" style="
                        padding: 10px 20px;
                        border: none;
                        background: #e74c3c;
                        color: white;
                        border-radius: 6px;
                        cursor: pointer;
                        font-weight: 600;
                    ">
                        <i class="fas fa-times me-2"></i>Conferma Rifiuto
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Aggiorna contatore caratteri
    const textarea = modal.querySelector('#reject-note');
    const counter = modal.querySelector('#reject-note-counter');
    
    textarea.addEventListener('input', function() {
        counter.textContent = this.value.length;
    });
}

// Funzione per mostrare i dettagli dell'evento
function showEventDetails(eventId) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        padding: 20px;
    `;
    
    modal.innerHTML = `
        <div class="modal-content" style="
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 600px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        ">
            <div class="modal-header" style="
                display: flex;
                justify-content: between;
                align-items: center;
                margin-bottom: 1.5rem;
                border-bottom: 2px solid #f1f3f4;
                padding-bottom: 1rem;
            ">
                <h3 style="margin: 0; color: #2c3e50;">
                    <i class="fas fa-info-circle me-2"></i>Dettagli Evento
                </h3>
                <button onclick="this.closest('.modal-overlay').remove()" style="
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: #6c757d;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">&times;</button>
            </div>
            <div class="modal-body">
                <div class="loading" style="text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-2">Caricamento dettagli evento...</p>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Carica i dettagli dell'evento via AJAX
    loadEventDetails(eventId, modal);
}

// Funzione per caricare i dettagli dell'evento
function loadEventDetails(eventId, modal) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'get_event_details.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        const modalBody = modal.querySelector('.modal-body');
        
        if (xhr.status === 200) {
            try {
                const event = JSON.parse(xhr.responseText);
                modalBody.innerHTML = formatEventDetails(event);
            } catch (e) {
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Errore nel caricamento dei dettagli: ${e.message}
                    </div>
                `;
            }
        } else {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Errore nel caricamento dei dettagli (${xhr.status})
                </div>
            `;
        }
    };
    
    xhr.onerror = function() {
        const modalBody = modal.querySelector('.modal-body');
        modalBody.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Errore di connessione
            </div>
        `;
    };
    
    xhr.send('event_id=' + encodeURIComponent(eventId));
}

// Funzione per formattare i dettagli dell'evento
function formatEventDetails(event) {
    if (!event || event.error) {
        return `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${event.error || 'Evento non trovato'}
            </div>
        `;
    }
    
    return `
        <div class="event-details">
            <!-- Informazioni Generali -->
            <div class="detail-section mb-4">
                <h5 class="section-title" style="color: #2c3e50; border-bottom: 1px solid #e9ecef; padding-bottom: 0.5rem;">
                    <i class="fas fa-info-circle me-2"></i>Informazioni Generali
                </h5>
                <div class="row" style="display: flex; flex-wrap: wrap; margin: 0 -8px;">
                    <div class="col-6" style="flex: 0 0 50%; padding: 0 8px;">
                        <strong>Codice Evento:</strong><br>
                        <span class="event-code" style="background: #f8f9fa; padding: 4px 8px; border-radius: 4px; font-family: monospace;">${event.codice_evento || 'N/D'}</span>
                    </div>
                    <div class="col-6" style="flex: 0 0 50%; padding: 0 8px;">
                        <strong>Tipo Evento:</strong><br>
                        ${event.tipo_evento || 'N/D'}
                    </div>
                </div>
                <div class="row mt-2" style="display: flex; flex-wrap: wrap; margin: 0 -8px;">
                    <div class="col-12" style="flex: 0 0 100%; padding: 0 8px;">
                        <strong>Evento:</strong><br>
                        ${event.evento || 'N/D'}
                    </div>
                </div>
                <div class="row mt-2" style="display: flex; flex-wrap: wrap; margin: 0 -8px;">
                    <div class="col-12" style="flex: 0 0 100%; padding: 0 8px;">
                        <strong>Località:</strong><br>
                        ${event.localita || 'N/D'}
                    </div>
                </div>
            </div>
            
            <!-- Date e Orari -->
            <div class="detail-section mb-4">
                <h5 class="section-title" style="color: #2c3e50; border-bottom: 1px solid #e9ecef; padding-bottom: 0.5rem;">
                    <i class="fas fa-calendar-alt me-2"></i>Date e Orari
                </h5>
                <div class="row" style="display: flex; flex-wrap: wrap; margin: 0 -8px;">
                    <div class="col-6" style="flex: 0 0 50%; padding: 0 8px;">
                        <strong>Data Inserimento:</strong><br>
                        ${event.data_inserimento || 'N/D'}
                    </div>
                    <div class="col-6" style="flex: 0 0 50%; padding: 0 8px;">
                        <strong>Data Evento:</strong><br>
                        ${event.data_evento || 'N/D'}
                    </div>
                </div>
                <div class="row mt-2" style="display: flex; flex-wrap: wrap; margin: 0 -8px;">
                    <div class="col-6" style="flex: 0 0 50%; padding: 0 8px;">
                        <strong>Dalle Ore:</strong><br>
                        ${event.ora_dalle || 'N/D'}
                    </div>
                    <div class="col-6" style="flex: 0 0 50%; padding: 0 8px;">
                        <strong>Alle Ore:</strong><br>
                        ${event.ora_alle || 'N/D'}
                    </div>
                </div>
            </div>
            
            <!-- Stato Evento -->
            <div class="detail-section mb-4">
                <h5 class="section-title" style="color: #2c3e50; border-bottom: 1px solid #e9ecef; padding-bottom: 0.5rem;">
                    <i class="fas fa-check-circle me-2"></i>Stato Evento
                </h5>
                <div class="status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px;">
                    <div class="status-item" style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                        <strong>Calendario</strong><br>
                        <span class="badge ${getStatusBadgeClass(event.calendario)}">${event.calendario || 'N/D'}</span>
                    </div>
                    <div class="status-item" style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                        <strong>Preventivo</strong><br>
                        <span class="badge ${getStatusBadgeClass(event.preventivo)}">${event.preventivo || 'N/D'}</span>
                    </div>
                    <div class="status-item" style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                        <strong>Accettazione</strong><br>
                        <span class="badge ${getStatusBadgeClass(event.accettazione)}">${event.accettazione || 'N/D'}</span>
                    </div>
                    <div class="status-item" style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                        <strong>Games Inserito</strong><br>
                        <span class="badge ${getStatusBadgeClass(event.games_inserito)}">${event.games_inserito || 'N/D'}</span>
                    </div>
                    <div class="status-item" style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                        <strong>Games Completo</strong><br>
                        <span class="badge ${getStatusBadgeClass(event.games_completo)}">${event.games_completo || 'N/D'}</span>
                    </div>
                    <div class="status-item" style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                        <strong>Allegato 5</strong><br>
                        <span class="badge ${getStatusBadgeClass(event.allegato5)}">${event.allegato5 || 'N/D'}</span>
                    </div>
                </div>
            </div>
            
            <!-- Note -->
            ${event.note ? `
            <div class="detail-section mb-4">
                <h5 class="section-title" style="color: #2c3e50; border-bottom: 1px solid #e9ecef; padding-bottom: 0.5rem;">
                    <i class="fas fa-sticky-note me-2"></i>Note
                </h5>
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; border-left: 4px solid #3498db;">
                    ${event.note}
                </div>
            </div>
            ` : ''}
            
            <!-- Allegato PDF -->
            ${event.allegato5_pdf ? `
            <div class="detail-section">
                <h5 class="section-title" style="color: #2c3e50; border-bottom: 1px solid #e9ecef; padding-bottom: 0.5rem;">
                    <i class="fas fa-paperclip me-2"></i>Allegato
                </h5>
                <a href="${event.allegato5_pdf}" target="_blank" class="btn btn-primary btn-sm">
                    <i class="fas fa-download me-2"></i>Scarica Allegato PDF
                </a>
            </div>
            ` : ''}
        </div>
    `;
}

// Funzione helper per le classi dei badge di stato
function getStatusBadgeClass(status) {
    switch(status) {
        case 'SI': return 'bg-success';
        case 'NO': return 'bg-danger';
        case 'CONC': return 'bg-warning';
        default: return 'bg-secondary';
    }
}

// Funzione per gestire l'upload dell'allegato
function uploadAttachment(eventId) {
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = '.pdf';
    fileInput.style.display = 'none';
    
    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            
            // Verifica che sia un PDF
            if (file.type !== 'application/pdf') {
                alert('Per favore, seleziona un file PDF.');
                return;
            }
            
            // Verifica la dimensione del file (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Il file è troppo grande. Dimensione massima consentita: 5MB');
                return;
            }
            
            if (confirm(`Sei sicuro di voler allegare il file "${file.name}" all'evento?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'eventi.php';
                form.enctype = 'multipart/form-data';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'upload_attachment';
                form.appendChild(actionInput);
                
                const eventIdInput = document.createElement('input');
                eventIdInput.type = 'hidden';
                eventIdInput.name = 'event_id';
                eventIdInput.value = eventId;
                form.appendChild(eventIdInput);
                
                // Creara un nuovo input file e trasferisce il file
                const newFileInput = document.createElement('input');
                newFileInput.type = 'file';
                newFileInput.name = 'attachment';
                
                // Trasferisce il file selezionato
                const dt = new DataTransfer();
                dt.items.add(new File([file], file.name, { type: file.type }));
                newFileInput.files = dt.files;
                
                form.appendChild(newFileInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    });
    
    document.body.appendChild(fileInput);
    fileInput.click();
    document.body.removeChild(fileInput);
}

// Animazione per i card
function initCardAnimations() {
    const cards = document.querySelectorAll('.card, .dashboard-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
}

// Tooltip per i bottoni
function initTooltips() {
    const tooltipTriggers = document.querySelectorAll('[title]');
    tooltipTriggers.forEach(trigger => {
        trigger.addEventListener('mouseenter', function(e) {
        });
    });
}

// Funzione per mostrare un messaggio di alert
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Rimuove l'alert dopo 5 secondi
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Funzione per esportare in Excel
function exportToExcel() {
    // Funzione da implementare completamente
    showAlert('Funzionalità di esportazione Excel in sviluppo', 'warning');
}

// Validazione del form
function initFormValidation() {
    const form = document.querySelector('form.needs-validation');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    }
}

// Inizializza tutte le funzioni quando il DOM è caricato
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza le tabs
    initTabs();
    
    // Inizializza animazioni e tooltip
    initCardAnimations();
    initTooltips();
    
    // Event listener per il tipo evento
    const tipoEventoSelect = document.getElementById('tipo_evento');
    if (tipoEventoSelect) {
        tipoEventoSelect.addEventListener('change', updateCodiceEvento);
        updateCodiceEvento();
    }
    
    // Event listener per il campo note
    const noteField = document.getElementById('note');
    if (noteField) {
        noteField.addEventListener('input', updateCharCounter);
        updateCharCounter(); // Aggiorna il contatore iniziale
    }
    
    // Event listener per il pulsante di esportazione Excel
    const exportBtn = document.getElementById('export-excel');
    if (exportBtn) {
        exportBtn.addEventListener('click', exportToExcel);
    }

    // Hover effects alle righe
    const tableRows = document.querySelectorAll('.compact-table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8fafc';
        });
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });

    // Inizializza validazione form
    initFormValidation();
});
