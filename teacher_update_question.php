<?php
include 'connection.php';

if (!isset($_GET['question_id'])) {
    echo "<script>alert('Question ID missing'); window.location.href='teacher_questions.php';</script>";
    exit();
}

$question_id = (int)$_GET['question_id'];

$sql = "SELECT * FROM questions WHERE question_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $question_id);
$stmt->execute();
$question = $stmt->get_result()->fetch_assoc();

$sql_choices = "SELECT * FROM choices WHERE question_id = ?";
$stmt = $conn->prepare($sql_choices);
$stmt->bind_param("i", $question_id);
$stmt->execute();
$choices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Question</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .card {
            border-left: 5px solid #198754;
        }
        .form-check-label {
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Update Question</h4>
            </div>
            <div class="card-body">
                <form action="teacher_update_question_process.php" method="POST">
                    <input type="hidden" name="question_id" value="<?= $question_id ?>">

                    <div class="mb-3">
                        <label class="form-label">Question Text</label>
                        <textarea name="question_text" class="form-control" rows="4" required><?= htmlspecialchars($question['question_text']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Marks</label>
                        <input type="number" name="marks" class="form-control" value="<?= $question['marks'] ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Question Type</label>
                        <input type="text" class="form-control" value="<?= $question['question_type'] ?>" disabled>
                    </div>

                    <?php if ($question['question_type'] == "Multiple Choice"): ?>
                        <?php foreach ($choices as $index => $choice): ?>
                            <div class="mb-3">
                                <label class="form-label">Choice <?= chr(65 + $index) ?></label>
                                <input type="text" name="choices[<?= $choice['choice_id'] ?>]" class="form-control" value="<?= htmlspecialchars($choice['choice_text']) ?>" required>
                                
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" name="correct_choices[]" value="<?= $choice['choice_id'] ?>" <?= $choice['is_correct'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">Mark as Correct</label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($question['question_type'] == "Fill in the Blanks"): ?>
                        <label class="form-label">Accepted Answers</label>
                        <?php foreach ($choices as $choice): ?>
                            <input type="text" name="fill_answers[<?= $choice['choice_id'] ?>]" class="form-control my-2" value="<?= htmlspecialchars($choice['choice_text']) ?>" required>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-success mt-4">Update Question</button>
                    <a href="teacher_questions.php" class="btn btn-secondary mt-4 ms-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
