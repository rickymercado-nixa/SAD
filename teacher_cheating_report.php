<?php
require 'connection.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Teacher") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

$exam_query = "SELECT exam_id, exam_name FROM exams WHERE teacher_id = ?";
$stmt = $conn->prepare($exam_query);
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$exam_result = $stmt->get_result();

$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;
$exam_name = "N/A";
$cheating_logs = [];

if ($exam_id) {
    $stmt = $conn->prepare("SELECT exam_name FROM exams WHERE exam_id = ?");
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $exam_data = $stmt->get_result()->fetch_assoc();
    if ($exam_data) {
        $exam_name = $exam_data['exam_name'];
    }

    $stmt = $conn->prepare("
        SELECT cheating_logs.student_id, users.fname, users.lname, cheating_logs.event_type, cheating_logs.timestamp 
        FROM cheating_logs 
        JOIN users ON cheating_logs.student_id = users.user_id 
        WHERE cheating_logs.exam_id = ?
    ");
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $cheating_logs = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cheating Reports</title>
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
        <h1 class="mb-4">Cheating Reports</h1>

        <!-- Exam Selection Dropdown -->
        <form method="GET" class="mb-3">
            <label for="exam_id" class="form-label">Select an Exam:</label>
            <select name="exam_id" id="exam_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Choose Exam --</option>
                <?php while ($exam = $exam_result->fetch_assoc()) { ?>
                    <option value="<?php echo $exam['exam_id']; ?>" 
                        <?php if ($exam_id == $exam['exam_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($exam['exam_name']); ?>
                    </option>
                <?php } ?>
            </select>
        </form>

        <?php if ($exam_id): ?>
            <h3>Cheating Logs for Exam: <strong><?php echo htmlspecialchars($exam_name); ?></strong></h3>
            <table class="table table-striped table-bordered text-center">
                <thead class="table-dark">
                    <tr>
                        <th>Student Name</th>
                        <th>Event</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($cheating_logs->num_rows > 0) { ?>
                        <?php while ($log = $cheating_logs->fetch_assoc()) { ?>
                            <tr>
                            <td><?php echo htmlspecialchars($log['fname']) . ' ' . htmlspecialchars($log['lname']); ?></td>
                                <td><?php echo htmlspecialchars($log['event_type']); ?></td>
                                <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr><td colspan="4" class="text-center">No cheating logs found for this exam.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php endif; ?>
        </main>
        </div>
    </div>
    </div>
    <script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
