<?php
require_once dirname(__DIR__) . '/includes/session.php';

session_start();
require_once dirname(__DIR__) . '/includes/csrf.php';

csrf_verify();
session_destroy();
header('Location: index.php');
exit;
