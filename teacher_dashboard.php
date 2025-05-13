<?php
require 'connection.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Teacher") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT COUNT(*) AS total_subjects FROM subjects WHERE teacher_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_subjects = $stmt->get_result()->fetch_assoc()['total_subjects'];

// Fetch total exams
$stmt = $conn->prepare("SELECT COUNT(*) AS total_exams FROM exams WHERE teacher_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_exams = $stmt->get_result()->fetch_assoc()['total_exams'];

// Fetch total students (students enrolled in the teacher's subjects)
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT enrollments.student_id) AS total_students 
    FROM enrollments 
    JOIN subjects ON enrollments.sub_id = subjects.sub_id 
    WHERE subjects.teacher_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_students = $stmt->get_result()->fetch_assoc()['total_students'];

// Fetch total cheating logs (cheating incidents in the teacher's exams)
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total_cheating_logs 
    FROM cheating_logs 
    JOIN exams ON cheating_logs.exam_id = exams.exam_id 
    WHERE exams.teacher_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_cheating_logs = $stmt->get_result()->fetch_assoc()['total_cheating_logs'];

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
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
                <h2 class="mb-4">Dashboard Overview</h2>

                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="card p-3">
                            <h5>Subjects</h5>
                            <p>Total: <?php echo $total_subjects; ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3">
                            <h5>Exams</h5>
                            <p>Total: <?php echo $total_exams; ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3">
                            <h5>Students</h5>
                            <p>Total: <?php echo $total_students; ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3">
                            <h5>Cheating Logs</h5>
                            <p>Total: <?php echo $total_cheating_logs; ?></p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
