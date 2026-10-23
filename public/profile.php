<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

// Saved quiz answers (multiple choices comma separated)
try {
    $quizStmt = $conn->prepare("SELECT vegetarian, meat, vegetable, food, cook, heat, eatout
        FROM exam
        WHERE userid = :userid");
    $quizStmt->execute(['userid' => $_SESSION['userid']]);
    $savedQuiz = $quizStmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    handle_db_error('load quiz answers', $e);
}

// Saved answers are checked; an unanswered question checks its default
function quiz_checked(array $saved, string $question, string $option, bool $isDefault = false): string {
    $answer = (string) ($saved[$question] ?? '');
    if ($answer === '') {
        return $isDefault ? 'checked' : '';
    }
    return in_array($option, explode(',', $answer), true) ? 'checked' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Health Me</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/ionicons.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,700,300">

<!-- Highstock, pinned to one version -->
<script src="https://code.highcharts.com/stock/13.1.1/highstock.js"></script>
<script src="https://code.highcharts.com/stock/13.1.1/modules/exporting.js"></script>
<script src="https://code.highcharts.com/stock/13.1.1/modules/export-data.js"></script>
<script src="https://code.highcharts.com/stock/13.1.1/modules/no-data-to-display.js"></script>
<link rel="stylesheet" href="assets/css/multi-select.css">
</head>
<body>

<!-- Navigation -->
<div class="nav-container">
    <nav class="nav-inner transparent">
        <div class="navbar">
            <div class="container">
                <div class="row">
                    <div class="brand"> <a href="index.php">Health Me</a> </div>
                    <div class="navicon">
                        <div class="menu-container">
                            <div class="circle dark inline"> <i class="icon ion-navicon"></i> </div>
                            <div class="list-menu"> <i class="icon ion-close-round close-iframe"></i>
                                <div class="intro-inner">
                                    <ul id="nav-menu">
                                        <li><a href="blog.php">Articles</a></li>
                                        <li><a href="choice.php">Better Choice</a></li>
                                        <li>
                                            <form action="logout.function.php" method="post" style="display:inline;">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="nav-logout">Logout</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</div>

<!-- Header -->
<section id="header" class="header-six">
    <div class="container">
        <div class="row">
            <div class="col-md-offset-3 col-md-6 col-sm-offset-2 col-sm-8">
                <div class="header-thumb">
                    <h1>Welcome</h1>
                    <h3><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?> </h3>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tabs: quiz, profile, diet record, charts -->
<section id="portfolio">
    <div class="container">
        <div class="row">
            <div class="col-md-12 col-sm-12">
                <div class="iso-section">
                    <ul class="filter-wrapper clearfix" id="tabs">
                        <li><a href="#" class="opc-main-bg">Quiz</a></li>
                        <li><a href="#" class="opc-main-bg">My Profile</a></li>
                        <li><a href="#" class="opc-main-bg">Record my diet</a></li>
                        <li><a href="#" class="opc-main-bg">My Diet</a></li>
                    </ul>
                    <div id="contact1">
                        <div class="container">
                            <div class="row">
                                <div class="col-md-2 col-sm-12"></div>
                                <div class="col-md-8 col-sm-12">
                                    <div id="quiz" class="tab">
                                        <h1>Take a simple quiz</h1>
                                        <div class="contact-form">
                                            <form id="contact-form" method="post" action="exam.function.php">
                                                <?php echo csrf_field(); ?>
                                                <div class="row">
                                                    <h2 class="col-md-12 col-sm-12">1. Meat eater or vegetarian?</h2><br>
                                                    <div class="col-md-8 col-sm-12">
                                                        <label class="quiz-contain">Meat eater <span class="zh">吃葷</span>
                                                            <input type="radio" <?php echo quiz_checked($savedQuiz, 'vegetarian', '吃葷', true); ?> name="checkbox1" value="吃葷">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Vegetarian <span class="zh">吃素</span>
                                                            <input type="radio" <?php echo quiz_checked($savedQuiz, 'vegetarian', '吃素'); ?> name="checkbox1" value="吃素">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <h2 class="col-md-12 col-sm-12">2. Which meats do you like most? (choose all that apply)</h2><br>
                                                    <div class="col-md-8 col-sm-12">
                                                        <label class="quiz-contain">Beef <span class="zh">牛肉</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'meat', '牛肉', true); ?> name="checkbox2[]" value="牛肉">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Pork <span class="zh">豬肉</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'meat', '豬肉'); ?> name="checkbox2[]" value="豬肉">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Lamb <span class="zh">羊肉</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'meat', '羊肉'); ?> name="checkbox2[]" value="羊肉">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Chicken <span class="zh">雞肉</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'meat', '雞肉'); ?> name="checkbox2[]" value="雞肉">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Fish <span class="zh">魚肉</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'meat', '魚肉'); ?> name="checkbox2[]" value="魚肉">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">None (vegetarian) <span class="zh">吃素</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'meat', '吃素'); ?> name="checkbox2[]" value="吃素">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <h2 class="col-md-12 col-sm-12">3. Which vegetables do you like most? (choose all that apply)</h2><br>
                                                    <div class="col-md-8 col-sm-12">
                                                        <label class="quiz-contain">Cabbage <span class="zh">高麗菜</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'vegetable', '高麗菜', true); ?> name="checkbox3[]" value="高麗菜">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Water spinach <span class="zh">空心菜</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'vegetable', '空心菜'); ?> name="checkbox3[]" value="空心菜">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Sweet potato leaves <span class="zh">地瓜葉</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'vegetable', '地瓜葉'); ?> name="checkbox3[]" value="地瓜葉">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Romaine lettuce <span class="zh">羅美生菜</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'vegetable', '羅美生菜'); ?> name="checkbox3[]" value="羅美生菜">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Potato <span class="zh">馬鈴薯</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'vegetable', '馬鈴薯'); ?> name="checkbox3[]" value="馬鈴薯">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <h2 class="col-md-12 col-sm-12">4. Which of these foods do you like most? (choose all that apply)</h2><br>
                                                    <div class="col-md-8 col-sm-12">
                                                        <label class="quiz-contain">Salad <span class="zh">沙拉</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'food', '沙拉', true); ?> name="checkbox4[]" value="沙拉">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Fast-food beef burger <span class="zh">速食牛肉漢堡</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'food', '速食牛肉漢堡'); ?> name="checkbox4[]" value="速食牛肉漢堡">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Fruit yogurt <span class="zh">水果優格</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'food', '水果優格'); ?> name="checkbox4[]" value="水果優格">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Instant noodles <span class="zh">泡麵</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'food', '泡麵'); ?> name="checkbox4[]" value="泡麵">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Pasta <span class="zh">義大利麵</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'food', '義大利麵'); ?> name="checkbox4[]" value="義大利麵">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <h2 class="col-md-12 col-sm-12">5. Which cooking methods do you use most? (choose all that apply)</h2><br>
                                                    <div class="col-md-8 col-sm-12">
                                                        <label class="quiz-contain">Steaming <span class="zh">蒸</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'cook', '蒸', true); ?> name="checkbox5[]" value="蒸">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Deep-frying <span class="zh">炸</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'cook', '炸'); ?> name="checkbox5[]" value="炸">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Stir-frying <span class="zh">炒</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'cook', '炒'); ?> name="checkbox5[]" value="炒">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Stewing <span class="zh">燉</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'cook', '燉'); ?> name="checkbox5[]" value="燉">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Boiling <span class="zh">水煮</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'cook', '水煮'); ?> name="checkbox5[]" value="水煮">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <h2 class="col-md-12 col-sm-12">6. What do you usually heat food with? (choose all that apply)</h2><br>
                                                    <div class="col-md-8 col-sm-12">
                                                        <label class="quiz-contain">Gas stove <span class="zh">瓦斯爐</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'heat', '瓦斯爐', true); ?> name="checkbox6[]" value="瓦斯爐">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Induction cooker <span class="zh">電磁爐</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'heat', '電磁爐'); ?> name="checkbox6[]" value="電磁爐">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Electric rice cooker <span class="zh">電鍋</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'heat', '電鍋'); ?> name="checkbox6[]" value="電鍋">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Microwave <span class="zh">微波爐</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'heat', '微波爐'); ?> name="checkbox6[]" value="微波爐">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">Oven <span class="zh">烤箱</span>
                                                            <input type="checkbox" <?php echo quiz_checked($savedQuiz, 'heat', '烤箱'); ?> name="checkbox6[]" value="烤箱">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <h2 class="col-md-12 col-sm-12">7. How many times a week do you eat out?</h2><br>
                                                    <div class="col-md-8 col-sm-12">
                                                        <label class="quiz-contain">0 times
                                                            <input type="radio" <?php echo quiz_checked($savedQuiz, 'eatout', '0', true); ?> name="checkbox7" value="0">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">1–2 times
                                                            <input type="radio" <?php echo quiz_checked($savedQuiz, 'eatout', '2'); ?> name="checkbox7" value="2">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">3–4 times
                                                            <input type="radio" <?php echo quiz_checked($savedQuiz, 'eatout', '4'); ?> name="checkbox7" value="4">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">5–6 times
                                                            <input type="radio" <?php echo quiz_checked($savedQuiz, 'eatout', '6'); ?> name="checkbox7" value="6">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                        <label class="quiz-contain">7 or more times
                                                            <input type="radio" <?php echo quiz_checked($savedQuiz, 'eatout', '8'); ?> name="checkbox7" value="8">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="contact-submit">
                                                    <input type="submit" class="form-control submit" value="Save">
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <div id="pro" class="tab">
                                        <h1>Change my profile here</h1>
                                        <div class="contact-form">
                                            <form id="contact-form2" method="post" action="profile.function.php">
                                                <?php echo csrf_field(); ?>
                                                <h2 class="col-md-3 col-sm-12">Username</h2>
                                                <h3 class="col-md-8 col-sm-12"><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                                <h2 class="col-md-3 col-sm-12">Email</h2>
                                                <h3 class="col-md-8 col-sm-12"><?php echo htmlspecialchars($_SESSION['mail'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                                <h2 class="col-md-3 col-sm-12">Password</h2>
                                                <div class="col-md-8 col-sm-12">
                                                    <input name="password" type="password" class="form-control" placeholder="New Password (8 characters at least)" minlength="8">
                                                </div>
                                                <h2 class="col-md-3 col-sm-12">Sex</h2>
                                                <div class="col-md-8 col-sm-12">
                                                    <label class="sex-contain">Male
                                                        <?php if ($_SESSION['sex'] == 'male') { ?>
                                                            <input type="radio" checked name="sex" class="form-control" value="male">
                                                        <?php } else { ?>
                                                            <input type="radio" name="sex" class="form-control" value="male">
                                                        <?php } ?>
                                                        <span class="checkmark"></span>
                                                    </label>
                                                    <label class="sex-contain">Female
                                                        <?php if ($_SESSION['sex'] == 'female') { ?>
                                                            <input type="radio" checked name="sex" class="form-control" value="female">
                                                        <?php } else { ?>
                                                            <input type="radio" name="sex" class="form-control" value="female">
                                                        <?php } ?>
                                                        <span class="checkmark"></span>
                                                    </label>
                                                    <label class="sex-contain">Other
                                                        <?php if ($_SESSION['sex'] == 'other') { ?>
                                                            <input type="radio" checked name="sex" class="form-control" value="other">
                                                        <?php } else { ?>
                                                            <input type="radio" name="sex" class="form-control" value="other">
                                                        <?php } ?>
                                                        <span class="checkmark"></span>
                                                    </label>
                                                </div>
                                                <h2 class="col-md-3 col-sm-12">Height (cm)</h2>
                                                <div class="col-md-8 col-sm-12">
                                                    <input name="height" type="number" min="50" max="250" step="any" class="form-control" placeholder="Height" value="<?php echo htmlspecialchars($_SESSION['height'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                <h2 class="col-md-3 col-sm-12">Weight (kg)</h2>
                                                <div class="col-md-8 col-sm-12">
                                                    <input name="weight" type="number" min="20" max="300" step="any" class="form-control" placeholder="Weight" value="<?php echo htmlspecialchars($_SESSION['weight'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                <h2 class="col-md-3 col-sm-12">Age</h2>
                                                <div class="col-md-8 col-sm-12">
                                                    <input name="age" type="number" min="1" max="120" step="1" class="form-control" placeholder="Age" value="<?php echo htmlspecialchars($_SESSION['age'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                <div class="contact-submit">
                                                    <input type="submit" class="form-control submit" value="Save">
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <div id="rec" class="tab">
                                        <h1>Record my diet here</h1>
                                        <div class="contact-form">
                                            <form id="contact-form1" method="post" action="diet.function.php">
                                                <?php echo csrf_field(); ?>
                                                <h2 class="col-md-3 col-sm-12">Date</h2>
                                                <div class="col-md-8 col-sm-12">
                                                    <input name="date" type="date" class="form-control" required>
                                                </div>
                                                <h2 class="col-md-3 col-sm-12">Meal</h2>
                                                <div class="col-md-8 col-sm-12">
                                                    <select name="meal">
                                                        <option value="早餐" selected>Breakfast 早餐</option>
                                                        <option value="中餐">Lunch 中餐</option>
                                                        <option value="晚餐">Dinner 晚餐</option>
                                                        <option value="宵夜">Late-night snack 宵夜</option>
                                                        <option value="其他">Other 其他</option>
                                                    </select>
                                                </div>
                                                <h2 class="col-md-3 col-sm-12">Food</h2>
                                                <div class="col-md-8 col-sm-12">
                                                    <?php
                                                    try {
                                                        $q = $conn->query("SELECT foodname FROM food");
                                                    } catch (PDOException $e) {
                                                        handle_db_error('list foods', $e);
                                                    }
                                                    ?>
                                                    <select name="food[]" id="food" multiple>
                                                        <?php
                                                        while ($r = $q->fetch()) {
                                                        ?>
                                                            <option value="<?php echo htmlspecialchars($r['foodname'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($r['foodname'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="contact-submit">
                                                    <input type="submit" class="form-control submit" value="Record">
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab">
                                    <div class="chart">
                                        <h1>My Food Carbon Footprint</h1>
                                        <br>
                                        <div id="carbon"></div>
                                    </div>
                                    <div class="chart">
                                        <h1>Calorie Intake</h1>
                                        <br>
                                        <div id="five"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-12 col-sm-12">
                <p>© 2018 Health Me</p>
            </div>
        </div>
    </div>
</footer>

<script>
    // Daily totals beside everyone's daily average, on real dates, opening on the latest month
    Highcharts.setOptions({
        lang: {
            noData: 'No meals recorded yet'
        }
    });
    var dietChart = {
        chart: {
            type: 'column',
            height: 470
        },
        title: {
            text: ''
        },
        rangeSelector: {
            buttons: [
                {type: 'week', count: 1, text: '1w'},
                {type: 'month', count: 1, text: '1m'},
                {type: 'month', count: 3, text: '3m'},
                {type: 'all', text: 'All'}
            ],
            selected: 1,
            inputEnabled: false
        },
        xAxis: {
            // Keep real gaps between dates
            ordinal: false,

            // Label days, not hours, when there is only one day
            minTickInterval: 24 * 3600 * 1000
        },
        navigator: {
            xAxis: {
                minTickInterval: 24 * 3600 * 1000
            }
        },
        yAxis: {
            opposite: false,
            min: 0
        },
        legend: {
            enabled: true,
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'middle'
        },
        tooltip: {
            split: false,
            shared: true,
            xDateFormat: '%Y/%m/%d',
            valueDecimals: 2
        },
        plotOptions: {
            series: {
                // Never merge days: the averages would be added up
                dataGrouping: {
                    enabled: false
                }
            },
            column: {
                maxPointWidth: 30
            }
        },
        responsive: {
            rules: [{
                condition: {
                    maxWidth: 500
                },
                chartOptions: {
                    chart: {
                        height: 500
                    },
                    legend: {
                        layout: 'horizontal',
                        align: 'center',
                        verticalAlign: 'bottom'
                    }
                }
            }]
        }
    };

    // Calories chart
    Highcharts.stockChart('five', Highcharts.merge(dietChart, {
        yAxis: {
            title: {
                text: 'Calories (kcal)'
            }
        },
        tooltip: {
            valueSuffix: ' kcal'
        },
        series: [{
            name: 'Your calories',
            <?php
            try {
                // d: seconds since 1970 (UNIX_TIMESTAMP would depend on the database's time zone)
                $q = $conn->prepare("SELECT TIMESTAMPDIFF(SECOND, '1970-01-01', STR_TO_DATE(date, '%Y-%m-%d')) AS d,
                        SUM(food.cal) AS cal
                    FROM eat JOIN food ON eat.food = food.foodname
                    WHERE eat.userid = :userid
                    GROUP BY date
                    ORDER BY d");
                $q->execute(['userid' => $_SESSION['userid']]);
                $q->setFetchMode(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                handle_db_error('load user calorie chart', $e);
            }
            $dataCal = [];
            while ($r = $q->fetch()) {
                $dateCal = $r['d'] * 1000;
                $cal = $r['cal'];
                $dataCal[] = "[$dateCal, $cal]";
            }
            ?>
            data: [<?php echo join(',', $dataCal); ?>]
        }, {
            name: 'Average of all users',
            <?php
            try {
                // Everyone's average total on this user's days
                $q = $conn->prepare("SELECT d, AVG(cal) AS cal
                    FROM (
                        SELECT TIMESTAMPDIFF(SECOND, '1970-01-01', STR_TO_DATE(date, '%Y-%m-%d')) AS d, eat.userid,
                                SUM(food.cal) AS cal
                            FROM eat JOIN food ON eat.food = food.foodname
                            WHERE date IN (SELECT date FROM eat WHERE userid = :userid)
                            GROUP BY eat.userid, date
                    ) AS temp
                    GROUP BY d
                    ORDER BY d");
                $q->execute(['userid' => $_SESSION['userid']]);
            } catch (PDOException $e) {
                handle_db_error('load average calorie chart', $e);
            }
            $dataCalAve = [];
            while ($r = $q->fetch()) {
                $dateCalAve = $r['d'] * 1000;
                $calAve = $r['cal'];
                $dataCalAve[] = "[$dateCalAve, $calAve]";
            }
            ?>
            data: [<?php echo join(',', $dataCalAve); ?>]
        }
        ]
    }));

    // Carbon chart
    Highcharts.stockChart('carbon', Highcharts.merge(dietChart, {
        yAxis: {
            title: {
                text: 'Carbon emissions (g)'
            }
        },
        tooltip: {
            valueSuffix: ' g'
        },
        series: [{
            name: 'Your carbon emissions',
            <?php
            try {
                $q = $conn->prepare("SELECT TIMESTAMPDIFF(SECOND, '1970-01-01', STR_TO_DATE(date, '%Y-%m-%d')) AS d,
                        SUM(food.carbon) AS carbon
                    FROM eat JOIN food ON eat.food = food.foodname
                    WHERE eat.userid = :userid
                    GROUP BY date
                    ORDER BY d");
                $q->execute(['userid' => $_SESSION['userid']]);
                $q->setFetchMode(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                handle_db_error('load user carbon chart', $e);
            }
            $data = [];
            while ($r = $q->fetch()) {
                $date = $r['d'] * 1000;
                $carbon = $r['carbon'];
                $data[] = "[$date, $carbon]";
            }
            ?>
            data: [<?php echo join(',', $data); ?>]
        }, {
            name: 'Average of all users',
            <?php
            try {
                $q = $conn->prepare("SELECT d, AVG(carbon) AS carbon
                    FROM (
                        SELECT TIMESTAMPDIFF(SECOND, '1970-01-01', STR_TO_DATE(date, '%Y-%m-%d')) AS d, eat.userid,
                                SUM(food.carbon) AS carbon
                            FROM eat JOIN food ON eat.food = food.foodname
                            WHERE date IN (SELECT date FROM eat WHERE userid = :userid)
                            GROUP BY eat.userid, date
                    ) AS temp
                    GROUP BY d
                    ORDER BY d");
                $q->execute(['userid' => $_SESSION['userid']]);
            } catch (PDOException $e) {
                handle_db_error('load average carbon chart', $e);
            }
            $dataAve = [];
            while ($r = $q->fetch()) {
                $dateAve = $r['d'] * 1000;
                $carbonAve = $r['carbon'];
                $dataAve[] = "[$dateAve, $carbonAve]";
            }
            ?>
            data: [<?php echo join(',', $dataAve); ?>]
        }
        ]
    }));
</script>
<script src="assets/js/jquery.js"></script>
<script src="assets/js/custom.js"></script>

<!-- Food picker of the diet form -->
<script src="assets/js/jquery.multi-select.js"></script>
<script>
    $('#food').multiSelect();
</script>
</body>
</html>
