<?php
require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/functions.php';
require_login();

$uid  = current_user_id();
$pdo  = get_db();
$user = $pdo->prepare('SELECT gender, goal FROM users WHERE id = ?');
$user->execute([$uid]);
$user = $user->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Body Map - kayaX</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/body-map.css">
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

    <div style="margin-bottom:1.5rem;">
        <h2 style="font-size:1.4rem;font-weight:700;">Interactive Body Map</h2>
        <p style="color:var(--text-secondary);font-size:0.88rem;margin-top:0.2rem;">Click a muscle group to explore exercises and form guides</p>
    </div>

    <div style="display:grid;grid-template-columns:300px 1fr;gap:1.5rem;align-items:start;">

        <div>
            <div class="toggle-view-btn" style="margin-bottom:0.75rem;">
                <button class="active" id="btn-front">Front</button>
                <button id="btn-back">Back</button>
            </div>

            <div class="gender-toggle">
                <button class="<?= $user['gender'] === 'male' ? 'active' : '' ?>" id="btn-male">Male</button>
                <button class="<?= $user['gender'] === 'female' ? 'active' : '' ?>" id="btn-female">Female</button>
            </div>

            <div class="body-map-wrap card" style="padding:1rem;">
                <div id="body-svg-container">
                    <?php
                    $svg_front = file_get_contents(__DIR__ . '/../assets/svg/body-front.svg');
                    $svg_back  = file_get_contents(__DIR__ . '/../assets/svg/body-back.svg');
                    echo '<div id="svg-front">' . ($svg_front ?: '<p style="color:var(--text-muted);font-size:0.8rem;padding:1rem;">body-front.svg not found. Download from svgrepo.com and place in assets/svg/</p>') . '</div>';
                    echo '<div id="svg-back" style="display:none;">' . ($svg_back ?: '<p style="color:var(--text-muted);font-size:0.8rem;padding:1rem;">body-back.svg not found.</p>') . '</div>';
                    ?>
                </div>
                <div id="heatmap-legend"></div>
            </div>

            <div style="margin-top:0.75rem;">
                <label style="font-size:0.8rem;color:var(--text-secondary);margin-bottom:0.3rem;">Filter goal</label>
                <select id="goal-filter">
                    <option value="all">All Goals</option>
                    <option value="muscle_gain" <?= $user['goal'] === 'muscle_gain' ? 'selected' : '' ?>>Muscle Gain</option>
                    <option value="fat_loss"    <?= $user['goal'] === 'fat_loss'    ? 'selected' : '' ?>>Fat Loss</option>
                    <option value="endurance"   <?= $user['goal'] === 'endurance'   ? 'selected' : '' ?>>Endurance</option>
                    <option value="flexibility" <?= $user['goal'] === 'flexibility' ? 'selected' : '' ?>>Flexibility</option>
                </select>
            </div>
        </div>

        <div>
            <div id="muscle-placeholder" class="card" style="text-align:center;padding:3rem 1.5rem;">
                <div style="font-size:2.5rem;margin-bottom:0.75rem;">
                    <lottie-player src="../assets/lottie/workout.json" background="transparent" speed="1" style="width:80px;height:80px;margin:0 auto;" loop autoplay></lottie-player>
                </div>
                <p style="color:var(--text-secondary);">Select a muscle group on the body map to see exercises</p>
            </div>

            <div id="exercise-panel" style="display:none;" class="fade-in">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:0.5rem;">
                    <h3 id="muscle-title" style="font-size:1.1rem;font-weight:600;"></h3>
                    <div style="display:flex;gap:0.5rem;">
                        <button class="btn btn-outline btn-sm" id="log-muscle-btn">Log Session</button>
                    </div>
                </div>
                <div id="exercise-list" class="grid-2" style="gap:1rem;"></div>
            </div>
        </div>

    </div>

</div>
</div>

<div id="exercise-detail-panel" class="insight-panel">
    <button class="btn btn-outline btn-sm" data-close-panel="exercise-detail-panel" style="margin-bottom:1rem;">Close</button>
    <div id="exercise-detail-content"></div>
</div>

<div id="log-panel" class="insight-panel">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
        <h3 style="font-size:1rem;font-weight:600;">Log Muscle Session</h3>
        <button class="btn btn-outline btn-sm" data-close-panel="log-panel">Close</button>
    </div>
    <div class="form-group">
        <label>Workout ID</label>
        <input type="number" id="log-workout-id" placeholder="Enter workout ID from dashboard">
    </div>
    <div class="form-group">
        <label>Intensity (0-100)</label>
        <input type="range" id="log-intensity" min="0" max="100" value="60" style="padding:0;">
        <div style="display:flex;justify-content:space-between;font-size:0.78rem;color:var(--text-muted);margin-top:0.2rem;">
            <span>Easy</span><span id="intensity-val">60</span><span>Max</span>
        </div>
    </div>
    <button class="btn btn-primary btn-full" id="submit-log-btn">Log Session</button>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/body-map.js"></script>
<script>
$(function () {
    var currentMuscleId = null;

    BodyMap.init({ gender: '<?= $user['gender'] ?>', mode: 'select' });

    $('#btn-front').on('click', function () {
        $('#svg-front').show();
        $('#svg-back').hide();
    });
    $('#btn-back').on('click', function () {
        $('#svg-front').hide();
        $('#svg-back').show();
    });

    $(document).on('muscle:selected', function (e, id, name) {
        currentMuscleId = id;
        loadExercises(id, name);
    });

    function loadExercises(id, name) {
        $('#muscle-placeholder').hide();
        $('#exercise-panel').show().find('#muscle-title').text(name);
        $('#exercise-list').html('<div class="loader"></div>');

        var goal = $('#goal-filter').val();
        ajax('../api/exercises.php', { action: 'by_muscle', muscle_id: id, goal: goal })
            .done(function (r) {
                var exercises = r.exercises || [];
                if (!exercises.length) {
                    $('#exercise-list').html('<p style="color:var(--text-muted);font-size:0.9rem;">No exercises found for this filter.</p>');
                    return;
                }
                var html = '';
                var diff_colors = { beginner: 'success', intermediate: 'warn', advanced: 'danger' };
                $.each(exercises, function (i, ex) {
                    html += '<div class="exercise-card" data-ex-id="' + ex.id + '">';
                    html += '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem;margin-bottom:0.5rem;">';
                    html += '<span style="font-size:0.92rem;font-weight:600;">' + ex.name + '</span>';
                    html += '<span class="badge badge-' + (diff_colors[ex.difficulty] || 'accent') + '">' + ex.difficulty + '</span>';
                    html += '</div>';
                    html += '<div style="font-size:0.8rem;color:var(--text-secondary);margin-bottom:0.5rem;">';
                    html += ex.sets_recommended + ' sets &times; ' + ex.reps_recommended + ' reps';
                    html += ' &bull; Rest: ' + ex.rest_seconds + 's';
                    html += '</div>';
                    if (ex.equipment) {
                        html += '<div style="font-size:0.78rem;color:var(--text-muted);">' + ex.equipment + '</div>';
                    }
                    html += '</div>';
                });
                $('#exercise-list').html(html);
            })
            .fail(function () { toast('Failed to load exercises.', 'error'); });
    }

    $(document).on('click', '.exercise-card', function () {
        var id = $(this).data('ex-id');
        openPanel('exercise-detail-panel');
        $('#exercise-detail-content').html('<div class="loader"></div>');

        ajax('../api/exercises.php', { action: 'detail', id: id })
            .done(function (r) {
                var ex = r.exercise;
                var html  = '<h3 style="font-size:1.1rem;font-weight:700;margin-bottom:0.3rem;">' + ex.name + '</h3>';
                html += '<p style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:1rem;">' + ex.muscle_name + '</p>';
                html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem;margin-bottom:1.2rem;">';
                html += '<div class="stat-card"><div class="stat-value" style="font-size:1.4rem;">' + ex.sets_recommended + '</div><div class="stat-label">Sets</div></div>';
                html += '<div class="stat-card"><div class="stat-value" style="font-size:1.4rem;">' + ex.reps_recommended + '</div><div class="stat-label">Reps</div></div>';
                html += '<div class="stat-card"><div class="stat-value" style="font-size:1.4rem;">' + ex.rest_seconds + 's</div><div class="stat-label">Rest</div></div>';
                html += '</div>';

                html += '<div class="section-title">Correct Form</div>';
                html += '<div style="background:rgba(34,197,94,0.07);border:1px solid rgba(34,197,94,0.2);border-radius:8px;padding:0.85rem;font-size:0.87rem;margin-bottom:1rem;line-height:1.65;">' + ex.correct_form + '</div>';

                if (ex.mistakes && ex.mistakes.length) {
                    html += '<div class="section-title">Common Mistakes &amp; Fixes</div>';
                    $.each(ex.mistakes, function (i, m) {
                        html += '<div style="background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.18);border-radius:8px;padding:0.75rem;margin-bottom:0.5rem;font-size:0.85rem;">';
                        html += '<strong style="color:var(--danger);display:block;margin-bottom:0.25rem;">Mistake: ' + m.description + '</strong>';
                        html += '<span style="color:var(--success);">Fix: ' + m.fix + '</span>';
                        html += '</div>';
                    });
                }

                if (ex.equipment) {
                    html += '<div style="margin-top:0.75rem;font-size:0.82rem;color:var(--text-muted);">Equipment: ' + ex.equipment + '</div>';
                }

                $('#exercise-detail-content').html(html);
            })
            .fail(function () { toast('Could not load exercise detail.', 'error'); });
    });

    $('#goal-filter').on('change', function () {
        if (currentMuscleId) {
            loadExercises(currentMuscleId, $('#muscle-title').text());
        }
    });

    $('#log-muscle-btn').on('click', function () {
        if (!currentMuscleId) { toast('Select a muscle first.', 'warn'); return; }
        openPanel('log-panel');
    });

    $('#log-intensity').on('input', function () {
        $('#intensity-val').text($(this).val());
    });

    $('#submit-log-btn').on('click', function () {
        var workout_id = $('#log-workout-id').val();
        var intensity  = $('#log-intensity').val();

        if (!workout_id) { toast('Enter a workout ID.', 'warn'); return; }
        var $btn = $(this).text('Logging...').prop('disabled', true);

        ajax('../api/progress.php', {
            action:     'log',
            workout_id: workout_id,
            muscle_id:  currentMuscleId,
            intensity:  intensity,
        }, 'POST')
        .done(function (r) {
            if (r.success) {
                toast('Session logged!', 'success');
                closePanel('log-panel');
            }
            $btn.text('Log Session').prop('disabled', false);
        })
        .fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Log failed.';
            toast(msg, 'error');
            $btn.text('Log Session').prop('disabled', false);
        });
    });
});
</script>
</body>
</html>