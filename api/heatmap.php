<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/functions.php';

only_ajax();
require_login();
header('Content-Type: application/json; charset=utf-8');

$user_id = current_user_id();
$days    = min(365, max(7, (int)($_GET['days'] ?? 30)));

$pdo  = get_db();
$stmt = $pdo->prepare(
    "SELECT m.svg_id, m.name, m.slug, m.body_side,
            ROUND(AVG(p.intensity)) AS heat,
            COUNT(p.id) AS hits
     FROM muscles m
     LEFT JOIN progress p ON p.muscle_id = m.id
         AND p.user_id = ?
         AND p.logged_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
     GROUP BY m.id, m.svg_id, m.name, m.slug, m.body_side"
);
$stmt->execute([$user_id, $days]);
$rows = $stmt->fetchAll();

$heatmap = [];
foreach ($rows as $r) {
    $heat = (int)$r['heat'];
    if ($heat === 0) {
        $color = '#1a1a2e';
    } elseif ($heat < 30) {
        $color = '#0d4f8c';
    } elseif ($heat < 55) {
        $color = '#1d9e75';
    } elseif ($heat < 75) {
        $color = '#ef9f27';
    } else {
        $color = '#e24b4a';
    }
    $heatmap[] = [
        'svg_id'    => $r['svg_id'],
        'name'      => $r['name'],
        'slug'      => $r['slug'],
        'body_side' => $r['body_side'],
        'heat'      => $heat,
        'hits'      => (int)$r['hits'],
        'color'     => $color,
    ];
}

json_response(['heatmap' => $heatmap, 'days' => $days]);