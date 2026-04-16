<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/functions.php';

only_ajax();
require_login();
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'by_muscle';

switch ($action) {

    case 'by_muscle':
        $muscle_id = (int)($_GET['muscle_id'] ?? 0);
        $goal      = $_GET['goal'] ?? 'all';
        $valid_goals = ['fat_loss','muscle_gain','endurance','flexibility','all'];
        if (!in_array($goal, $valid_goals)) $goal = 'all';

        if (!$muscle_id) json_response(['error' => 'muscle_id required'], 422);

        $pdo  = get_db();
        $sql  = "SELECT e.id, e.name, e.difficulty, e.sets_recommended, e.reps_recommended,
                        e.rest_seconds, e.correct_form, e.common_mistakes, e.equipment, e.goal_tag,
                        m.name AS muscle_name
                 FROM exercises e
                 JOIN muscles m ON m.id = e.muscle_id
                 WHERE e.muscle_id = ?";
        $params = [$muscle_id];

        if ($goal !== 'all') {
            $sql .= " AND (e.goal_tag = ? OR e.goal_tag = 'all')";
            $params[] = $goal;
        }
        $sql .= ' ORDER BY e.difficulty, e.name';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $exercises = $stmt->fetchAll();

        foreach ($exercises as &$ex) {
            $ms = $pdo->prepare('SELECT description, fix FROM mistakes WHERE exercise_id = ?');
            $ms->execute([$ex['id']]);
            $ex['mistakes'] = $ms->fetchAll();
        }
        unset($ex);

        json_response(['exercises' => $exercises]);
        break;

    case 'detail':
        $id   = (int)($_GET['id'] ?? 0);
        if (!$id) json_response(['error' => 'id required'], 422);
        $pdo  = get_db();
        $stmt = $pdo->prepare(
            "SELECT e.*, m.name AS muscle_name FROM exercises e JOIN muscles m ON m.id = e.muscle_id WHERE e.id = ?"
        );
        $stmt->execute([$id]);
        $ex = $stmt->fetch();
        if (!$ex) json_response(['error' => 'Not found'], 404);
        $ms = $pdo->prepare('SELECT description, fix FROM mistakes WHERE exercise_id = ?');
        $ms->execute([$id]);
        $ex['mistakes'] = $ms->fetchAll();
        json_response(['exercise' => $ex]);
        break;

    default:
        json_response(['error' => 'Unknown action.'], 400);
}