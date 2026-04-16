<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/functions.php';

only_ajax();
require_login();
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

switch ($action) {

    case 'list':
        $side   = in_array($_GET['side'] ?? '', ['front','back']) ? $_GET['side'] : 'front';
        $gender = in_array($_GET['gender'] ?? '', ['male','female']) ? $_GET['gender'] : 'male';
        $pdo    = get_db();
        $stmt   = $pdo->prepare(
            "SELECT id, name, slug, svg_id, region, body_side
             FROM muscles
             WHERE body_side = ?
               AND (gender_applicable = 'both' OR gender_applicable = ?)
             ORDER BY region, name"
        );
        $stmt->execute([$side, $gender]);
        json_response(['muscles' => $stmt->fetchAll()]);
        break;

    case 'imbalance':
        $user_id = current_user_id();
        $pdo     = get_db();
        $stmt    = $pdo->prepare(
            "SELECT m.id, m.name, m.slug, m.svg_id,
                    COALESCE(AVG(p.intensity), 0) AS intensity
             FROM muscles m
             LEFT JOIN progress p ON p.muscle_id = m.id AND p.user_id = ?
                AND p.logged_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY m.id, m.name, m.slug, m.svg_id"
        );
        $stmt->execute([$user_id]);
        $data        = $stmt->fetchAll();
        $undertrained = get_imbalance($data);
        json_response(['all' => $data, 'undertrained' => $undertrained]);
        break;

    default:
        json_response(['error' => 'Unknown action.'], 400);
}