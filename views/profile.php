<?php
require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/functions.php';
require_login();

$uid  = current_user_id();
$pdo  = get_db();
$user = $pdo->prepare('SELECT id, username, email, gender, goal, age, weight_kg, height_cm, created_at FROM users WHERE id = ?');
$user->execute([$uid]);
$user = $user->fetch();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $age       = min(120, max(10, (int)($_POST['age'] ?? 0)));
    $weight    = (float)($_POST['weight_kg'] ?? 0);
    $height    = (float)($_POST['height_cm'] ?? 0);
    $goal      = in_array($_POST['goal'] ?? '', ['fat_loss','muscle_gain','endurance','flexibility']) ? $_POST['goal'] : $user['goal'];
    $gender    = in_array($_POST['gender'] ?? '', ['male','female']) ? $_POST['gender'] : $user['gender'];

    $upd = $pdo->prepare('UPDATE users SET age=?, weight_kg=?, height_cm=?, goal=?, gender=? WHERE id=?');
    $upd->execute([$age, $weight, $height, $goal, $gender, $uid]);
    $msg = 'success';
    $user['age'] = $age; $user['weight_kg'] = $weight;
    $user['height_cm'] = $height; $user['goal'] = $goal; $user['gender'] = $gender;
}

$bmi = '';
if ($user['weight_kg'] && $user['height_cm']) {
    $h   = $user['height_cm'] / 100;
    $bmi = round($user['weight_kg'] / ($h * $h), 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - kayaX</title>
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
<div class="container" style="max-width:720px;">

    <h2 style="font-size:1.4rem;font-weight:700;margin-bottom:1.5rem;">Your Profile</h2>

    <?php if ($msg === 'success'): ?>
    <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);border-radius:8px;padding:0.75rem 1rem;margin-bottom:1rem;color:var(--success);font-size:0.88rem;">
        Profile updated successfully.
    </div>
    <?php endif; ?>

    <div class="grid-4" style="margin-bottom:1.5rem;">
        <div class="stat-card">
            <div class="stat-value" style="font-size:1.3rem;"><?= htmlspecialchars($user['username']) ?></div>
            <div class="stat-label">Username</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="font-size:1.3rem;"><?= $user['weight_kg'] ? $user['weight_kg'].'kg' : '--' ?></div>
            <div class="stat-label">Weight</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="font-size:1.3rem;"><?= $user['height_cm'] ? $user['height_cm'].'cm' : '--' ?></div>
            <div class="stat-label">Height</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="font-size:1.3rem;"><?= $bmi ?: '--' ?></div>
            <div class="stat-label">BMI</div>
        </div>
    </div>

    <div class="card">
        <div class="section-title" style="margin-bottom:1.25rem;">Update Profile</div>
        <form method="POST">
            <input type="hidden" name="update_profile" value="1">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="male"   <?= $user['gender'] === 'male'   ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= $user['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Primary Goal</label>
                    <select name="goal">
                        <option value="muscle_gain"  <?= $user['goal'] === 'muscle_gain'  ? 'selected' : '' ?>>Muscle Gain</option>
                        <option value="fat_loss"     <?= $user['goal'] === 'fat_loss'     ? 'selected' : '' ?>>Fat Loss</option>
                        <option value="endurance"    <?= $user['goal'] === 'endurance'    ? 'selected' : '' ?>>Endurance</option>
                        <option value="flexibility"  <?= $user['goal'] === 'flexibility'  ? 'selected' : '' ?>>Flexibility</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" min="10" max="120" value="<?= (int)$user['age'] ?: '' ?>" placeholder="e.g. 22">
                </div>
                <div class="form-group">
                    <label>Weight (kg)</label>
                    <input type="number" name="weight_kg" step="0.1" min="20" max="300" value="<?= $user['weight_kg'] ?: '' ?>" placeholder="e.g. 75.5">
                </div>
                <div class="form-group">
                    <label>Height (cm)</label>
                    <input type="number" name="height_cm" step="0.1" min="100" max="250" value="<?= $user['height_cm'] ?: '' ?>" placeholder="e.g. 175">
                </div>
                <div class="form-group">
                    <label>Email (read-only)</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity:0.5;cursor:not-allowed;">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:0.5rem;">Save Changes</button>
        </form>
    </div>

    <div class="card" style="margin-top:1.25rem;">
        <div class="section-title">Account Info</div>
        <p style="font-size:0.88rem;color:var(--text-secondary);">
            Member since: <?= date('F j, Y', strtotime($user['created_at'])) ?>
        </p>
        <button class="btn btn-danger btn-sm" data-logout style="margin-top:1rem;">Logout</button>
    </div>

</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>