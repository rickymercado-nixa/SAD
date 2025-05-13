<?php
require 'connection.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Student") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

$student_id = $_SESSION['user_id'];
$exam_id = $_GET['exam_id'];

// ✅ Fetch exam questions and student answers
$query = "SELECT questions.question_id, 
       questions.question_text, 
       questions.question_type, 
       COALESCE(student_choice.choice_text, student_answers.answer_text) AS student_answer, 
       student_answers.is_correct, 
       GROUP_CONCAT(choices.choice_text ORDER BY choices.choice_id SEPARATOR ', ') AS correct_answers
FROM questions
LEFT JOIN student_answers 
    ON questions.question_id = student_answers.question_id 
    AND student_answers.student_id = '$student_id'
LEFT JOIN choices AS student_choice
    ON student_answers.answer_text = student_choice.choice_id
LEFT JOIN choices 
    ON questions.question_id = choices.question_id 
    AND choices.is_correct = 1
WHERE questions.exam_id = '$exam_id'
GROUP BY questions.question_id;
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Exam</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <style>
    .correct {
        color: green;
        font-weight: bold;
    }

    .wrong {
        color: red;
        font-weight: bold;
    }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2>Review Exam</h2>
        <a href="student_dashboard.php" class="btn btn-primary mb-3">Back to Dashboard</a>
        <div class="list-group">
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <div class="list-group-item">
                <p><strong>Question:</strong> <?php echo htmlspecialchars($row['question_text']); ?></p>

                <?php if ($row['question_type'] == 'Essay') { ?>
                <p><strong>Your Answer:</strong>
                    <?php echo nl2br(htmlspecialchars($row['student_answer'] ?? 'No answer')); ?></p>
                <?php } else { ?>
                <p><strong>Your Answer:</strong>
                    <span class="<?php echo ($row['is_correct'] == 1) ? 'correct' : 'wrong'; ?>">
                        <?php echo htmlspecialchars($row['student_answer'] ?? 'No answer'); ?>
                    </span>
                </p>
                <p><strong>Correct Answer:</strong> <span class="correct"><?php echo $row['correct_answers']; ?></span>
                </p>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
    </div>
</body>

</html>