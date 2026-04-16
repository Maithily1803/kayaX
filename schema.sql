SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS fitmap_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fitmap_db;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    gender ENUM('male','female') NOT NULL DEFAULT 'male',
    goal ENUM('fat_loss','muscle_gain','endurance','flexibility') NOT NULL DEFAULT 'muscle_gain',
    age TINYINT UNSIGNED,
    weight_kg DECIMAL(5,2),
    height_cm DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE muscles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    body_side ENUM('front','back') NOT NULL,
    svg_id VARCHAR(80) NOT NULL,
    gender_applicable ENUM('both','male','female') NOT NULL DEFAULT 'both',
    region VARCHAR(60)
);

CREATE TABLE exercises (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    muscle_id INT UNSIGNED NOT NULL,
    goal_tag ENUM('fat_loss','muscle_gain','endurance','flexibility','all') NOT NULL DEFAULT 'all',
    difficulty ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
    sets_recommended TINYINT UNSIGNED DEFAULT 3,
    reps_recommended TINYINT UNSIGNED DEFAULT 12,
    rest_seconds SMALLINT UNSIGNED DEFAULT 60,
    correct_form TEXT,
    common_mistakes TEXT,
    equipment VARCHAR(120),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (muscle_id) REFERENCES muscles(id) ON DELETE CASCADE
);

CREATE TABLE workouts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(120),
    scheduled_at DATE,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE workout_exercises (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workout_id INT UNSIGNED NOT NULL,
    exercise_id INT UNSIGNED NOT NULL,
    sets_done TINYINT UNSIGNED DEFAULT 0,
    reps_done TINYINT UNSIGNED DEFAULT 0,
    weight_kg DECIMAL(6,2) DEFAULT 0,
    FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
);

CREATE TABLE progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    muscle_id INT UNSIGNED NOT NULL,
    workout_id INT UNSIGNED NOT NULL,
    intensity TINYINT UNSIGNED DEFAULT 50,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (muscle_id) REFERENCES muscles(id) ON DELETE CASCADE,
    FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE
);

CREATE TABLE mistakes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exercise_id INT UNSIGNED NOT NULL,
    description TEXT NOT NULL,
    fix TEXT NOT NULL,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
);

INSERT INTO muscles (name, slug, body_side, svg_id, gender_applicable, region) VALUES
('Chest','chest','front','svg-chest','both','upper'),
('Front Deltoids','front-deltoids','front','svg-front-deltoids','both','upper'),
('Biceps','biceps','front','svg-biceps','both','upper'),
('Forearms','forearms','front','svg-forearms','both','upper'),
('Abs','abs','front','svg-abs','both','core'),
('Obliques','obliques','front','svg-obliques','both','core'),
('Quadriceps','quadriceps','front','svg-quads','both','lower'),
('Adductors','adductors','front','svg-adductors','both','lower'),
('Tibialis','tibialis','front','svg-tibialis','both','lower'),
('Trapezius','trapezius','back','svg-trapezius','both','upper'),
('Rear Deltoids','rear-deltoids','back','svg-rear-deltoids','both','upper'),
('Triceps','triceps','back','svg-triceps','both','upper'),
('Lats','lats','back','svg-lats','both','upper'),
('Lower Back','lower-back','back','svg-lower-back','both','core'),
('Glutes','glutes','back','svg-glutes','both','lower'),
('Hamstrings','hamstrings','back','svg-hamstrings','both','lower'),
('Calves','calves','back','svg-calves','both','lower');

INSERT INTO exercises (name, muscle_id, goal_tag, difficulty, sets_recommended, reps_recommended, rest_seconds, correct_form, common_mistakes, equipment) VALUES
('Barbell Bench Press', 1, 'muscle_gain', 'intermediate', 4, 8, 90, 'Lie flat, retract scapula, lower bar to mid-chest with control, drive through heels.', 'Flared elbows, bouncing bar off chest, not touching chest.', 'Barbell, Bench'),
('Push-Ups', 1, 'fat_loss', 'beginner', 3, 15, 60, 'Straight body line, hands shoulder-width, lower chest to 1 inch from floor.', 'Hips sagging, partial range of motion.', 'Bodyweight'),
('Incline Dumbbell Press', 1, 'muscle_gain', 'intermediate', 3, 10, 75, 'Set bench 30-45 degrees, press dumbbells up and slightly inward.', 'Too steep incline shifts load to delts.', 'Dumbbells, Incline Bench'),
('Overhead Press', 2, 'muscle_gain', 'intermediate', 4, 8, 90, 'Stand tall, press barbell directly overhead, lockout arms at top.', 'Excessive lumbar arch, bar drifting forward.', 'Barbell'),
('Lateral Raises', 2, 'muscle_gain', 'beginner', 3, 15, 45, 'Slight bend in elbow, raise arms to shoulder height, control descent.', 'Swinging, shrugging shoulders, going past parallel.', 'Dumbbells'),
('Barbell Curl', 3, 'muscle_gain', 'beginner', 3, 12, 60, 'Elbows fixed at sides, supinated grip, full range of motion.', 'Swinging torso, partial reps.', 'Barbell'),
('Hammer Curl', 3, 'muscle_gain', 'beginner', 3, 12, 60, 'Neutral grip, same motion as regular curl.', 'Rushing the eccentric.', 'Dumbbells'),
('Plank', 5, 'fat_loss', 'beginner', 3, 1, 45, 'Forearms on floor, straight line from head to heels, breathe steadily.', 'Hips raised, head dropped.', 'Bodyweight'),
('Crunches', 5, 'fat_loss', 'beginner', 3, 20, 30, 'Lift shoulder blades off floor, exhale at top.', 'Pulling neck, using momentum.', 'Bodyweight'),
('Leg Press', 7, 'muscle_gain', 'intermediate', 4, 10, 90, 'Feet shoulder-width, lower until knees near 90 degrees, do not lock out.', 'Knees caving inward, too low foot placement.', 'Leg Press Machine'),
('Barbell Squat', 7, 'muscle_gain', 'intermediate', 4, 8, 120, 'Bar on traps, chest up, squat to parallel, drive knees out.', 'Knees caving, heels rising, forward lean.', 'Barbell, Squat Rack'),
('Deadlift', 14, 'muscle_gain', 'advanced', 4, 5, 150, 'Hinge at hips, flat back, bar close to shins, drive hips forward at top.', 'Rounding lower back, bar drifting away.', 'Barbell'),
('Pull-Ups', 13, 'muscle_gain', 'intermediate', 3, 8, 90, 'Dead hang start, engage lats, chin over bar, full extension at bottom.', 'Kipping, partial range.', 'Pull-Up Bar'),
('Lat Pulldown', 13, 'muscle_gain', 'beginner', 3, 12, 60, 'Wide grip, lean slightly back, pull bar to upper chest.', 'Pulling too far down, excessive lean.', 'Cable Machine'),
('Glute Bridge', 15, 'fat_loss', 'beginner', 3, 15, 45, 'Feet flat, drive hips up, squeeze glutes at top.', 'Hyperextending lower back.', 'Bodyweight'),
('Romanian Deadlift', 16, 'muscle_gain', 'intermediate', 3, 10, 90, 'Slight knee bend, hinge at hips, bar slides down thighs.', 'Rounding back, bending knees too much.', 'Barbell'),
('Calf Raises', 17, 'endurance', 'beginner', 4, 20, 30, 'Full plantarflexion at top, stretch at bottom.', 'Partial range, bouncing.', 'Bodyweight or Machine'),
('Tricep Dips', 12, 'muscle_gain', 'beginner', 3, 12, 60, 'Lean forward slightly for chest, upright for triceps.', 'Flared elbows, not reaching full depth.', 'Dip Bars'),
('Skull Crushers', 12, 'muscle_gain', 'intermediate', 3, 10, 75, 'Elbows fixed, lower bar to forehead, extend fully.', 'Elbows flaring outward.', 'EZ Bar, Bench');

INSERT INTO mistakes (exercise_id, description, fix) VALUES
(1, 'Elbows flare out past 90 degrees', 'Tuck elbows to 45-75 degrees from torso'),
(1, 'Bar bounced off chest', 'Pause 1 second at the bottom under control'),
(11, 'Knees cave inward on ascent', 'Push knees out in line with toes throughout movement'),
(12, 'Lower back rounds at the start', 'Set hips lower, extend thoracic spine, brace core before pulling'),
(13, 'Shrugging instead of pulling with lats', 'Depress scapula first, think elbows to back pockets'),
(9, 'Pulling neck with hands', 'Cross arms on chest or hover hands at temples');

COMMIT;