<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/functions.php';

only_ajax();
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'register':
        $username = sanitize($_POST['username'] ?? '');
        $email    = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $gender   = in_array($_POST['gender'] ?? '', ['male','female']) ? $_POST['gender'] : 'male';
        $goal     = in_array($_POST['goal'] ?? '', ['fat_loss','muscle_gain','endurance','flexibility']) ? $_POST['goal'] : 'muscle_gain';

        if (strlen($username) < 3 || strlen($username) > 50) {
            json_response(['error' => 'Username must be 3-50 characters.'], 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => 'Invalid email address.'], 422);
        }
        if (strlen($password) < 8) {
            json_response(['error' => 'Password must be at least 8 characters.'], 422);
        }

        $pdo  = get_db();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            json_response(['error' => 'Email or username already exists.'], 409);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $ins  = $pdo->prepare('INSERT INTO users (username, email, password_hash, gender, goal) VALUES (?, ?, ?, ?, ?)');
        $ins->execute([$username, $email, $hash, $gender, $goal]);
        $user_id = (int)$pdo->lastInsertId();

        login_user($user_id, $username);
        json_response(['success' => true, 'redirect' => '../views/dashboard.php']);
        break;

    case 'login':
        $email    = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            json_response(['error' => 'Email and password are required.'], 422);
        }

        $pdo  = get_db();
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            json_response(['error' => 'Invalid email or password.'], 401);
        }

        login_user((int)$user['id'], $user['username']);
        json_response(['success' => true, 'redirect' => '../views/dashboard.php']);
        break;

    case 'logout':
        logout_user();
        json_response(['success' => true, 'redirect' => '../views/login.php']);
        break;

    case 'check':
        json_response(['logged_in' => is_logged_in(), 'user_id' => current_user_id()]);
        break;

    default:
        json_response(['error' => 'Unknown action.'], 400);
}