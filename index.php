<?php
require_once __DIR__ . '/app/session.php';
start_session();
if (is_logged_in()) {
    header('Location: views/dashboard.php');
} else {
    header('Location: views/login.php');
}
exit;