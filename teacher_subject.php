<?php
include 'connection.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Teacher") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

$teacher_id = $_SESSION['user_id'];

function generateSubjectCode() {
    return substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 6);
}


if (isset($_POST['create'])) {
    $subject_name = trim($_POST['subject_name']);
    $subject_code = generateSubjectCode();

    $sql = "INSERT INTO subjects (teacher_id, subject_name, subject_code) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $teacher_id, $subject_name, $subject_code);
    
    if ($stmt->execute()) {
        echo "<script>alert('Subject Created Successfully!'); window.location.href='teacher_subject.php';</script>";
    } else {
        echo "<script>alert('Error creating subject. Try again!');</script>";
    }
}

// Handle Subject Update
if (isset($_POST['update'])) {
    $subject_id = $_POST['sub_id'];
    $subject_name = trim($_POST['subject_name']);

    $sql = "UPDATE subjects SET subject_name = ? WHERE sub_id = ? AND teacher_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $subject_name, $subject_id, $teacher_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Subject Updated Successfully!'); window.location.href='teacher_subject.php';</script>";
    }
}

if (isset($_POST['delete'])) {
    $subject_id = $_POST['sub_id'];

    $sql = "DELETE FROM subjects WHERE sub_id = ? AND teacher_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $subject_id, $teacher_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Subject Deleted Successfully!'); window.location.href='teacher_subject.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fontawesome-free-6.7.2-web/fontawesome-free-6.7.2-web/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
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

            <main class="col-md-9 col-lg-10 p-4">
                <h2 class="text-success">Manage Subjects</h2>

                <form method="POST" class="mb-3">
                    <div class="mb-2">
                        <label for="subject_name" class="form-label">Subject Name:</label>
                        <input type="text" name="subject_name" class="form-control" required>
                    </div>
                    <button type="submit" name="create" class="btn btn-success">Create Subject</button>
                </form>

                <hr>

                <h4>Your Subjects</h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Subject Name</th>
                            <th>Subject Code</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT subjects.*
                                FROM subjects
                                WHERE subjects.teacher_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $teacher_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>{$row['subject_name']}</td>
                                <td>{$row['subject_code']}</td>
                                <td>
                                    <form method='POST' style='display:inline;'>
                                        <input type='hidden' name='sub_id' value='{$row['sub_id']}'>
                                        <input type='text' name='subject_name' value='{$row['subject_name']}' required>
                                        <button type='submit' name='update' class='btn btn-warning btn-sm'>Update</button>
                                    </form>
                                    <form method='POST' style='display:inline;'>
                                        <input type='hidden' name='sub_id' value='{$row['sub_id']}'>
                                        <button type='submit' name='delete' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure?\")'>Delete</button>
                                    </form>
                                </td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </main>
        </div>
    </div>
    <script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
