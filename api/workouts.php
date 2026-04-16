<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/functions.php';

only_ajax();
require_login();
header('Content-Type: application/json; charset=utf-8');

$user_id = current_user_id();
$action  = $_GET['action'] ?? $_POST['action'] ?? 'list';

switch ($action) {

    case 'list':
        $pdo  = get_db();
        $stmt = $pdo->prepare(
            "SELECT w.id, w.name, w.scheduled_at, w.completed_at, w.created_at,
                    COUNT(we.id) AS exercise_count
             FROM workouts w
             LEFT JOIN workout_exercises we ON we.workout_id = w.id
             WHERE w.user_id = ?
             GROUP BY w.id
             ORDER BY w.created_at DESC
             LIMIT 20"
        );
        $stmt->execute([$user_id]);
        json_response(['workouts' => $stmt->fetchAll()]);
        break;

    case 'create':
        $name = sanitize($_POST['name'] ?? 'Workout ' . date('M d'));
        $date = $_POST['scheduled_at'] ?? date('Y-m-d');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $pdo = get_db();
        $ins = $pdo->prepare('INSERT INTO workouts (user_id, name, scheduled_at) VALUES (?, ?, ?)');
        $ins->execute([$user_id, $name, $date]);
        json_response(['success' => true, 'workout_id' => (int)$pdo->lastInsertId()]);
        break;

    case 'add_exercise':
        $workout_id  = (int)($_POST['workout_id'] ?? 0);
        $exercise_id = (int)($_POST['exercise_id'] ?? 0);
        $sets        = (int)($_POST['sets_done'] ?? 0);
        $reps        = (int)($_POST['reps_done'] ?? 0);
        $weight      = (float)($_POST['weight_kg'] ?? 0);

        if (!$workout_id || !$exercise_id) {
            json_response(['error' => 'workout_id and exercise_id required'], 422);
        }

        $pdo = get_db();
        $chk = $pdo->prepare('SELECT id FROM workouts WHERE id = ? AND user_id = ?');
        $chk->execute([$workout_id, $user_id]);
        if (!$chk->fetch()) json_response(['error' => 'Workout not found'], 404);

        $ins = $pdo->prepare(
            'INSERT INTO workout_exercises (workout_id, exercise_id, sets_done, reps_done, weight_kg) VALUES (?, ?, ?, ?, ?)'
        );
        $ins->execute([$workout_id, $exercise_id, $sets, $reps, $weight]);
        json_response(['success' => true]);
        break;

    case 'complete':
        $workout_id = (int)($_POST['workout_id'] ?? 0);
        if (!$workout_id) json_response(['error' => 'workout_id required'], 422);

        $pdo = get_db();
        $upd = $pdo->prepare('UPDATE workouts SET completed_at = NOW() WHERE id = ? AND user_id = ?');
        $upd->execute([$workout_id, $user_id]);
        json_response(['success' => true]);
        break;

    default:
        json_response(['error' => 'Unknown action.'], 400);
}