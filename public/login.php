<?php
require_once dirname(__DIR__) . '/includes/session.php';

session_start();
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
<body class="fill-screen">

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
                                        <li><a href="about.php">About</a></li>
                                        <li><a href="login.php">Login</a></li>
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

<!-- Login and register forms -->
<section id="contact">
    <div class="container">
        <div class="row">
            <div class="col-md-3 col-sm-12"></div>
            <div class="col-md-6 col-sm-12">
                <div id="log">
                    <h1>Login</h1>
                    <div class="contact-form">
                        <form id="contact-form" method="post" action="login.function.php">
                            <?php echo csrf_field(); ?>
                            <input name="username" type="text" class="form-control" placeholder="Username" required>
                            <input name="password" type="password" class="form-control" placeholder="Password" required>
                            <div class="contact-submit">
                                <input type="submit" class="form-control submit" value="Login">
                            </div>
                        </form>
                    </div>
                    <p>Not a member yet? <a href="javascript:show(this, 'log', 'res')">Register now</a></p>
                </div>
                <div id="res" style="display:none">
                    <h1>Register</h1>
                    <div class="contact-form">
                        <form id="contact-form" method="post" action="register.function.php">
                            <?php echo csrf_field(); ?>
                            <input name="username_r" type="text" class="form-control" placeholder="Username" maxlength="50" required>
                            <input name="email_r" type="email" class="form-control" placeholder="Email" maxlength="255" required>
                            <input name="password_r" type="password" class="form-control" placeholder="Password (8 characters at least)" minlength="8" required>
                            <div class="contact-submit">
                                <input type="submit" class="form-control submit" value="Register">
                            </div>
                        </form>
                    </div>
                    <p>Already have an account? <a href="javascript:show(this, 'log', 'res')">Log In</a></p>
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

<script src="assets/js/jquery.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>
