<?php
require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/functions.php';
require_login();

$uid  = current_user_id();
$pdo  = get_db();

$user = $pdo->prepare('SELECT username, gender, goal, weight_kg, height_cm, age FROM users WHERE id = ?');
$user->execute([$uid]);
$user = $user->fetch();

$workout_count = $pdo->prepare('SELECT COUNT(*) FROM workouts WHERE user_id = ? AND completed_at IS NOT NULL');
$workout_count->execute([$uid]);
$workout_count = (int)$workout_count->fetchColumn();

$muscle_count = $pdo->prepare(
    'SELECT COUNT(DISTINCT muscle_id) FROM progress WHERE user_id = ? AND logged_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
);
$muscle_count->execute([$uid]);
$muscle_count = (int)$muscle_count->fetchColumn();

$streak = $pdo->prepare(
    'SELECT COUNT(DISTINCT DATE(logged_at)) FROM progress WHERE user_id = ? AND logged_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
);
$streak->execute([$uid]);
$streak = (int)$streak->fetchColumn();

$recent = $pdo->prepare(
    'SELECT w.id, w.name, w.scheduled_at, w.completed_at,
            COUNT(we.id) AS exercise_count
     FROM workouts w
     LEFT JOIN workout_exercises we ON we.workout_id = w.id
     WHERE w.user_id = ?
     GROUP BY w.id ORDER BY w.created_at DESC LIMIT 5'
);
$recent->execute([$uid]);
$recent_workouts = $recent->fetchAll();

$goal_label = [
    'muscle_gain' => 'Muscle Gain',
    'fat_loss'    => 'Fat Loss',
    'endurance'   => 'Endurance',
    'flexibility' => 'Flexibility',
][$user['goal']] ?? 'Muscle Gain';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - kayaX</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <span class="navbar-brand">Fit<span>Map</span></span>
    <div class="navbar-nav">
        <a href="dashboard.php" class="nav-link">Dashboard</a>
        <a href="bodymap.php"   class="nav-link">Body Map</a>
        <a href="progress.php"  class="nav-link">Progress</a>
        <a href="profile.php"   class="nav-link">Profile</a>
        <button class="btn btn-outline btn-sm" data-logout style="margin-left:0.5rem;">Logout</button>
    </div>
</nav>

<div class="page-wrapper">
<div class="container">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.75rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h2 style="font-size:1.5rem;font-weight:700;">Hey, <?= htmlspecialchars($user['username']) ?></h2>
            <p style="color:var(--text-secondary);font-size:0.9rem;margin-top:0.2rem;">
                Goal: <span class="badge badge-accent"><?= $goal_label ?></span>
            </p>
        </div>
        <button class="btn btn-primary" id="new-workout-btn">+ New Workout</button>
    </div>

    <div class="grid-4" style="margin-bottom:1.75rem;">
        <div class="stat-card">
            <div class="stat-value"><?= $workout_count ?></div>
            <div class="stat-label">Workouts Done</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $muscle_count ?></div>
            <div class="stat-label">Muscles Trained (30d)</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $streak ?></div>
            <div class="stat-label">Active Days (7d)</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="font-size:1.4rem;"><?= htmlspecialchars($user['goal'] === 'fat_loss' ? 'Cut' : ($user['goal'] === 'muscle_gain' ? 'Bulk' : ucfirst($user['goal']))) ?></div>
            <div class="stat-label">Current Phase</div>
        </div>
    </div>

    <div class="grid-2" style="margin-bottom:1.75rem;">
        <div class="card">
            <div class="section-title">30-Day Progress</div>
            <div style="height:220px;position:relative;">
                <canvas id="progress-chart"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="section-title">Recent Workouts</div>
            <?php if (empty($recent_workouts)): ?>
                <p style="color:var(--text-muted);font-size:0.9rem;">No workouts yet. Start one now.</p>
            <?php else: foreach ($recent_workouts as $w): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:0.65rem 0;border-bottom:1px solid var(--border);">
                    <div>
                        <div style="font-size:0.92rem;font-weight:500;"><?= htmlspecialchars($w['name']) ?></div>
                        <div style="font-size:0.78rem;color:var(--text-muted);">
                            <?= $w['exercise_count'] ?> exercises &bull; <?= $w['scheduled_at'] ?>
                        </div>
                    </div>
                    <?php if ($w['completed_at']): ?>
                        <span class="badge badge-success">Done</span>
                    <?php else: ?>
                        <span class="badge badge-warn">Pending</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="section-title">Quick Access</div>
            <div style="display:flex;flex-direction:column;gap:0.6rem;">
                <a href="bodymap.php" class="btn btn-outline btn-full" style="justify-content:flex-start;">Body Map &amp; Exercise Finder</a>
                <a href="progress.php" class="btn btn-outline btn-full" style="justify-content:flex-start;">Progress &amp; Heatmap</a>
                <a href="profile.php" class="btn btn-outline btn-full" style="justify-content:flex-start;">Update Profile &amp; Goals</a>
            </div>
        </div>

        <div class="card" id="imbalance-card">
            <div class="section-title">Muscle Imbalance Alert</div>
            <div id="imbalance-content" style="color:var(--text-muted);font-size:0.9rem;">Analyzing...</div>
        </div>
    </div>

</div>
</div>

<div id="new-workout-panel" class="insight-panel">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.2rem;">
        <h3 style="font-size:1rem;font-weight:600;">New Workout</h3>
        <button class="btn btn-outline btn-sm" data-close-panel="new-workout-panel">Close</button>
    </div>
    <div class="form-group">
        <label>Workout Name</label>
        <input type="text" id="wo-name" placeholder="e.g. Push Day A">
    </div>
    <div class="form-group">
        <label>Date</label>
        <input type="date" id="wo-date" value="<?= date('Y-m-d') ?>">
    </div>
    <button class="btn btn-primary btn-full" id="create-workout-btn">Create Workout</button>
    <div id="wo-result" style="margin-top:1rem;"></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/heatmap.js"></script>
<script>
$(function () {
    ProgressChart.init('progress-chart');

    ajax('../api/muscles.php', { action: 'imbalance' })
        .done(function (r) {
            var $c = $('#imbalance-content');
            if (!r.undertrained || r.undertrained.length === 0) {
                $c.html('<span style="color:var(--success);">No significant imbalances detected in the last 30 days.</span>');
                return;
            }
            var html = '<p style="color:var(--danger);font-size:0.85rem;margin-bottom:0.5rem;font-weight:500;">Undertrained muscles detected:</p>';
            $.each(r.undertrained, function (i, m) {
                html += '<div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.35rem;">';
                html += '<span style="width:8px;height:8px;border-radius:50%;background:var(--danger);display:inline-block;flex-shrink:0;"></span>';
                html += '<span style="font-size:0.88rem;">' + m.name + '</span>';
                html += '<span style="margin-left:auto;font-size:0.78rem;color:var(--text-muted);">Avg: ' + Math.round(m.intensity) + '%</span>';
                html += '</div>';
            });
            $c.html(html);
        });

    $('#new-workout-btn').on('click', function () { openPanel('new-workout-panel'); });

    $('#create-workout-btn').on('click', function () {
        var name = $('#wo-name').val().trim() || 'Workout ' + new Date().toLocaleDateString('en-IN');
        var date = $('#wo-date').val();
        var $btn = $(this).text('Creating...').prop('disabled', true);

        ajax('../api/workouts.php', { action: 'create', name: name, scheduled_at: date }, 'POST')
            .done(function (r) {
                if (r.success) {
                    toast('Workout created! ID: ' + r.workout_id, 'success');
                    $('#wo-result').html('<span style="color:var(--success);">Workout #' + r.workout_id + ' ready. Head to Body Map to add exercises.</span>');
                    $btn.text('Create Workout').prop('disabled', false);
                }
            })
            .fail(function () {
                toast('Failed to create workout.', 'error');
                $btn.text('Create Workout').prop('disabled', false);
            });
    });
});
</script>
</body>
</html>