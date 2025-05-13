<?php
include 'connection.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Teacher") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

$teacher_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create'])) {
    $exam_name = htmlspecialchars(trim($_POST['exam_name']));
    $sub_id = filter_input(INPUT_POST, 'sub_id', FILTER_VALIDATE_INT);
    $total_marks = filter_input(INPUT_POST, 'total_marks', FILTER_VALIDATE_INT);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $status = $_POST['status'];


    if (strtotime($start_time) >= strtotime($end_time)) {
        echo "<script>alert('End time must be after start time!');</script>";
    } else {
        $sql = "INSERT INTO exams (exam_name, sub_id, teacher_id, total_marks, start_time, end_time, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siiisss", $exam_name, $sub_id, $teacher_id, $total_marks, $start_time, $end_time, $status);
        if ($stmt->execute()) {
            echo "<script>alert('Exam Created Successfully!'); window.location.href='teacher_viewexams.php';</script>";
        } else {
            error_log("Database Error: " . $stmt->error);
            echo "<script>alert('Error creating exam. Try again!');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fontawesome-free-6.7.2-web/fontawesome-free-6.7.2-web/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            color: #333;
        }
        .sidebar {
            background-color: #2E7D32;
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
            padding: 20px;
        }
        .form-label {
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 sidebar d-md-block bg-success text-white p-3">
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
            <h2 class="text-success">Create Exam</h2>

            <div class="card shadow">
                <form method="POST" class="row g-3">
                    <input type="hidden" name="status" value="Upcoming">
                    
                <div class="col-12">
    <label class="form-label">Exam Name</label>
    <input type="text" class="form-control" name="exam_name" placeholder="Enter exam name..." required>
</div>

                    <div class="col-md-6">
                        <label class="form-label">Subject</label>
                        <select name="sub_id" class="form-control" required>
                            <option value="">Select Subject</option>
                            <?php
                            $sql = "SELECT * FROM subjects WHERE teacher_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $teacher_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='{$row['sub_id']}'>{$row['subject_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Total Marks</label>
                        <input type="number" name="total_marks" class="form-control" placeholder="Total Marks" required min="1">
                    </div>
                    <!-- 
<div class="col-md-6">
    <label class="form-label">Status</label>
    <select name="status" class="form-control" required>
        <option value="Upcoming">Upcoming</option>
    </select>
</div>
-->
                    <div class="col-md-6">
                        <label class="form-label">Start Time</label>
                        <input type="datetime-local" name="start_time" id="start_time" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">End Time</label>
                        <input type="datetime-local" name="end_time" id="end_time" class="form-control" required>
                    </div>

                    <div class="col-12 text-center">
                        <button type="submit" name="create" class="btn btn-success btn-lg">Create Exam</button>
                    </div>

                </form>
            </div>
        </main>
    </div>
</div>

<script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const now = new Date();
    const formatted = now.toISOString().slice(0, 16); // yyyy-MM-ddTHH:mm

    document.getElementById('start_time').min = formatted;
    document.getElementById('end_time').min = formatted;
});
</script>
</body>
</html>
