<?php
// Health check for the scheduled pinger: one cheap query, so the database stays in use and its state is visible
require_once dirname(__DIR__) . '/includes/config.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
try {
    $conn->query('SELECT 1 FROM user LIMIT 1')->fetchColumn();
    echo 'ok';
} catch (PDOException $e) {
    handle_db_error('health check', $e);
}
