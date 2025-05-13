<?php
require 'connection.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Teacher") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT exam_id, exam_name FROM exams WHERE teacher_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$exams = $stmt->get_result();

$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;
$chart_labels = [];
$chart_correct = [];
$chart_incorrect = [];

if ($exam_id) {
    $stmt = $conn->prepare("
        SELECT questions.question_id, questions.question_text,
            SUM(CASE WHEN student_answers.is_correct = 1 THEN 1 ELSE 0 END) AS correct_answers,
            SUM(CASE WHEN student_answers.is_correct = 0 THEN 1 ELSE 0 END) AS incorrect_answers
        FROM questions
        JOIN student_answers ON questions.question_id = student_answers.question_id
        WHERE questions.exam_id = ?
        GROUP BY questions.question_id, questions.question_text
        ORDER BY questions.question_id
    ");
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $analytics_data = $stmt->get_result();

    while ($row = $analytics_data->fetch_assoc()) { 
        $chart_labels[] = $row['question_text'];
        $chart_correct[] = $row['correct_answers'];
        $chart_incorrect[] = $row['incorrect_answers'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scores Per Question Analytics</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fontawesome-free-6.7.2-web/fontawesome-free-6.7.2-web/css/all.min.css">
    <script src="assets/Chart.js-4.4.8/package/dist/chart.umd.js"></script>
    <style>
    body {
        background-color: #f8f9fa;
        color: #333;
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 250px;
        background-color: #198754;
        color: white;
        padding-top: 20px;
        overflow-y: auto;
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
        margin-left: 250px;;
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
        <h2>Scores Per Question Analytics</h2>

        <!-- Exam Selection Dropdown -->
        <form method="GET" class="mb-3">
            <label for="exam_id" class="form-label">Select Exam:</label>
            <select name="exam_id" id="exam_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Choose Exam --</option>
                <?php while ($exam = $exams->fetch_assoc()) { ?>
                    <option value="<?php echo $exam['exam_id']; ?>" 
                        <?php if ($exam_id == $exam['exam_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($exam['exam_name']); ?>
                    </option>
                <?php } ?>
            </select>
        </form>

        <?php if ($exam_id): ?>
            <!-- Chart -->
            <canvas id="questionChart"></canvas>
            <script>
                var ctx = document.getElementById('questionChart').getContext('2d');
                var questionChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($chart_labels); ?>,
                        datasets: [
                            {
                                label: 'Correct Answers',
                                backgroundColor: 'green',
                                data: <?php echo json_encode($chart_correct); ?>
                            },
                            {
                                label: 'Incorrect Answers',
                                backgroundColor: 'red',
                                data: <?php echo json_encode($chart_incorrect); ?>
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            </script>
        <?php endif; ?>
        </main>
        </div>
</div>
    </div>
    <script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
