<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'connection.php';

// Access control
if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Teacher") {
    echo '<script>alert("Access denied!"); window.location.href="login.php";</script>';
    exit();
}

if (!isset($_GET['exam_id']) || !isset($_GET['question_part'])) {
    echo '<script>alert("Missing parameters!"); window.location.href="teacher_dashboard.php";</script>';
    exit();
}

$exam_id = (int)$_GET['exam_id'];
$question_part = $_GET['question_part'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $duration = (int)$_POST['timer'];

    $stmt = $conn->prepare("INSERT INTO exam_part_timers (exam_id, question_part, timer)
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE timer = ?");
    $stmt->bind_param("isii", $exam_id, $question_part, $duration, $duration);

    if ($stmt->execute()) {
        echo '<script>alert("Timer updated successfully!"); window.location.href = "teacher_questions.php?exam_id=' . $exam_id . '";</script>';
        exit();
    } else {
        echo '<script>alert("Failed to update timer.");</script>';
    }
}

$stmt = $conn->prepare("SELECT timer FROM exam_part_timers WHERE exam_id = ? AND question_part = ?");
$stmt->bind_param("is", $exam_id, $question_part);
$stmt->execute();
$result = $stmt->get_result();

$existing_duration = '';
if ($row = $result->fetch_assoc()) {
    $existing_duration = $row['timer'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set Timer</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow p-4">
            <h3 class="text-success mb-4">Set Timer for <span class="text-primary"><?= htmlspecialchars($question_part) ?></span></h3>
            <form method="POST">
            <div class="mb-3">
    <label for="timer" class="form-label">Select Timer</label>
    <select name="timer" id="timer" class="form-control" required>
        <option value="">Select Timer</option>
        <?php
        $timers = [
            5 => '5 minutes',
            10 => '10 minutes',
            15 => '15 minutes',
            30 => '30 minutes',
            45 => '45 minutes',
            60 => '1 hour',
            75 => '1 hour & 15 minutes',
            90 => '1 hour & 30 minutes',
            105 => '1 hour & 45 minutes',
            120 => '2 hours'
        ];
        foreach ($timers as $value => $label) {
            $selected = ($existing_duration == $value) ? 'selected' : '';
            echo "<option value=\"$value\" $selected>$label</option>";
        }
        ?>
    </select>
</div>

                <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
                <input type="hidden" name="question_part" value="<?= htmlspecialchars($question_part) ?>">
                <button type="submit" class="btn btn-primary">Save Timer</button>
                <a href="teacher_questions.php?exam_id=<?= $exam_id ?>" class="btn btn-secondary ms-2">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>
