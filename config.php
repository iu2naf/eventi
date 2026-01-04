<?php
// Imposta alcune opzioni di sessione prima di avviare la sessione
$dotenvPath = __DIR__ . '/.env';
if (file_exists($dotenvPath) && is_readable($dotenvPath)) {
    $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if ((substr($value,0,1) === '"' && substr($value,-1) === '"') || (substr($value,0,1) === "'" && substr($value,-1) === "'")) {
                $value = substr($value, 1, -1);
            }
            if (getenv($name) === false) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
            }
        }
    }
}

ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', getenv('SESSION_COOKIE_SAMESITE') ?: 'Lax');

// Avvia la sessione
if (session_status() === PHP_SESSION_NONE) {
    $forceSecure = filter_var(getenv('SESSION_COOKIE_SECURE') ?: '', FILTER_VALIDATE_BOOLEAN);
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($forceSecure === true);
    ini_set('session.cookie_secure', $isHttps ? 1 : 0);

    session_start();
}

// Configurazione del database
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'database_name');
define('DB_USER', getenv('DB_USER') ?: 'database_user');
define('DB_PASS', getenv('DB_PASS') ?: 'database_password');

// Configurazione error reporting
$APP_ENV = getenv('APP_ENV') ?: 'production';
$APP_DEBUG = filter_var(getenv('APP_DEBUG') ?: ($APP_ENV === 'development' ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);

ini_set('log_errors', 1);
$errorLogPath = getenv('ERROR_LOG') ?: (__DIR__ . '/logs/error.log');
ini_set('error_log', $errorLogPath);

if ($APP_DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
}

// Connessione al database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
                   DB_USER, 
                   DB_PASS, 
                   [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch(PDOException $e) {
    error_log("Errore connessione database: " . $e->getMessage());
    die("Errore di connessione al database. Riprova più tardi.");
}

// Impostazioni dell'applicazione
define('APP_NAME', 'Sistema di Gestione Eventi');
define('APP_VERSION', '1.0.0');

?>
