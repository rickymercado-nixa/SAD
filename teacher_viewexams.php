<?php
include 'connection.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Teacher") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

$teacher_id = $_SESSION['user_id'];

date_default_timezone_set('Asia/Manila');
$now = date("Y-m-d H:i:s");

$update_query = "UPDATE exams 
                 SET status = CASE 
                     WHEN start_time <= ? AND end_time > ? THEN 'Ongoing'
                     WHEN end_time <= ? THEN 'Completed'
                     ELSE 'Upcoming'
                 END";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("sss", $now, $now, $now);
$stmt->execute();

if (isset($_POST['update'])) {
    $exam_id = $_POST['exam_id'];
    $exam_name = trim($_POST['exam_name']);
    $total_marks = $_POST['total_marks'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $current_time = date("Y-m-d H:i:s");

    // Server-side time validation
    if ($start_time < $current_time || $end_time < $current_time) {
        echo "<script>alert('Start time and end time cannot be in the past.'); window.location.href='teacher_viewexams.php';</script>";
        exit();
    } elseif ($end_time <= $start_time) {
        echo "<script>alert('End time must be after start time.'); window.location.href='teacher_viewexams.php';</script>";
        exit();
    }

    $sql = "UPDATE exams SET exam_name = ?, total_marks = ?, start_time = ?, end_time = ? WHERE exam_id = ? AND teacher_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sissii", $exam_name, $total_marks, $start_time, $end_time, $exam_id, $teacher_id);

    if ($stmt->execute()) {
        echo "<script>alert('Exam Updated Successfully!'); window.location.href='teacher_viewexams.php';</script>";
    }
}

if (isset($_POST['delete'])) {
    $exam_id = $_POST['exam_id'];
    $sql = "DELETE FROM exams WHERE exam_id = ? AND teacher_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $exam_id, $teacher_id);
    if ($stmt->execute()) {
        echo "<script>alert('Exam Deleted Successfully!'); window.location.href='teacher_viewexams.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fontawesome-free-6.7.2-web/fontawesome-free-6.7.2-web/css/all.min.css">
</head>
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
        }
</style>
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
    <h2 class="text-success">Manage Exams</h2>
    <table class="table table-bordered text-center">
        <thead>
        <tr>
            <th>Exam Name</th>
            <th>Subject</th>
            <th>Total Marks</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Status</th>
            <th>Actions</th>
            <th>Manage Questions</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT exams.*, subjects.subject_name FROM exams JOIN subjects ON exams.sub_id = subjects.sub_id WHERE exams.teacher_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result -> num_rows > 0){
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td>{$row['exam_name']}</td>
                <td>{$row['subject_name']}</td>
                <td>{$row['total_marks']}</td>
                <td>{$row['start_time']}</td>
                <td>{$row['end_time']}</td>
                <td>{$row['status']}</td>
                <td>
                    <button class='btn btn-primary btn-sm' data-bs-toggle='modal' data-bs-target='#editExamModal{$row['exam_id']}'>Edit</button>
                    <form method='POST' class='d-inline'>
                        <input type='hidden' name='exam_id' value='{$row['exam_id']}'>
                        <button type='submit' name='delete' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure?\")'>Delete</button>
                    </form>
                </td>
                <td>
    <a href='teacher_questions.php?exam_id={$row['exam_id']}' class='btn btn-warning btn-sm'>Manage Questions</a>
</td>

            </tr>";
            
            echo "<div class='modal fade' id='editExamModal{$row['exam_id']}' tabindex='-1' aria-hidden='true'>
                <div class='modal-dialog'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title'>Edit Exam</h5>
                            <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                        </div>
                        <form method='POST' class='update-form'>
                            <div class='modal-body'>
                                <input type='hidden' name='exam_id' value='{$row['exam_id']}'>
                                <label class='form-label'>Exam Name</label>
                                <input type='text' name='exam_name' class='form-control' value='{$row['exam_name']}' required>
                                <label class='form-label'>Total Marks</label>
                                <input type='number' name='total_marks' class='form-control' value='{$row['total_marks']}' required>
                                <label class='form-label'>Start Time</label>
                                <input type='datetime-local' name='start_time' class='form-control' value='".date("Y-m-d\TH:i", strtotime($row['start_time']))."' required>
                                <label class='form-label'>End Time</label>
                                <input type='datetime-local' name='end_time' class='form-control' value='".date("Y-m-d\TH:i", strtotime($row['end_time']))."' required>
                            </div>
                            <div class='modal-footer'>
                                <button type='submit' name='update' class='btn btn-success'>Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>";
    }
}else{
    echo "<tr><td colspan='9' class='text-center text-danger'>No Exam Found!</td></tr>";
}
        ?>
        </tbody>
    </table>
    </main>
    </div>
</div>
</div>
<script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll(".update-form").forEach(form => {
    form.addEventListener("submit", function (e) {
        const startInput = form.querySelector("input[name='start_time']");
        const endInput = form.querySelector("input[name='end_time']");
        if (startInput && endInput) {
            const start = new Date(startInput.value);
            const end = new Date(endInput.value);
            const now = new Date();

            if (start < now || end < now) {
                alert("Start time and end time cannot be in the past.");
                e.preventDefault();
            } else if (end <= start) {
                alert("End time must be after start time.");
                e.preventDefault();
            }
        }
    });
});

</script>

</body>
</html>
