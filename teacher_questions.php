<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'connection.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Teacher") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

if (!isset($_GET['exam_id'])) {
    echo "<script>alert('Exam ID is missing!'); window.location.href='teacher_dashboard.php';</script>";
    exit();
}

$exam_id = (int)$_GET['exam_id'];
$teacher_id = $_SESSION['user_id'];

$question_part = isset($_GET['question_part']) ? $_GET['question_part'] : '';

if (!empty($question_part)) {
    $sql = "SELECT * FROM questions WHERE exam_id = ? AND question_part = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $exam_id, $question_part);
} else {
    $sql = "SELECT * FROM questions WHERE exam_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $exam_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Questions</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fontawesome-free-6.7.2-web/fontawesome-free-6.7.2-web/css/all.min.css">
    <script>
    function showQuestionFields() {
        let type = document.getElementById('question_type').value;

        document.getElementById('multiple_choice_fields').style.display = (type === 'Multiple Choice') ? 'block' :
            'none';
        document.getElementById('true_false_fields').style.display = (type === 'True/False') ? 'block' : 'none';
        document.getElementById('fill_blanks_fields').style.display = (type === 'Fill in the Blanks') ? 'block' :
            'none';

        document.querySelectorAll('#multiple_choice_fields input').forEach(el => {
            el.required = (type === 'Multiple Choice');
        });

        document.querySelectorAll('#true_false_fields select').forEach(el => {
            el.required = (type === 'True/False');
        });

        document.querySelectorAll('#fill_blanks_fields input').forEach(el => {
            el.required = (type === 'Fill in the Blanks');
        });

        if (type !== 'Multiple Choice') {
            document.querySelectorAll('#multiple_choice_fields input').forEach(el => el.removeAttribute('required'));
        }
        if (type !== 'True/False') {
            document.querySelectorAll('#true_false_fields select').forEach(el => el.removeAttribute('required'));
        }
        if (type !== 'Fill in the Blanks') {
            document.querySelectorAll('#fill_blanks_fields input').forEach(el => el.removeAttribute('required'));
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        showQuestionFields();
        document.getElementById('question_type').addEventListener('change', showQuestionFields);
    });
    </script>
    <style>
    body {
        background-color: #f8f9fa;
        color: #333;
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
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
        margin-left: 220px;
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
                <h2 class="text-success">Manage Questions</h2>
                <button class="btn btn-primary mb-3"
                    onclick="location.href='teacher_add_questions.php?exam_id=<?= $exam_id ?>'">
                    Add Question
                </button>
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5>Added Questions</h5>
                        <form method="GET" action="teacher_questions.php" class="mb-3">
                            <input type="hidden" name="exam_id" value="<?= $exam_id ?>">

                            <label for="question_part" class="form-label">Filter by Question Part:</label>
                            <select name="question_part" id="question_part" class="form-select"
                                onchange="this.form.submit()">
                                <option value="">-- Select Question Part --</option>
                                <option value="Test 1"
                                    <?= (isset($_GET['question_part']) && $_GET['question_part'] == 'Test 1') ? 'selected' : '' ?>>
                                    Test 1</option>
                                <option value="Test 2"
                                    <?= (isset($_GET['question_part']) && $_GET['question_part'] == 'Test 2') ? 'selected' : '' ?>>
                                    Test 2</option>
                                <option value="Test 3"
                                    <?= (isset($_GET['question_part']) && $_GET['question_part'] == 'Test 3') ? 'selected' : '' ?>>
                                    Test 3</option>
                                <option value="Test 4"
                                    <?= (isset($_GET['question_part']) && $_GET['question_part'] == 'Test 4') ? 'selected' : '' ?>>
                                    Test 4</option>
                                <option value="">All Question Parts</option>
                            </select>
                        </form>
                    </div>
                    <div class="card-body">
                        <?php
        if ($result->num_rows > 0) {
            echo "<table class='table table-bordered text-center'>";
            echo "<thead class='table-dark'>
                    <tr>
                        <th>Exam Part</th>
                        <th>Question</th>
                        <th>Type</th>
                        <th>Marks</th>
                        <th>Choices / Answer</th>
                        <th>Actions</th>
                    </tr>
                  </thead>";
            echo "<tbody>";
            $seen_parts = []; // Track displayed parts
            while ($row = $result->fetch_assoc()) {
                if (!in_array($row['question_part'], $seen_parts)) {
                    echo "<tr class='table-success'>
                            <td colspan='6' class='text-start'>
                                <strong>Exam Part: " . htmlspecialchars($row['question_part']) . "</strong>
                                <a href='teacher_set_timer.php?exam_id=$exam_id&question_part=" . urlencode($row['question_part']) . "' class='btn btn-sm btn-outline-primary ms-3'>
                                    Set Timer
                                </a>
                            </td>
                          </tr>";
                    $seen_parts[] = $row['question_part'];
                }
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['question_part']) ."</td>";
                echo "<td>" . htmlspecialchars($row['question_text']) . "</td>";
                echo "<td>" . htmlspecialchars($row['question_type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['marks']) . "</td>";
                echo "<td>";
                
                if ($row['question_type'] === "Multiple Choice" || $row['question_type'] === "True/False") {
                    $sql_choices = "SELECT * FROM choices WHERE question_id = ?";
                    $stmt_choices = $conn->prepare($sql_choices);
                    $stmt_choices->bind_param("i", $row['question_id']);
                    $stmt_choices->execute();
                    $result_choices = $stmt_choices->get_result();

                    $choice_labels = ['A', 'B', 'C', 'D'];
                    $index = 0;

                    while ($choice = $result_choices->fetch_assoc()) {
                    $correct = ($choice['is_correct'] == 1) ? "<span class='badge bg-success'>Correct</span>" : "";
                    echo "<p><strong>Choice " . $choice_labels[$index] . ":</strong> " . htmlspecialchars($choice['choice_text']) . " $correct</p>";
                    $index++;
                }

                } elseif ($row['question_type'] === "Fill in the Blanks") {
                    $sql_answer = "SELECT choice_text FROM choices WHERE question_id = ?";
                    $stmt_answer = $conn->prepare($sql_answer);
                    $stmt_answer->bind_param("i", $row['question_id']);
                    $stmt_answer->execute();
                    $stmt_answer->bind_result($correct_answer);
                    $result_answer = $stmt_answer->get_result();
                    echo "<strong>Answers:</strong> ";
                    $answers = [];
                    
                    while ($answer_row = $result_answer->fetch_assoc()) {
                        $answers[] = htmlspecialchars($answer_row['choice_text']);
                    }
                
                    echo implode(", ", $answers);
                
                    $stmt_answer->close();
                }
                
                echo "</td>";

                echo "<td>";
echo '<a href="teacher_update_question.php?question_id=' . $row['question_id'] . '" class="btn btn-warning btn-sm">Edit</a> ';

                echo '<form action="teacher_delete_question.php" method="POST" style="display:inline;">
               <input type="hidden" name="question_id" value="' . $row['question_id'] . '">
                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this question?\');">Delete</button>
                </form>';

                echo "</td>"; 
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p>No questions added yet.</p>";
        }
        ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>