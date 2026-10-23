<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

header('Content-Type:text/html; charset=utf-8');

// Questions 1 and 7 take one answer, the others one or more
$quizChoices = [
    'checkbox1' => ['吃葷', '吃素'],
    'checkbox2' => ['牛肉', '豬肉', '羊肉', '雞肉', '魚肉', '吃素'],
    'checkbox3' => ['高麗菜', '空心菜', '地瓜葉', '羅美生菜', '馬鈴薯'],
    'checkbox4' => ['沙拉', '速食牛肉漢堡', '水果優格', '泡麵', '義大利麵'],
    'checkbox5' => ['蒸', '炸', '炒', '燉', '水煮'],
    'checkbox6' => ['瓦斯爐', '電磁爐', '電鍋', '微波爐', '烤箱'],
    'checkbox7' => ['0', '2', '4', '6', '8'],
];

function valid_answers(array $choices): bool {
    foreach ($choices as $question => $allowed) {
        $answer = $_POST[$question];
        if ($question == 'checkbox1' || $question == 'checkbox7') {
            if (!in_array($answer, $allowed, true)) {
                return false;
            }
            continue;
        }
        if (!is_array($answer) || count($answer) == 0) {
            return false;
        }
        foreach ($answer as $one) {
            if (!in_array($one, $allowed, true)) {
                return false;
            }
        }
    }
    return true;
}

try {
    if (isset($_POST['checkbox1']) && isset($_POST['checkbox2']) && isset($_POST['checkbox3']) && isset($_POST['checkbox4']) && isset($_POST['checkbox5']) && isset($_POST['checkbox6']) && isset($_POST['checkbox7'])) {
        csrf_verify();
        if (!valid_answers($quizChoices)) {
            alert_and_go('Please choose answers from the list.', 'profile.php');
        }
        $userId = $_SESSION['userid'];
        $q1 = $_POST['checkbox1'];
        $q2 = $_POST['checkbox2'];
        $q2 = implode(',', $q2);
        $q3 = $_POST['checkbox3'];
        $q3 = implode(',', $q3);
        $q4 = $_POST['checkbox4'];
        $q4 = implode(',', $q4);
        $q5 = $_POST['checkbox5'];
        $q5 = implode(',', $q5);
        $q6 = $_POST['checkbox6'];
        $q6 = implode(',', $q6);
        $q7 = $_POST['checkbox7'];
        $sql = "UPDATE exam
            SET vegetarian = :vegetarian, meat = :meat, vegetable = :vegetable, food = :food, cook = :cook,
                heat = :heat, eatout = :eatout
            WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'vegetarian' => $q1,
            'meat' => $q2,
            'vegetable' => $q3,
            'food' => $q4,
            'cook' => $q5,
            'heat' => $q6,
            'eatout' => $q7,
            'userid' => $userId,
        ]);
        refresh_recommendations('foodrec.py');
        refresh_recommendations('blogrec.py', (string) $userId);
        alert_and_go('Thanks for taking the quiz!', 'profile.php');
    } else {
        alert_and_go('Please answer every question.', 'profile.php');
    }
} catch (PDOException $e) {
    handle_db_error('save exam answers', $e);
}
