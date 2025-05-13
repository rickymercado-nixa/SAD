<?php
    require 'connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Score Report</title>
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

    .pdf-header img {
    display: block;
    margin: 0 auto;
}

@media screen {
    .pdf-header, .pdf-footer {
        display: none;
    }
}

@media print {
    .no-print {
        display: none !important;
    }
    .pdf-header, .pdf-footer {
        display: block !important;
    }
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
        <?php
if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Teacher") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

$teacher_id = $_SESSION['user_id'];
$result = $conn->query("SELECT Fname, Lname FROM users WHERE user_id = '$teacher_id'");
if ($teacher_row = $result->fetch_assoc()) {
    $teacher_name = $teacher_row['Fname'] . ' ' . $teacher_row['Lname'];
} else {
    $teacher_name = 'Unknown Teacher';
}

if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];

    // Get student full name
    $stmt = $conn->prepare("SELECT Fname, Lname FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student_info = $stmt->get_result()->fetch_assoc();
    $full_name = $student_info['Fname'] . ' ' . $student_info['Lname'];

    // Fetch student's exam scores
    $stmt = $conn->prepare("
        SELECT exams.exam_name, exams.total_marks, exams.status, 
               SUM(student_answers.marks_obtained) AS student_score
        FROM student_answers
        INNER JOIN exams ON student_answers.exam_id = exams.exam_id
        WHERE student_answers.student_id = ?
        GROUP BY exams.exam_id
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $exam_results = $stmt->get_result();
}
?>
<h1>Exam Score Reports</h1>
<form method="GET" class="mb-3 no-print">
    <label for="student_id" class="form-label">Select a Student:</label>
    <select name="student_id" id="student_id" class="form-select" onchange="this.form.submit()">
        <option value="">-- Choose Student --</option>
        <?php
        $students = $conn->prepare("
            SELECT DISTINCT users.user_id, users.Fname, users.Lname
            FROM users
            JOIN enrollments ON users.user_id = enrollments.student_id
            JOIN subjects ON enrollments.sub_id = subjects.sub_id
            WHERE users.User_role = 'Student' AND subjects.teacher_id = ?
        ");
        $students->bind_param("i", $teacher_id);
        $students->execute();
        $students_result = $students->get_result();
        while ($s = $students_result->fetch_assoc()):
        ?>
            <option value="<?= $s['user_id'] ?>" <?= (isset($_GET['student_id']) && $_GET['student_id'] == $s['user_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['Fname'] . ' ' . $s['Lname']) ?>
            </option>
        <?php endwhile; ?>
    </select>
</form>

<?php if (isset($exam_results)): ?>
    <div id="pdfContent" class="p-4" style="background-color: white; color: black;">
    <!-- Header Template -->
    <div class="pdf-header text-center mb-4">
        <img src="assets/images/pnhs.jpg" alt="School Logo" style="height: 80px;">
        <h2 class="mt-2 mb-0">Polomolok National High School</h2>
        <p class="mb-0">Octavio Village, Cannery Site, Polomolok, South Cotabato.</p>
        <hr style="border: 1px solid #000;">
    </div>

    <!-- Report Content -->
    <h4 class="text-center mb-3">Exam Score Report for <strong><?= htmlspecialchars($full_name) ?></strong></h4>
    <table id="dataTable" class="table table-bordered text-center">
        <thead>
            <tr>
                <th>Exam Name</th>
                <th>Status</th>
                <th>Score</th>
                <th>Total Marks</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $total_score = 0;
        $total_possible = 0;
        while ($row = $exam_results->fetch_assoc()):
            $percent = ($row['student_score'] / $row['total_marks']) * 100;
            $total_score += $row['student_score'];
            $total_possible += $row['total_marks'];
        ?>
            <tr>
                <td><?= htmlspecialchars($row['exam_name']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= $row['student_score'] ?></td>
                <td><?= $row['total_marks'] ?></td>
                <td><?= number_format($percent, 2) ?>%</td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <p><strong>Total Score:</strong> <?= $total_score ?> / <?= $total_possible ?></p>
    <p><strong>Overall Percentage:</strong> <?= $total_possible ? number_format(($total_score / $total_possible) * 100, 2) : '0.00' ?>%</p>

    <!-- Footer Template -->
    <div class="pdf-footer mt-5 text-center">
        <hr style="border: 1px solid #000;">
        <p><strong>Prepared by:</strong></p>
<p><strong><?= htmlspecialchars($teacher_name) ?></strong></p>
        <p>___________________________</p>
        <p><em>Teacher's Signature</em></p>
    </div>

        <button class="btn btn-success no-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print Report
        </button>
        <button class="btn btn-primary no-print" onclick="downloadPDF()">
            <i class="fas fa-file-pdf"></i> Download as PDF
        </button>
    </div>
<?php endif; ?>

        </main>
    </div>
</div>
<script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/Chart.js-4.4.8/package/dist/chart.umd.js"></script>
<script src="assets/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    const element = document.getElementById("pdfContent");

    // Temporarily show print-only elements (header/footer)
    document.querySelectorAll('.pdf-header, .pdf-footer').forEach(function(elem) {
        elem.style.display = 'block';
    });

    // Hide UI buttons and sidebar
    document.querySelectorAll('.no-print').forEach(function(elem) {
        elem.style.display = 'none';
    });

    // PDF options
    const opt = {
        margin: 0.5,
        filename: 'Exam_Score_Report_<?= isset($full_name) ? preg_replace("/\s+/", "_", $full_name) : "Report" ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    };

    // Generate PDF
    html2pdf().set(opt).from(element).save().then(() => {
        // Re-hide the header/footer
        document.querySelectorAll('.pdf-header, .pdf-footer').forEach(function(elem) {
            elem.style.display = 'none';
        });

        // Restore the UI elements
        document.querySelectorAll('.no-print').forEach(function(elem) {
            elem.style.display = '';
        });
    });
}

</script>
</body>
</html>