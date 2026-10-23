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
<section id="header" class="header-five">
    <div class="container">
        <div class="row">
            <div class="col-md-offset-3 col-md-6 col-sm-offset-2 col-sm-8">
                <div class="header-thumb">
                    <h1>Articles</h1>
                    <h3>Recommended for You</h3>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recommended articles -->
<section id="blog">
    <div class="container">
        <div class="row">
            <?php
            $userId = $_SESSION['userid'];

            try {
                // Own picks once there are quiz answers or reads (what blogrec.py uses)
                $quiz = $conn->prepare("SELECT COUNT(*) FROM exam
                    WHERE userid = :userid AND CONCAT_WS('', vegetarian, meat, vegetable, food, cook, heat) <> ''");
                $quiz->execute(['userid' => $userId]);
                $reads = $conn->prepare("SELECT COUNT(*) FROM readart WHERE userid = :userid");
                $reads->execute(['userid' => $userId]);
                $picks = '';
                if ($quiz->fetchColumn() > 0 || $reads->fetchColumn() > 0) {
                    $q = $conn->prepare("SELECT articleid FROM recarticle WHERE userid = :userid");
                    $q->execute(['userid' => $userId]);
                    $picks = (string) $q->fetchColumn();
                }

                // Otherwise, or until they are worked out: the 9 most read articles
                if ($picks === '') {
                    $popular = $conn->query("SELECT article.artid
                        FROM article
                        JOIN readart ON readart.articleid = article.artid
                        GROUP BY article.artid
                        ORDER BY COUNT(*) DESC, article.artid
                        LIMIT 9");
                    $picks = implode(',', $popular->fetchAll(PDO::FETCH_COLUMN));
                }
                $q1 = $conn->prepare("SELECT artid, link, title, pic FROM article WHERE artid = :artid");
            } catch (PDOException $e) {
                handle_db_error('fetch recommended articles', $e);
            }
            ?>
            <?php
            foreach (explode(',', $picks) as $articleId) {
                try {
                    $q1->execute(['artid' => $articleId]);
                    $r1 = $q1->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    handle_db_error('fetch article details', $e);
                }
                if (!$r1) {
                    continue;
                }
            ?>
                <div class="col-md-4 col-sm-4">
                    <div class="blog-thumb"> <a href="<?php echo htmlspecialchars($r1['link'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank"><img src="<?php echo htmlspecialchars($r1['pic'], ENT_QUOTES, 'UTF-8'); ?>" class="img-responsive" alt="Blog"></a> <a href="<?php echo htmlspecialchars($r1['link'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                        <h3><?php echo htmlspecialchars($r1['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        </a>
                        <form action="read.function.php" method="post" target="_blank" style="display:inline;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="artid" value="<?php echo htmlspecialchars($r1['artid'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn btn-default">Read More</button>
                        </form>
                    </div>
                </div>
            <?php
            }
            ?>
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
