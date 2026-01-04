<?php
require_once 'config.php';
require_once 'functions.php';

// Verifica se l'utente è loggato
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Verifica se l'utente è Admin
if ($_SESSION['user_role'] !== 'Admin') {
    header('Location: index.php');
    exit;
}

// PROCESSING DELLE AZIONI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    switch($_POST['action']) {
        case 'create_user':
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $ruolo = $_POST['ruolo'] ?? '';
            $email = trim($_POST['email'] ?? '');
            
            if (empty($username) || empty($password) || empty($ruolo)) {
                $error_message = "Username, password e ruolo sono obbligatori";
            } elseif (create_user($username, $password, $ruolo, $email)) {
                $success_message = "Utente creato con successo";
            } else {
                $error_message = "Errore durante la creazione dell'utente";
            }
            break;
            
        case 'delete_user':
            if ($user_id > 0) {
                // Impedisce di cancellare se stessi
                if ($user_id == $_SESSION['user_id']) {
                    $error_message = "Non puoi cancellare il tuo account";
                } elseif (delete_user($user_id)) {
                    $success_message = "Utente cancellato con successo";
                } else {
                    $error_message = "Errore durante la cancellazione dell'utente";
                }
            }
            break;
            
        case 'change_password':
            $new_password = $_POST['new_password'] ?? '';
            if ($user_id > 0 && !empty($new_password)) {
                if (change_user_password($user_id, $new_password)) {
                    $success_message = "Password cambiata con successo";
                } else {
                    $error_message = "Errore durante il cambio password";
                }
            } else {
                $error_message = "Password obbligatoria";
            }
            break;
    }
}

// Ottieni tutti gli utenti
$users = get_all_users();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Utenti - <?php echo APP_NAME; ?></title>
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
                    <li><a href="storico_eventi.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'storico_eventi.php' ? 'active' : ''; ?>">
                    <i class="fas fa-history me-2"></i>Storico
                    </a></li>
                <?php if ($_SESSION['user_role'] === 'Admin'): ?>
                    <li><a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users me-2"></i>Utenti
                    </a></li>
                <?php endif; ?>
                    <?php if ($_SESSION['user_role'] === 'Admin'): ?>
                    <li><a href="users.php" class="active">
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
            <h1><i class="fas fa-users me-2"></i>Gestione Utenti</h1>
            <p class="text-muted">Gestisci gli account utente del sistema</p>
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
        
        <div class="row">
            <!-- Form Creazione Utente -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-plus me-2"></i>Nuovo Utente
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="users.php" method="post" id="create-user-form">
                            <input type="hidden" name="action" value="create_user">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" id="username" name="username" class="form-control" required 
                                       placeholder="Inserisci username">
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       placeholder="Inserisci email (opzionale)">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <div class="input-group">
                                    <input type="password" id="password" name="password" class="form-control" required 
                                           placeholder="Inserisci password">
                                    <button type="button" class="btn btn-outline-secondary" onclick="generatePassword()">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div class="form-text">Minimo 8 caratteri</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ruolo" class="form-label">Ruolo *</label>
                                <select id="ruolo" name="ruolo" class="form-control" required>
                                    <option value="">Seleziona ruolo...</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Centralinista">Centralinista</option>
                                    <option value="LeadCent">LeadCent</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>Crea Utente
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Lista Utenti -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Utenti del Sistema
                            <span class="badge bg-primary ms-2"><?php echo count($users); ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <!-- <th>  </th> -->
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Ruolo</th>
                                        <th width="180" class="text-center">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <!-- <td><?php echo $user['id']; ?></td> -->
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                    <span class="badge bg-info badge-sm">Tu</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email'] ?? 'N/D'); ?></td>
                                            <td>
                                                <span class="badge <?php echo getRoleBadgeClass($user['ruolo']); ?>">
                                                    <?php echo $user['ruolo']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-warning" 
                                                            onclick="showChangePasswordModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>')"
                                                            title="Cambia Password">
                                                        <i class="fas fa-key"></i>
                                                    </button>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <button type="button" class="btn btn-outline-danger"
                                                            onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>')"
                                                            title="Cancella Utente">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <div class="empty-state">
                                                    <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                                    <p class="text-muted mb-0">Nessun utente trovato</p>
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
    
    <!-- Modal Cambio Password -->
    <div id="changePasswordModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-key me-2"></i>Cambia Password
                </h5>
                <button type="button" class="btn-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm" method="post">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="user_id" id="change_password_user_id">
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nuova Password *</label>
                        <div class="input-group">
                            <input type="password" id="new_password" name="new_password" class="form-control" required 
                                   placeholder="Inserisci nuova password">
                            <button type="button" class="btn btn-outline-secondary" onclick="generatePasswordForModal()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <div class="form-text">Minimo 8 caratteri</div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        La password verrà cambiata immediatamente dopo la conferma.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Annulla</button>
                <button type="button" class="btn btn-primary" onclick="submitChangePassword()">Cambia Password</button>
            </div>
        </div>
    </div>
    
    <script src="script.js"></script>
    <script>
    // Funzione per generare password
    function generatePassword() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%&*';
        let password = '';
        for (let i = 0; i < 12; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('password').value = password;
    }
    
    // Funzione per generare password nel modal
    function generatePasswordForModal() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%&*';
        let password = '';
        for (let i = 0; i < 12; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('new_password').value = password;
    }
    
    // Funzione per mostrare il modal cambio password
    function showChangePasswordModal(userId, username) {
        document.getElementById('change_password_user_id').value = userId;
        document.querySelector('#changePasswordModal .modal-title').innerHTML = 
            `<i class="fas fa-key me-2"></i>Cambia Password - ${username}`;
        document.getElementById('changePasswordModal').style.display = 'flex';
    }
    
    // Funzione per chiudere il modal
    function closeModal() {
        document.getElementById('changePasswordModal').style.display = 'none';
        document.getElementById('changePasswordForm').reset();
    }
    
    // Funzione per inviare il cambio password
    function submitChangePassword() {
        const form = document.getElementById('changePasswordForm');
        if (form.checkValidity()) {
            form.submit();
        } else {
            form.reportValidity();
        }
    }
    
    // Funzione per confermare cancellazione
    function confirmDelete(userId, username) {
        if (confirm(`Sei sicuro di voler cancellare l'utente "${username}"?\n\nQuesta azione non può essere annullata.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'users.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete_user';
            form.appendChild(actionInput);
            
            const userIdInput = document.createElement('input');
            userIdInput.type = 'hidden';
            userIdInput.name = 'user_id';
            userIdInput.value = userId;
            form.appendChild(userIdInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Chiudi modal cliccando fuori
    document.addEventListener('click', function(e) {
        if (e.target.id === 'changePasswordModal') {
            closeModal();
        }
    });
    </script>
</body>
</html>

<?php
// Funzione helper per le classi dei badge dei ruoli
function getRoleBadgeClass($role) {
    switch($role) {
        case 'Admin': return 'bg-danger';
        case 'Centralinista': return 'bg-primary';
        case 'LeadCent': return 'bg-warning';
        default: return 'bg-secondary';
    }
}
?>
