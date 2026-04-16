<?php
require_once __DIR__ . '/app/session.php';
logout_user();
header('Location: views/login.php');
exit;