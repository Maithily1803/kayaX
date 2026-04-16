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
<title>Progress - FitMap</title>
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

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h2 style="font-size:1.4rem;font-weight:700;">Progress &amp; Heatmap</h2>
            <p style="color:var(--text-secondary);font-size:0.88rem;margin-top:0.2rem;">Track muscle usage intensity over time</p>
        </div>
        <div style="display:flex;gap:0.5rem;align-items:center;">
            <label style="font-size:0.82rem;color:var(--text-secondary);">Range:</label>
            <select id="days-filter" style="width:auto;">
                <option value="7">7 days</option>
                <option value="30" selected>30 days</option>
                <option value="90">90 days</option>
            </select>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:280px 1fr;gap:1.5rem;align-items:start;margin-bottom:1.5rem;">

        <div class="card">
            <div class="section-title">Body Heatmap</div>
            <div class="toggle-view-btn" style="margin-bottom:0.75rem;">
                <button class="active" id="btn-front">Front</button>
                <button id="btn-back">Back</button>
            </div>
            <div id="body-svg-container">
                <?php
                $svg_front = file_get_contents(__DIR__ . '/../assets/svg/body-front.svg');
                $svg_back  = file_get_contents(__DIR__ . '/../assets/svg/body-back.svg');
                echo '<div id="svg-front">' . ($svg_front ?: '<p style="color:var(--text-muted);font-size:0.8rem;">SVG not found.</p>') . '</div>';
                echo '<div id="svg-back" style="display:none;">' . ($svg_back ?: '') . '</div>';
                ?>
            </div>
            <div id="heatmap-legend" style="margin-top:0.5rem;"></div>
        </div>

        <div>
            <div class="card" style="margin-bottom:1.25rem;">
                <div class="section-title">Training Volume (30 days)</div>
                <div style="height:200px;position:relative;">
                    <canvas id="progress-chart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="section-title">Muscle Activity Summary</div>
                <div id="muscle-summary-table" style="overflow-x:auto;">
                    <div class="loader"></div>
                </div>
            </div>
        </div>

    </div>

    <div class="card">
        <div class="section-title">Imbalance Analysis</div>
        <div id="imbalance-analysis"><div class="loader"></div></div>
    </div>

</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/body-map.js"></script>
<script src="../assets/js/heatmap.js"></script>
<script>
$(function () {
    BodyMap.init({ gender: '<?= $user['gender'] ?>', mode: 'heatmap' });
    ProgressChart.init('progress-chart');

    $('#btn-front').on('click', function () {
        $('#svg-front').show(); $('#svg-back').hide();
        $(this).addClass('active'); $('#btn-back').removeClass('active');
        BodyMap.switchSide('front');
    });
    $('#btn-back').on('click', function () {
        $('#svg-front').hide(); $('#svg-back').show();
        $(this).addClass('active'); $('#btn-front').removeClass('active');
        BodyMap.switchSide('back');
    });

    function loadSummary(days) {
        ajax('../api/progress.php', { action: 'summary', days: days })
            .done(function (r) {
                var rows = r.summary || [];
                if (!rows.length) {
                    $('#muscle-summary-table').html('<p style="color:var(--text-muted);font-size:0.9rem;">No data yet. Log some workouts first.</p>');
                    return;
                }
                var html = '<table style="width:100%;border-collapse:collapse;font-size:0.87rem;">';
                html += '<thead><tr style="border-bottom:1px solid var(--border);">';
                html += '<th style="text-align:left;padding:0.5rem 0.75rem;color:var(--text-secondary);font-weight:500;">Muscle</th>';
                html += '<th style="text-align:center;padding:0.5rem 0.75rem;color:var(--text-secondary);font-weight:500;">Avg Intensity</th>';
                html += '<th style="text-align:center;padding:0.5rem 0.75rem;color:var(--text-secondary);font-weight:500;">Sessions</th>';
                html += '<th style="text-align:center;padding:0.5rem 0.75rem;color:var(--text-secondary);font-weight:500;">Last Trained</th>';
                html += '</tr></thead><tbody>';
                $.each(rows, function (i, m) {
                    var heat = parseInt(m.avg_intensity);
                    var color = heat === 0 ? 'var(--text-muted)' : heat < 30 ? '#378add' : heat < 55 ? '#1d9e75' : heat < 75 ? '#ef9f27' : '#e24b4a';
                    html += '<tr style="border-bottom:1px solid var(--border);">';
                    html += '<td style="padding:0.55rem 0.75rem;font-weight:500;">' + m.name + '</td>';
                    html += '<td style="padding:0.55rem 0.75rem;text-align:center;">';
                    html += '<span style="color:' + color + ';font-weight:600;">' + heat + '%</span>';
                    html += ' <div style="height:4px;background:var(--bg-secondary);border-radius:4px;margin-top:3px;">';
                    html += '<div style="height:4px;width:' + heat + '%;background:' + color + ';border-radius:4px;transition:width 0.5s;"></div>';
                    html += '</div></td>';
                    html += '<td style="padding:0.55rem 0.75rem;text-align:center;">' + m.session_count + '</td>';
                    html += '<td style="padding:0.55rem 0.75rem;text-align:center;color:var(--text-muted);">' + (m.last_trained ? m.last_trained.split('T')[0] : '-') + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
                $('#muscle-summary-table').html(html);
            });

        ajax('../api/muscles.php', { action: 'imbalance' })
            .done(function (r) {
                var $el = $('#imbalance-analysis');
                if (!r.undertrained || !r.undertrained.length) {
                    $el.html('<p style="color:var(--success);font-size:0.9rem;">All muscle groups are reasonably balanced over the past 30 days.</p>');
                    return;
                }
                var html = '<p style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:0.75rem;">These muscles are trained significantly less than your average. Consider adding targeted work.</p>';
                html += '<div style="display:flex;flex-wrap:wrap;gap:0.5rem;">';
                $.each(r.undertrained, function (i, m) {
                    html += '<div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);border-radius:8px;padding:0.5rem 0.9rem;">';
                    html += '<span style="font-size:0.88rem;color:var(--danger);font-weight:500;">' + m.name + '</span>';
                    html += '<span style="font-size:0.78rem;color:var(--text-muted);margin-left:0.5rem;">~' + Math.round(m.intensity) + '% avg</span>';
                    html += '</div>';
                });
                html += '</div>';
                $el.html(html);
            });

        Heatmap.load(days);
    }

    loadSummary(30);

    $('#days-filter').on('change', function () {
        loadSummary(parseInt($(this).val()));
    });
});
</script>
</body>
</html>