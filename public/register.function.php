<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

// Strings only (a forged request can send arrays)
if (isset($_POST['username_r']) && isset($_POST['password_r']) && isset($_POST['email_r'])
    && is_string($_POST['username_r']) && is_string($_POST['password_r']) && is_string($_POST['email_r'])) {
    csrf_verify();
    $username = $_POST['username_r'];
    $mail = $_POST['email_r'];

    // Both required; new passwords need 8 characters (invalid UTF-8 counts as too short)
    if (trim($username) === '') {
        alert_and_go('Please enter a username.', 'login.php');
    }
    if ($_POST['password_r'] === '') {
        alert_and_go('Please enter a password.', 'login.php');
    }
    if (text_length($_POST['password_r']) < 8) {
        alert_and_go('Password must be at least 8 characters.', 'login.php');
    }
    $password = password_hash($_POST['password_r'], PASSWORD_DEFAULT);
    if (text_length($username) > 50) {
        alert_and_go('Username is too long (50 characters at most).', 'login.php');
    }
    if (text_length($mail) > 255) {
        alert_and_go('Email is too long (255 characters at most).', 'login.php');
    }
    if (!valid_text($username)) {
        alert_and_go("Your username has a character that can't be used. Please choose a different username.", 'login.php');
    }
    if (!valid_text($mail)) {
        alert_and_go("Your email has a character that can't be used. Please use a different email.", 'login.php');
    }
    if (filter_var($mail, FILTER_VALIDATE_EMAIL) === false) {
        alert_and_go('Please enter a valid email address.', 'login.php');
    }

    try {
        // Compared as at login: "PETER" and "peter " match "peter"
        $check = $conn->prepare("SELECT COUNT(*) FROM user WHERE username = :username");
        $check->execute(['username' => $username]);
        if ($check->fetchColumn() > 0) {
            alert_and_go('This username is already taken. Please choose another one.', 'login.php');
        }

        // The account and its quiz row together, or neither
        $conn->beginTransaction();

        // g stays -1 until foodrec.py groups the user
        $stmt = $conn->prepare("INSERT INTO user (username, password, mail) VALUES (:username, :password, :mail)");
        $stmt->execute(['username' => $username, 'password' => $password, 'mail' => $mail]);
        $newUserId = $conn->lastInsertId();
        $examStmt = $conn->prepare("INSERT INTO exam (userid) VALUES (:userid)");
        $examStmt->execute(['userid' => $newUserId]);
        $conn->commit();
        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION['userid'] = $newUserId;
        $_SESSION['username'] = $username;
        $_SESSION['mail'] = $mail;
        $_SESSION['sex'] = null;
        $_SESSION['height'] = null;
        $_SESSION['weight'] = null;
        $_SESSION['age'] = null;
        alert_and_go('Registered successfully!', 'login.php');
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('DB error [register] at ' . $e->getFile() . ':' . $e->getLine() . ': ' . $e->getMessage());
        alert_and_go('Registration failed. Please try again.', 'login.php');
    }
} else {
    alert_and_go('Please enter a username, email and password.', 'login.php');
}
