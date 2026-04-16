<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/functions.php';

only_ajax();
require_login();
header('Content-Type: application/json; charset=utf-8');

$user_id = current_user_id();
$action  = $_GET['action'] ?? $_POST['action'] ?? 'summary';

switch ($action) {

    case 'log':
        $workout_id = (int)($_POST['workout_id'] ?? 0);
        $muscle_id  = (int)($_POST['muscle_id'] ?? 0);
        $intensity  = min(100, max(0, (int)($_POST['intensity'] ?? 50)));

        if (!$workout_id || !$muscle_id) {
            json_response(['error' => 'workout_id and muscle_id required'], 422);
        }

        $pdo  = get_db();
        $chk  = $pdo->prepare('SELECT id FROM workouts WHERE id = ? AND user_id = ?');
        $chk->execute([$workout_id, $user_id]);
        if (!$chk->fetch()) json_response(['error' => 'Workout not found'], 404);

        $ins = $pdo->prepare(
            'INSERT INTO progress (user_id, muscle_id, workout_id, intensity) VALUES (?, ?, ?, ?)'
        );
        $ins->execute([$user_id, $muscle_id, $workout_id, $intensity]);
        json_response(['success' => true]);
        break;

    case 'summary':
        $days = min(365, max(7, (int)($_GET['days'] ?? 30)));
        $pdo  = get_db();
        $stmt = $pdo->prepare(
            "SELECT m.name, m.slug, m.svg_id, ROUND(AVG(p.intensity)) AS avg_intensity,
                    COUNT(p.id) AS session_count, MAX(p.logged_at) AS last_trained
             FROM progress p
             JOIN muscles m ON m.id = p.muscle_id
             WHERE p.user_id = ? AND p.logged_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY m.id, m.name, m.slug, m.svg_id
             ORDER BY avg_intensity DESC"
        );
        $stmt->execute([$user_id, $days]);
        json_response(['summary' => $stmt->fetchAll(), 'days' => $days]);
        break;

    case 'chart':
        $pdo  = get_db();
        $stmt = $pdo->prepare(
            "SELECT DATE(p.logged_at) AS day, COUNT(DISTINCT p.muscle_id) AS muscles_worked,
                    ROUND(AVG(p.intensity)) AS avg_intensity
             FROM progress p
             WHERE p.user_id = ? AND p.logged_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(p.logged_at)
             ORDER BY day ASC"
        );
        $stmt->execute([$user_id]);
        json_response(['chart' => $stmt->fetchAll()]);
        break;

    default:
        json_response(['error' => 'Unknown action.'], 400);
}