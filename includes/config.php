<?php
require_once __DIR__ . '/session.php';

session_start();
$serverName = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

// Encrypted connection when DB_SSL_CA names a CA certificate (Aiven requires it)
if (getenv('DB_SSL_CA')) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = getenv('DB_SSL_CA');
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

try {
    // utf8, like the tables (no emoji)
    $conn = new PDO("mysql:host=$serverName;port=$port;dbname=$dbName;charset=utf8", $username, $password, $options);
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Service temporarily unavailable.');
}

function handle_db_error(string $context, PDOException $e): void {
    error_log("DB error [$context] at " . $e->getFile() . ':' . $e->getLine() . ': ' . $e->getMessage());
    http_response_code(500);
    die('Something went wrong. Please try again.');
}

// Show an alert, go to $page and stop
function alert_and_go(string $message, string $page): void {
    $flags = JSON_HEX_TAG | JSON_HEX_AMP;
    echo '<script>alert(' . json_encode($message, $flags) . '); window.location.assign(' . json_encode($page, $flags) . ');</script>';
    exit;
}

// Length in characters; false for invalid UTF-8
function text_length(string $text): int|false {
    return preg_match_all('/./su', $text);
}

// The utf8 tables cannot store characters above U+FFFF (emoji) or invalid UTF-8
function valid_text(string $text): bool {
    return preg_match('/[\x{10000}-\x{10FFFF}]/u', $text) === 0;
}

// Shared demo account (sign-in in the README)
const DEMO_USERNAME = 'demo';

// By the stored name: "DEMO " logs in to the demo account too
function is_demo_account(PDO $conn, int|string $userId): bool {
    try {
        $q = $conn->prepare("SELECT username FROM user WHERE userid = :id");
        $q->execute(['id' => $userId]);
        return $q->fetchColumn() === DEMO_USERNAME;
    } catch (PDOException $e) {
        handle_db_error('check demo account', $e);
    }
}

// Run a recommender in the background, one at a time (flock), at low priority (nice)
function refresh_recommendations(string $script, string ...$args): void {
    $command = 'python ' . $script;
    foreach ($args as $arg) {
        $command .= ' ' . escapeshellarg($arg);
    }

    // Output dropped, or PHP would wait for it
    $dir = escapeshellarg(dirname(__DIR__) . '/recommender');
    exec("cd $dir && flock /tmp/healthme-recommender.lock nice -n 19 $command > /dev/null &");
}
