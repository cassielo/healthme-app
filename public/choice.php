<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
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
                                        <li><a href="profile.php">Profile</a></li>
                                        <li><a href="blog.php">Articles</a></li>
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
<section id="header" class="header-five">
    <div class="container">
        <div class="row">
            <div class="col-md-offset-3 col-md-6 col-sm-offset-2 col-sm-8">
                <div class="header-thumb">
                    <h1>Better Choice</h1>
                    <h3>You might like</h3>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Food picks for the user's group -->
<section id="blog">
    <div class="container">
        <div id="better" class="tab">
            <?php

            // This user's group's picks
            try {
                $q = $conn->prepare("SELECT recfood.breakfast, recfood.lunch, recfood.dinner
                    FROM recfood JOIN user ON user.g = recfood.g
                    WHERE user.userid = :userid");
                $q->execute(['userid' => $_SESSION['userid']]);
                $r = $q->fetch(PDO::FETCH_ASSOC);

                // Group without picks: use group -1 (all users)
                if (!$r) {
                    $r = $conn->query("SELECT breakfast, lunch, dinner FROM recfood WHERE g = -1")->fetch(PDO::FETCH_ASSOC);
                }
                $foodQuery = $conn->prepare("SELECT foodname, pic, carbon, cal FROM food WHERE foodid = :foodid");
            } catch (PDOException $e) {
                handle_db_error('fetch food recommendations', $e);
            }
            $meals = ['breakfast' => 'Breakfast', 'lunch' => 'Lunch', 'dinner' => 'Dinner'];
            foreach ($meals as $column => $title) {
            ?>
                <div>
                    <div class="row"><?php if ($column != 'breakfast') { echo '<br>'; } ?>
                        <h1>Popular Healthy <?php echo $title; ?> Picks</h1>
                        <?php
                        foreach (preg_split('/ /', $r[$column] ?? '', -1, PREG_SPLIT_NO_EMPTY) as $foodId) {
                            try {
                                $foodQuery->execute(['foodid' => $foodId]);
                                $r1 = $foodQuery->fetch(PDO::FETCH_ASSOC);
                            } catch (PDOException $e) {
                                handle_db_error('fetch ' . $column . ' food details', $e);
                            }
                            if (!$r1) {
                                continue;
                            }
                        ?>
                            <div class="col-md-3 col-sm-3">
                                <div class="blog-thumb"> <img src="assets/img/food/<?php echo htmlspecialchars($r1['pic'], ENT_QUOTES, 'UTF-8'); ?>.jpg" border="0" height="180px">
                                    <h3><?php echo htmlspecialchars($r1['foodname'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <h5>Carbon emissions: <?php echo htmlspecialchars($r1['carbon'], ENT_QUOTES, 'UTF-8'); ?> g</h5>
                                    <h5>Calories: <?php echo htmlspecialchars($r1['cal'], ENT_QUOTES, 'UTF-8'); ?> kcal</h5>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
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

<script src="assets/js/jquery.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>
