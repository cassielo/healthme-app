<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

header('Content-Type:text/html; charset=utf-8');
csrf_verify();
$articleId = $_POST['artid'] ?? '';
$userId = $_SESSION['userid'];

try {
    // Unknown article: record nothing
    $link = false;
    if (is_string($articleId) && preg_match('/^[0-9]+$/', $articleId)) {
        $q = $conn->prepare("SELECT link FROM article WHERE artid = :artid");
        $q->execute(['artid' => $articleId]);
        $link = $q->fetchColumn();
    }
    if (!$link) {
        alert_and_go('Article not found.', 'blog.php');
    }
    $stmt = $conn->prepare("INSERT INTO readart (userid, articleid) VALUES (:userid, :artid)");
    $stmt->execute(['userid' => $userId, 'artid' => $articleId]);
    refresh_recommendations('blogrec.py', (string) $userId);
    header('Location: ' . $link);
} catch (PDOException $e) {
    handle_db_error('record article read', $e);
}
