<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

csrf_verify();

// By id: older data may hold duplicate usernames
$id = $_SESSION['userid'];
$passwordChanged = false;

// An empty, missing or array field means "leave unchanged"
function posted(string $name): string {
    $value = $_POST[$name] ?? '';
    return is_string($value) ? $value : '';
}

$password = posted('password');
$weight = posted('weight');
$age = posted('age');
$height = posted('height');
$sex = posted('sex');

// Check every field before saving any
if ($height != '' && !(is_numeric($height) && $height >= 50 && $height <= 250)) {
    alert_and_go('Please enter a height between 50 and 250 cm.', 'profile.php');
}
if ($weight != '' && !(is_numeric($weight) && $weight >= 20 && $weight <= 300)) {
    alert_and_go('Please enter a weight between 20 and 300 kg.', 'profile.php');
}
if ($age != '' && !(preg_match('/^[0-9]+$/', $age) && $age >= 1 && $age <= 120)) {
    alert_and_go('Please enter an age between 1 and 120.', 'profile.php');
}
if ($sex != '' && !in_array($sex, ['male', 'female', 'other'], true)) {
    alert_and_go('Please choose male, female or other.', 'profile.php');
}

// Invalid UTF-8 (length false) counts as too short
if ($password != '' && text_length($password) < 8) {
    alert_and_go('Password must be at least 8 characters.', 'profile.php');
}

// The shared demo account keeps its published password
if ($password != '' && is_demo_account($conn, $id)) {
    alert_and_go("The demo account's password can't be changed.", 'profile.php');
}
$personalChanged = $weight != '' || $age != '' || $height != '' || $sex != '';

try {
    if ($password != '') {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE user SET password = :password WHERE userid = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['password' => $password, 'id' => $id]);
        $passwordChanged = true;
    }
    if ($weight != '') {
        $weight = (float) $weight;
        $sql = "UPDATE user SET weight = :weight WHERE userid = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['weight' => $weight, 'id' => $id]);
        $_SESSION['weight'] = $weight;
    }
    if ($age != '') {
        $age = (int) $age;
        $sql = "UPDATE user SET age = :age WHERE userid = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['age' => $age, 'id' => $id]);
        $_SESSION['age'] = $age;
    }
    if ($height != '') {
        $height = (float) $height;
        $sql = "UPDATE user SET height = :height WHERE userid = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['height' => $height, 'id' => $id]);
        $_SESSION['height'] = $height;
    }
    if ($sex != '') {
        $sql = "UPDATE user SET sex = :sex WHERE userid = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['sex' => $sex, 'id' => $id]);
        $_SESSION['sex'] = $sex;
    }
    if ($personalChanged) {
        refresh_recommendations('foodrec.py');
    }
    if ($passwordChanged) {
        // A new password ends the session
        session_destroy();
        alert_and_go('Profile updated. Please log in again with your new password.', 'login.php');
    } else {
        alert_and_go('Profile updated!', 'profile.php');
    }
} catch (PDOException $e) {
    handle_db_error('update profile', $e);
}
