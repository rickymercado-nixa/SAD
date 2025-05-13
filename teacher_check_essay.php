<?php
require 'connection.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Teacher") {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION["user_id"];
$selected_exam_id = $_GET['exam_id'] ?? null;

// Fetch all exams created by the teacher
$exam_stmt = $conn->prepare("SELECT exam_id, exam_name FROM exams WHERE teacher_id = ?");
$exam_stmt->bind_param("i", $teacher_id);
$exam_stmt->execute();
$exam_result = $exam_stmt->get_result();

// If exam selected, fetch essay-type answers
$essays = [];
if ($selected_exam_id) {
    $essay_stmt = $conn->prepare("
        SELECT student_answers.students_answer_id, student_answers.answer_text, student_answers.marks_obtained, questions.question_text, questions.marks, users.fname, users.lname, exams.exam_name
        FROM student_answers
        JOIN questions ON student_answers.question_id = questions.question_id
        JOIN exams ON student_answers.exam_id = exams.exam_id
        JOIN users ON student_answers.student_id = users.user_id
        WHERE questions.question_type = 'Essay' AND exams.exam_id = ? AND student_answers.marks_obtained = 0
    ");
    $essay_stmt->bind_param("i", $selected_exam_id);
    $essay_stmt->execute();
    $essays = $essay_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Check Essay Answers</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fontawesome-free-6.7.2-web/fontawesome-free-6.7.2-web/css/all.css">
    <style>
    body {
        background-color: #f8f9fa;
        color: #333;
    }

    .sidebar {
        color: white;
        height: 100vh;
        padding-top: 20px;
    }

    .sidebar a {
        color: white;
        text-decoration: none;
        display: block;
        padding: 10px;
    }

    .sidebar a:hover {
        background-color: rgb(25, 135, 84);
    }

    .main-content {
        padding: 20px;
    }

    .card {
        border-left: 5px solid #2E7D32;
    }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 sidebar d-md-block bg-success">
                <h4 class="text-center">Teacher Panel</h4>
                <a href="teacher_dashboard.php" class="text-white"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
                <a href="teacher_subject.php" class="text-white"><i class="fa-solid fa-book"></i> Subject Class</a>
                <div class="dropdown">
                    <button class="btn text-white dropdown-toggle w-100 text-start" type="button" id="examDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-clipboard-list"></i> Exams
                    </button>
                    <ul class="dropdown-menu bg-success" aria-labelledby="examDropdown">
                        <li><a class="dropdown-item" href="teacher_exams.php"><i class="fa-solid fa-plus"></i> Create Exam</a></li>
                        <li><a class="dropdown-item" href="teacher_viewexams.php"><i class="fa-solid fa-eye"></i> View Exams</a></li>
                    </ul>
                </div>
                <a href="teacher_cheating_report.php"><i class="fa-solid fa-clipboard"></i> Cheating Logs</a>
                <a href="teacher_scores_questions.php"><i class="fa-solid fa-clipboard-question"></i> Scores Questions</a>
                <a href="teacher_exam_result.php"><i class="fa-solid fa-square-poll-vertical"></i> Examination Result</a>
                <a href="teacher_student_analytics.php"><i class="fa-solid fa-chart-simple"></i> Student Analytics</a>
                <a href="teacher_exam_score.php"><i class="fa-solid fa-print"></i> Exam Score Report</a>
                <a href="teacher_check_essay.php"><i class="fa-solid fa-circle-check"></i> Check Essay</a>
                <a href="logout.php"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</a>
        </nav>

        <main class="col-md-9 col-lg-10 main-content">
            <h4 class="mb-4">Check Essay Answers</h4>

            <!-- Exam Selection Form -->
            <form method="GET" class="mb-4">
                <label for="exam_id">Select Exam:</label>
                <select name="exam_id" id="exam_id" class="form-select w-50 d-inline-block" required onchange="this.form.submit()">
                    <option value="">-- Choose Exam --</option>
                    <?php while ($row = $exam_result->fetch_assoc()): ?>
                        <option value="<?= $row['exam_id'] ?>" <?= $row['exam_id'] == $selected_exam_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['exam_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>

            <?php if ($selected_exam_id && $essays->num_rows > 0): ?>
            <form method="POST" action="submit_essay_scores.php">
                <input type="hidden" name="exam_id" value="<?= $selected_exam_id ?>">
                <table class="table table-bordered">
                    <thead>
                        <tr class="text-center">
                            <th>Student</th>
                            <th>Question</th>
                            <th>Answer</th>
                            <th>Max Marks</th>
                            <th>Marks Given</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $essays->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['fname'] . ' ' . $row['lname']) ?></td>
                            <td><?= htmlspecialchars($row['question_text']) ?></td>
                            <td>
    <div style="max-height: 200px; overflow-y: auto; white-space: pre-wrap; background-color: #f1f1f1; padding: 10px; border-radius: 5px;">
        <?= nl2br(htmlspecialchars($row['answer_text'])) ?>
    </div>
</td>
                            <td><?= $row['marks'] ?></td>
                            <td>
                                <input type="number" name="marks[<?= $row['students_answer_id'] ?>]" min="0" max="<?= $row['marks'] ?>" class="form-control" required>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <button type="submit" class="btn btn-primary">Submit Scores</button>
            </form>
            <?php elseif ($selected_exam_id): ?>
                <div class="alert alert-info">No ungraded essay answers found for this exam.</div>
            <?php endif; ?>
        </main>
    </div>
</div>
<script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
