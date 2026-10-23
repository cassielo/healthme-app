<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

header('Content-Type:text/html; charset=utf-8');
$meals = ['早餐', '中餐', '晚餐', '宵夜', '其他'];
if (isset($_POST['date']) && isset($_POST['meal']) && isset($_POST['food'])) {
    csrf_verify();
    $userId = $_SESSION['userid'];
    $date = $_POST['date'];
    $meal = $_POST['meal'];
    $food = $_POST['food'];

    // The date must be a real day in 2000-2100 (2026-02-30 is not)
    $day = is_string($date) ? DateTime::createFromFormat('!Y-m-d', $date) : false;
    if (!$day || $day->format('Y-m-d') !== $date || $day->format('Y') < 2000 || $day->format('Y') > 2100) {
        alert_and_go('Please choose a valid date.', 'profile.php');
    }
    if (!in_array($meal, $meals, true)) {
        alert_and_go('Please choose a meal from the list.', 'profile.php');
    }
    if (!is_array($food) || count($food) == 0 || count(array_filter($food, 'is_string')) != count($food)) {
        alert_and_go('Please choose foods from the list.', 'profile.php');
    }

    // All foods or none
    try {
        $names = array_values(array_unique($food));
        $known = $conn->prepare("SELECT COUNT(DISTINCT foodname)
            FROM food
            WHERE foodname IN (" . implode(',', array_fill(0, count($names), '?')) . ")");
        $known->execute($names);
        if ($known->fetchColumn() != count($names)) {
            alert_and_go('Please choose foods from the list.', 'profile.php');
        }
        $stmt = $conn->prepare("INSERT INTO eat (userid, date, meal, food) VALUES (:userid, :date, :meal, :food)");
        $conn->beginTransaction();
        foreach ($food as $f) {
            $stmt->execute(['userid' => $userId, 'date' => $date, 'meal' => $meal, 'food' => $f]);
        }
        $conn->commit();
        refresh_recommendations('foodrec.py');
        alert_and_go('Meal recorded!', 'profile.php');
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('DB error [record diet entry] at ' . $e->getFile() . ':' . $e->getLine() . ': ' . $e->getMessage());
        alert_and_go('Could not save your record.', 'profile.php');
    }
} else {
    alert_and_go('Please choose a date, a meal and at least one food.', 'profile.php');
}
