<?php
include 'connection.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Teacher") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

$teacher_id = $_SESSION["user_id"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Results</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fontawesome-free-6.7.2-web/fontawesome-free-6.7.2-web/css/all.css">
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
        margin-left: 250px;
        padding: 20px;
    }

    .card {
        border-left: 5px solid #2E7D32;
    }

    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
        margin-bottom: 20px;
    }
    
    #dataTable {
        width: 100%;
        margin-top: 20px;
        margin-bottom: 20px;
        border-collapse: collapse;
    }
    
    #dataTable th, #dataTable td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    
    #dataTable th {
        background-color: #f2f2f2;
    }
    
    #dataTable tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    
    @media print {
        .sidebar, .no-print {
            display: none !important;
        }
        
        .main-content {
            margin-left: 0;
            padding: 0;
        }
        
        body {
            background-color: white;
        }
    }

    .table th, .table td {
    padding: 12px 16px !important; /* More padding for better spacing */
    vertical-align: middle !important; /* Center content vertically */
}
.table thead th {
    background-color: #d1e7dd !important; /* Slightly different green for header */
    color: #000;
    border-bottom: 2px solid #198754;
}
.table tbody tr:nth-child(even) {
    background-color: #f8f9fa;
}

</style>
</head>
<body class="bg-light">
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
        <h2 class="text-success mb-4">Examination Results</h2>

    <!-- Exam selection form -->
    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-6">
                <label>Select Exam:</label>
                <select name="exam_id" class="form-select" required>
                    <option value="">-- Select Exam --</option>
                    <?php
                    $sql = "SELECT exam_id, exam_name FROM exams WHERE teacher_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $teacher_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $selected = (isset($_GET['exam_id']) && $_GET['exam_id'] == $row['exam_id']) ? 'selected' : '';
                        echo "<option value='{$row['exam_id']}' $selected>{$row['exam_name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3 align-self-end">
                <button type="submit" class="btn btn-success">View Results</button>
            </div>
        </div>
    </form>

    <!-- Display results -->
    <?php
    if (isset($_GET['exam_id'])) {
        $exam_id = $_GET['exam_id'];

        $sql = "SELECT users.Fname, users.Mname, users.Lname, exam_submissions.submit_time, exam_submissions.taken, SUM(student_answers.marks_obtained) AS total_score
                FROM exam_submissions
                JOIN users ON exam_submissions.student_id = users.user_id
                JOIN student_answers ON student_answers.student_id = users.user_id AND student_answers.exam_id = exam_submissions.exam_id
                WHERE exam_submissions.exam_id = ?
                GROUP BY users.user_id, exam_submissions.submit_time, exam_submissions.taken";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0): ?>
            <div class="table-responsive">
            <table class="table table-bordered table-hover bg-white shadow-sm">
                    <thead class="table-success">
                        <tr>
                            <th>Student Name</th>
                            <th>Submitted Time</th>
                            <th>Status</th>
                            <th>Score</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['Fname']. ' ' . $row['Mname']. ' ' . $row['Lname'] ?></td>
                            <td><?= $row['submit_time'] ?></td>
                            <td><?= $row['taken'] ? 'Submitted' : 'Not Submitted' ?></td>
                            <td><?= $row['total_score'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="alert alert-warning">No results found for this exam.</p>
        <?php endif;
    }
    ?>
    </main>
</div>
</div>
<script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
