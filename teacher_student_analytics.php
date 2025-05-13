<?php
require 'connection.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Teacher") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

$user_id = $_SESSION["user_id"];

// Fetch teacher information
$stmt = $conn->prepare("SELECT fname, lname FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();
$teacher_name = $teacher['fname'] . ' ' . $teacher['lname'];
$stmt->close();

// Fetch school information (from a hypothetical settings table)
/*$stmt = $conn->prepare("SELECT school_name FROM settings WHERE id = 1");
$stmt->execute();
$result = $stmt->get_result();
$school_info = $result->fetch_assoc();
$school_name = $school_info['school_name'] ?? 'School Name';
$stmt->close();
*/
// Fetch students under the teacher
$stmt = $conn->prepare("
    SELECT DISTINCT users.user_id, users.fname, users.lname
    FROM enrollments
    JOIN subjects ON enrollments.sub_id = subjects.sub_id
    JOIN users ON enrollments.student_id = users.user_id
    WHERE subjects.teacher_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

// Prepare for graphs if student selected
$exam_subject_scores = [];
$question_correct_incorrect = [];
$selected_student = null;

if (isset($_GET['student_id']) && !empty($_GET['student_id'])) {
    $selected_student_id = $_GET['student_id'];
    
    // Get selected student info
    foreach ($students as $student) {
        if ($student['user_id'] == $selected_student_id) {
            $selected_student = $student;
            break;
        }
    }

    // Fetch student's exam subjects and scores
    $stmt = $conn->prepare("
        SELECT exams.exam_name, subjects.subject_name, SUM(student_answers.marks_obtained) AS total_score
        FROM student_answers
        JOIN exams ON student_answers.exam_id = exams.exam_id
        JOIN subjects ON exams.sub_id = subjects.sub_id
        WHERE student_answers.student_id = ? AND exams.teacher_id = ?
        GROUP BY exams.exam_id
    ");
    $stmt->bind_param("ii", $selected_student_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $exam_subject_scores[] = $row;
    }
    $stmt->close();
    $strongest_subject = null;
    $weakest_subject = null;

    if (!empty($exam_subject_scores)) {
        $max_score = -1;
        $min_score = PHP_INT_MAX;

        foreach ($exam_subject_scores as $exam) {
            if ($exam['total_score'] > $max_score) {
                $max_score = $exam['total_score'];
                $strongest_subject = $exam['subject_name'];
            }

            if ($exam['total_score'] < $min_score) {
                $min_score = $exam['total_score'];
                $weakest_subject = $exam['subject_name'];
            }
        }
    }

    // Fetch correct and incorrect answers per exam
    $stmt = $conn->prepare("
        SELECT exams.exam_name,
            SUM(CASE WHEN student_answers.is_correct = 1 THEN 1 ELSE 0 END) AS correct_answers,
            SUM(CASE WHEN student_answers.is_correct = 0 THEN 1 ELSE 0 END) AS incorrect_answers
        FROM student_answers
        JOIN exams ON student_answers.exam_id = exams.exam_id
        WHERE student_answers.student_id = ? AND exams.teacher_id = ?
        GROUP BY exams.exam_id
    ");
    $stmt->bind_param("ii", $selected_student_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $question_correct_incorrect[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Analytics</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fontawesome-free-6.7.2-web/fontawesome-free-6.7.2-web/css/all.min.css">
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
    
    /* PDF Header and Footer Styles */
    .pdf-header {
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #198754;
    }
    
    .school-logo {
        max-height: 80px;
        margin-bottom: 10px;
    }
    
    .pdf-footer {
        margin-top: 30px;
        padding-top: 10px;
        border-top: 1px solid #ddd;
    }
    
    .signature-line {
        margin-top: 40px;
        width: 200px;
        border-bottom: 1px solid #000;
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
            <div id="reportContent" style="max-width: 800px; margin: auto;">
                <!-- PDF Header - Hidden in normal view, shown in PDF and print -->
                <div class="pdf-header d-none" id="pdfHeader">
                    <img src="assets/images/pnhs.jpg" alt="School Logo" class="school-logo">
                    <h2>Polomolok National High School</h2>
                    <h4>Student Performance Analytics Report</h4>
                </div>
                
                <h2 class="web-only">Student Analytics</h2>
                
                <div class="mb-3 no-print">
                    <?php if (!empty($exam_subject_scores)): ?>
                    <button id="downloadPDF" class="btn btn-danger">
                        <i class="fas fa-file-pdf"></i> Download Report as PDF
                    </button>
                    <button id="printReport" class="btn btn-primary ms-2">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                    <?php endif; ?>
                </div>
                
                <form method="GET" action="" class="no-print">
                    <div class="form-group mb-3">
                        <label for="student_id">Select Student:</label>
                        <select name="student_id" id="student_id" class="form-select" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['user_id']; ?>" <?php echo (isset($_GET['student_id']) && $_GET['student_id'] == $student['user_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['fname'] . ' ' . $student['lname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">Generate Report</button>
                </form>

                <?php if ($selected_student): ?>
                    <div class="mt-4 alert alert-info">
                        <h4>Student: <?php echo htmlspecialchars($selected_student['fname'] . ' ' . $selected_student['lname']); ?></h4>
                        <p>Report generated on: <?php echo date('F j, Y, g:i a'); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($exam_subject_scores)): ?>
                <div class="mt-5">
                    <h4>Exam Scores per Subject</h4>
                    <div class="chart-container">
                        <canvas id="examScoresChart"></canvas>
                    </div>
                    
                    <!-- Data Table for Exam Scores -->
                    <table id="dataTable" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Exam Name</th>
                                <th>Subject</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exam_subject_scores as $score): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($score['exam_name']); ?></td>
                                <td><?php echo htmlspecialchars($score['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($score['total_score']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-5">
                    <h4>Correct vs Incorrect Answers per Exam</h4>
                    <div class="chart-container">
                        <canvas id="correctIncorrectChart"></canvas>
                    </div>
                    
                    <!-- Data Table for Correct/Incorrect -->
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Exam Name</th>
                                <th>Correct Answers</th>
                                <th>Incorrect Answers</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($question_correct_incorrect as $answers): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($answers['exam_name']); ?></td>
                                <td><?php echo htmlspecialchars($answers['correct_answers']); ?></td>
                                <td><?php echo htmlspecialchars($answers['incorrect_answers']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-5">
                    <h4>Student's Strength and Weakness</h4>
                    <ul class="list-group">
                        <li class="list-group-item">
                            <strong>Strongest Subject:</strong> 
                            <?php echo htmlspecialchars($strongest_subject); ?> (Score: <?php echo $max_score; ?>)
                        </li>
                        <li class="list-group-item">
                            <strong>Weakest Subject:</strong> 
                            <?php echo htmlspecialchars($weakest_subject); ?> (Score: <?php echo $min_score; ?>)
                        </li>
                    </ul>
                </div>
                
                <!-- PDF Footer - Hidden in normal view, shown in PDF and print -->
                <div class="pdf-footer d-none" id="pdfFooter">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Teacher:</strong> <?php echo htmlspecialchars($teacher_name); ?></p>
                            <div class="signature-line"></div>
                            <p>Teacher's Signature</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p>Generated on: <?php echo date('F j, Y'); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Hidden iframe for PDF generation -->
<iframe id="pdf-iframe" style="display:none;"></iframe>

<script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/Chart.js-4.4.8/package/dist/chart.umd.js"></script>
<script src="assets/html2pdf.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let charts = [];
    
    <?php if (!empty($exam_subject_scores)): ?>
    // 1. Exam Scores Pie Chart
    const examScoresCtx = document.getElementById('examScoresChart').getContext('2d');
    const examScoresChart = new Chart(examScoresCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($exam_subject_scores, 'exam_name')); ?>,
            datasets: [{
                label: 'Total Score',
                data: <?php echo json_encode(array_column($exam_subject_scores, 'total_score')); ?>,
                backgroundColor: ['#4caf50', '#2196f3', '#ff9800', '#f44336', '#9c27b0', '#00bcd4'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
    charts.push(examScoresChart);

    // 2. Correct vs Incorrect Answers (Bar Chart)
    const correctIncorrectCtx = document.getElementById('correctIncorrectChart').getContext('2d');
    const correctIncorrectChart = new Chart(correctIncorrectCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($question_correct_incorrect, 'exam_name')); ?>,
            datasets: [
                {
                    label: 'Correct',
                    backgroundColor: '#4caf50',
                    data: <?php echo json_encode(array_column($question_correct_incorrect, 'correct_answers')); ?>
                },
                {
                    label: 'Incorrect',
                    backgroundColor: '#f44336',
                    data: <?php echo json_encode(array_column($question_correct_incorrect, 'incorrect_answers')); ?>
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
    charts.push(correctIncorrectChart);
    
    // Print report button
    document.getElementById('printReport').addEventListener('click', function() {
        // Show header and footer for printing
        document.getElementById('pdfHeader').classList.remove('d-none');
        document.getElementById('pdfFooter').classList.remove('d-none');
        
        window.print();
        
        // Hide header and footer after printing
        setTimeout(function() {
            document.getElementById('pdfHeader').classList.add('d-none');
            document.getElementById('pdfFooter').classList.add('d-none');
        }, 500);
    });
    
    // PDF download with enhanced header and footer
    document.getElementById('downloadPDF').addEventListener('click', function() {
        const btn = this;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
        btn.disabled = true;
        
        // Show header and footer for PDF generation
        document.getElementById('pdfHeader').classList.remove('d-none');
        document.getElementById('pdfFooter').classList.remove('d-none');
        
        // Wait for charts to render completely
        setTimeout(function() {
            try {
                // Create a simplified copy of content for PDF generation
                const reportContent = document.getElementById('reportContent');
                
                // Wait for charts to be fully rendered
                setTimeout(function() {
                    try {
                        // Get student name for filename
                        const studentName = "<?php echo $selected_student ? preg_replace('/[^a-zA-Z0-9]/', '_', $selected_student['fname'] . '_' . $selected_student['lname']) : 'report'; ?>";
                        const dateTime = new Date().toISOString().replace(/[:.]/g, '-');
                        const filename = 'student_analytics_' + studentName + '_' + dateTime + '.pdf';
                        
                        // Create PDF options
                        const opt = {
                            margin: [0.5, 0.5, 0.5, 0.5],
                            filename: filename,
                            image: { type: 'jpeg', quality: 0.98 },
                            html2canvas: { 
                                scale: 2,
                                useCORS: true,
                                allowTaint: true
                            },
                            jsPDF: { unit: 'cm', format: 'a4', orientation: 'portrait' }
                        };
                        
                        // Hide non-printable elements
                        document.querySelectorAll('.no-print').forEach(el => {
                            el.style.display = 'none';
                        });
                        document.querySelectorAll('.web-only').forEach(el => {
                            el.style.display = 'none';
                        });
                        
                        // Generate PDF
                        html2pdf()
                          .from(reportContent)
                          .set(opt)
                          .toPdf()
                          .get('pdf')
                          .then(function(pdf) {
                              // Success
                              window.open(URL.createObjectURL(pdf.output('blob')));
                              
                              // Reset UI elements
                              btn.innerHTML = originalText;
                              btn.disabled = false;
                              document.querySelectorAll('.no-print').forEach(el => {
                                  el.style.display = '';
                              });
                              document.querySelectorAll('.web-only').forEach(el => {
                                  el.style.display = '';
                              });
                              document.getElementById('pdfHeader').classList.add('d-none');
                              document.getElementById('pdfFooter').classList.add('d-none');
                          })
                          .catch(function(error) {
                              console.error("PDF generation error:", error);
                              alert("There was an error generating the PDF. Please try the print option instead.");
                              
                              // Reset UI elements
                              btn.innerHTML = originalText;
                              btn.disabled = false;
                              document.querySelectorAll('.no-print').forEach(el => {
                                  el.style.display = '';
                              });
                              document.querySelectorAll('.web-only').forEach(el => {
                                  el.style.display = '';
                              });
                              document.getElementById('pdfHeader').classList.add('d-none');
                              document.getElementById('pdfFooter').classList.add('d-none');
                          });
                          
                    } catch (error) {
                        console.error("Error in PDF generation:", error);
                        alert("There was an error with the PDF generation. Please try the print option instead.");
                        
                        // Reset UI elements
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        document.querySelectorAll('.no-print').forEach(el => {
                            el.style.display = '';
                        });
                        document.querySelectorAll('.web-only').forEach(el => {
                            el.style.display = '';
                        });
                        document.getElementById('pdfHeader').classList.add('d-none');
                        document.getElementById('pdfFooter').classList.add('d-none');
                    }
                }, 500);
                
            } catch (error) {
                console.error("Error preparing PDF content:", error);
                alert("Error preparing PDF content. Please try the print option instead.");
                
                // Reset UI elements
                btn.innerHTML = originalText;
                btn.disabled = false;
                document.getElementById('pdfHeader').classList.add('d-none');
                document.getElementById('pdfFooter').classList.add('d-none');
            }
        }, 300);
    });
    <?php endif; ?>
});
</script>
</body>
</html>