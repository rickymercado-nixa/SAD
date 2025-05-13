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

// Get total marks of the exam
$sql_total_marks = "SELECT total_marks FROM exams WHERE exam_id = ?";
$stmt_total = $conn->prepare($sql_total_marks);
$stmt_total->bind_param("i", $exam_id);
$stmt_total->execute();
$stmt_total->bind_result($total_marks);
$stmt_total->fetch();
$stmt_total->close();

// Get the sum of current marks in the questions table
$sql_current_marks = "SELECT COALESCE(SUM(marks), 0) FROM questions WHERE exam_id = ?";
$stmt_current = $conn->prepare($sql_current_marks);
$stmt_current->bind_param("i", $exam_id);
$stmt_current->execute();
$stmt_current->bind_result($current_marks);
$stmt_current->fetch();
$stmt_current->close();

// Calculate the remaining marks
$remaining_marks = $total_marks - $current_marks;

if (isset($_POST['add_questions'])) {
    $exam_id = $_POST['exam_id'];
    $question_part = $_POST['question_part'];
    $question_type = $_POST['question_type'];
    $timer = $_POST['timer'];
    $question_count = count($_POST['question_text']);
    $total_new_marks = 0;
    
    // Calculate total marks being added
    for ($q = 0; $q < $question_count; $q++) {
        if (!empty($_POST['question_text'][$q])) {
            $total_new_marks += (int)$_POST['marks'][$q];
        }
    }
    
    // Check if total marks exceed the remaining marks
    if ($total_new_marks > $remaining_marks) {
        echo "<script>alert('Adding these questions exceeds the total exam marks! Remaining marks: $remaining_marks'); window.history.back();</script>";
        exit();
    }

    $sql_check_timer = "SELECT timer FROM exam_part_timers WHERE exam_id = ? AND question_part = ?";
    $stmt_check = $conn->prepare($sql_check_timer);
    $stmt_check->bind_param("is", $exam_id, $question_part);
    $stmt_check->execute();
    $stmt_check->bind_result($existing_timer);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($existing_timer === null) {
        // Insert new timer
        $sql_insert_timer = "INSERT INTO exam_part_timers (exam_id, question_part, timer) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert_timer);
        $stmt_insert->bind_param("isi", $exam_id, $question_part, $timer);
        $stmt_insert->execute();
        $stmt_insert->close();
    } else {
        // Use existing timer
        $timer = $existing_timer;
    }

    $allowed_types = [
        "Test 1" => "Multiple Choice",
        "Test 2" => "True/False",
        "Test 3" => "Fill in the Blanks",
        "Test 4" => "Essay"
    ];

    if (!isset($allowed_types[$question_part]) || $allowed_types[$question_part] !== $question_type) {
        echo "<script>alert('Invalid question type for selected exam part!'); window.history.back();</script>";
        exit();
    }

    $questions_added = 0;
    
    // Process each question
    for ($q = 0; $q < $question_count; $q++) {
        $question_text = trim($_POST['question_text'][$q]);
        $marks = (int)$_POST['marks'][$q];
        
        // Skip empty questions
        if (empty($question_text)) {
            continue;
        }
        
        // Insert into questions table
        $sql = "INSERT INTO questions (exam_id, question_text, question_type, question_part, marks, timer) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssii", $exam_id, $question_text, $question_type, $question_part, $marks, $timer);
        $stmt->execute();
        $question_id = $stmt->insert_id;

        // Handle question types
        if ($question_type === "Multiple Choice") {
            $correct_answers = isset($_POST['mc_correct_answer'][$q]) ? $_POST['mc_correct_answer'][$q] : [];
            for ($i = 1; $i <= 4; $i++) {
                if (!empty($_POST["choice_{$q}_{$i}"])) {
                    $choice_text = trim($_POST["choice_{$q}_{$i}"]);
                    $is_correct = in_array($i, $correct_answers) ? 1 : 0;
                    $sql = "INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("isi", $question_id, $choice_text, $is_correct);
                    $stmt->execute();
                }
            }
        } elseif ($question_type === "True/False") {
            $tf_correct = isset($_POST['tf_correct_answer'][$q]) ? $_POST['tf_correct_answer'][$q] : "True";
            $choices = [
                ["True", ($tf_correct === "True") ? 1 : 0],
                ["False", ($tf_correct === "False") ? 1 : 0]
            ];
            foreach ($choices as $choice) {
                $sql = "INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isi", $question_id, $choice[0], $choice[1]);
                $stmt->execute();
            }
        } elseif ($question_type === "Fill in the Blanks") {
            if (!empty($_POST['fill_answer'][$q])) {
                $correct_answers = array_map('trim', explode(",", $_POST['fill_answer'][$q]));
                foreach ($correct_answers as $answer) {
                    if (!empty($answer)) {
                        $sql = "INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, 1)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("is", $question_id, $answer);
                        $stmt->execute();
                    }
                }
            }
        }
        
        $questions_added++;
    }

    echo "<script>alert('$questions_added Questions Added Successfully!'); window.location.href='teacher_questions.php?exam_id=$exam_id';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Multiple Questions</title>
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
        padding: 20px;
    }

    .form-label {
        font-weight: 600;
    }
    
    .question-container {
        background-color: #f5f5f5;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        border-left: 5px solid #198754;
    }
    
    .question-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .remove-question {
        color: #dc3545;
        cursor: pointer;
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
                <h2 class="text-success mb-4">Add Multiple Questions</h2>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Exam Information</h5>
                        <p class="text-danger" id="remaining_marks_display">Remaining Marks: <?= $remaining_marks ?></p>
                        
                        <form method="POST" id="question-form">
                            <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Exam Part</label>
                                    <select name="question_part" id="question_part" class="form-control" required>
                                        <option value="Test 1">Test 1 - Multiple Choice</option>
                                        <option value="Test 2">Test 2 - True/False</option>
                                        <option value="Test 3">Test 3 - Fill in the Blanks</option>
                                        <option value="Test 4">Test 4 - Essay</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Question Type</label>
                                    <select name="question_type" id="question_type" class="form-control" required>
                                        <option value="Multiple Choice">Multiple Choice</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Timer</label>
                                    <select name="timer" class="form-control" required>
                                        <option value="">Select Timer</option>
                                        <option value="5">5 minutes</option>
                                        <option value="10">10 minutes</option>
                                        <option value="15">15 minutes</option>
                                        <option value="30">30 minutes</option>
                                        <option value="45">45 minutes</option>
                                        <option value="60">1 hour</option>
                                        <option value="75">1 hour & 15 minutes</option>
                                        <option value="90">1 hour & 30 minutes</option>
                                        <option value="105">1 hour & 45 minutes</option>
                                        <option value="120">2 hours</option>
                                    </select>
                                    <p id="timer-message" class="text-danger"></p>
                                </div>
                            </div>
                            
                            <div id="questions-container">
                                <!-- Questions will be added here dynamically -->
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="button" id="add-question-btn" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Add Another Question
                                </button>
                                
                                <div>
                                    <button type="submit" name="add_questions" class="btn btn-success">
                                        Save All Questions
                                    </button>
                                    <a href="teacher_questions.php?exam_id=<?= $exam_id ?>" class="btn btn-secondary">Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
<script>
    // Global variables
    let questionCounter = 0;
    let remainingMarks = <?= $remaining_marks ?>;
    
    // Question type templates
    const questionTemplates = {
        "Multiple Choice": function(index) {
            return `
                <div class="mb-3">
                    <label class="form-label">Question Text</label>
                    <input type="text" name="question_text[${index}]" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Choices</label>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <input type="text" name="choice_${index}_1" class="form-control" placeholder="Choice A" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <input type="text" name="choice_${index}_2" class="form-control" placeholder="Choice B" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <input type="text" name="choice_${index}_3" class="form-control" placeholder="Choice C" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <input type="text" name="choice_${index}_4" class="form-control" placeholder="Choice D" required>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Correct Answer(s)</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="mc_correct_answer[${index}][]" value="1" id="mc_${index}_1">
                            <label class="form-check-label" for="mc_${index}_1">Choice A</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="mc_correct_answer[${index}][]" value="2" id="mc_${index}_2">
                            <label class="form-check-label" for="mc_${index}_2">Choice B</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="mc_correct_answer[${index}][]" value="3" id="mc_${index}_3">
                            <label class="form-check-label" for="mc_${index}_3">Choice C</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="mc_correct_answer[${index}][]" value="4" id="mc_${index}_4">
                            <label class="form-check-label" for="mc_${index}_4">Choice D</label>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Marks</label>
                    <input type="number" name="marks[${index}]" class="form-control marks-input" min="1" max="${remainingMarks}" required>
                </div>
            `;
        },
        "True/False": function(index) {
            return `
                <div class="mb-3">
                    <label class="form-label">Question Text</label>
                    <input type="text" name="question_text[${index}]" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Correct Answer</label>
                    <select name="tf_correct_answer[${index}]" class="form-control">
                        <option value="True">True</option>
                        <option value="False">False</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Marks</label>
                    <input type="number" name="marks[${index}]" class="form-control marks-input" min="1" max="${remainingMarks}" required>
                </div>
            `;
        },
        "Fill in the Blanks": function(index) {
            return `
                <div class="mb-3">
                    <label class="form-label">Question Text</label>
                    <input type="text" name="question_text[${index}]" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Correct Answers (comma separated)</label>
                    <input type="text" name="fill_answer[${index}]" class="form-control" placeholder="Example: answer1, answer2" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Marks</label>
                    <input type="number" name="marks[${index}]" class="form-control marks-input" min="1" max="${remainingMarks}" required>
                </div>
            `;
        },
        "Essay": function(index) {
            return `
                <div class="mb-3">
                    <label class="form-label">Question Text</label>
                    <input type="text" name="question_text[${index}]" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Marks</label>
                    <input type="number" name="marks[${index}]" class="form-control marks-input" min="1" max="${remainingMarks}" required>
                </div>
            `;
        }
    };
    
    // Function to add a new question container
    function addQuestionContainer() {
        const questionsContainer = document.getElementById('questions-container');
        const questionType = document.getElementById('question_type').value;
        
        // Create a new question container
        const questionDiv = document.createElement('div');
        questionDiv.className = 'question-container';
        questionDiv.id = `question-${questionCounter}`;
        
        // Create question header with remove button
        const headerDiv = document.createElement('div');
        headerDiv.className = 'question-header';
        headerDiv.innerHTML = `
            <h5>Question ${questionCounter + 1}</h5>
            <button type="button" class="btn btn-sm btn-outline-danger remove-question" data-index="${questionCounter}">
                Remove
            </button>
        `;
        
        // Create question content div
        const contentDiv = document.createElement('div');
        contentDiv.className = 'question-content';
        contentDiv.innerHTML = questionTemplates[questionType](questionCounter);
        
        // Add header and content to question container
        questionDiv.appendChild(headerDiv);
        questionDiv.appendChild(contentDiv);
        
        // Add question container to questions container
        questionsContainer.appendChild(questionDiv);
        
        // Add event listener to the remove button
        questionDiv.querySelector('.remove-question').addEventListener('click', function() {
            removeQuestion(this.getAttribute('data-index'));
        });
        
        // Add event listeners to marks inputs
        questionDiv.querySelectorAll('.marks-input').forEach(input => {
            input.addEventListener('change', updateTotalMarks);
        });
        
        questionCounter++;
        
        // Update questions numbering
        updateQuestionNumbers();
    }
    
    // Function to remove a question
    function removeQuestion(index) {
        const questionDiv = document.getElementById(`question-${index}`);
        questionDiv.remove();
        
        // Update questions numbering
        updateQuestionNumbers();
        updateTotalMarks();
    }
    
    // Function to update question numbers
    function updateQuestionNumbers() {
        const questionContainers = document.querySelectorAll('.question-container');
        questionContainers.forEach((container, index) => {
            container.querySelector('h5').textContent = `Question ${index + 1}`;
        });
    }
    
    // Function to update total marks
    function updateTotalMarks() {
        let totalMarks = 0;
        const marksInputs = document.querySelectorAll('.marks-input');
        
        marksInputs.forEach(input => {
            if (input.value) {
                totalMarks += parseInt(input.value);
            }
        });
        
        const updatedRemainingMarks = <?= $remaining_marks ?> - totalMarks;
        
        // Update the remaining marks display
        document.getElementById('remaining_marks_display').textContent = `Remaining Marks: ${updatedRemainingMarks}`;
        
        // Update max attribute of all marks inputs
        marksInputs.forEach(input => {
            const currentValue = parseInt(input.value) || 0;
            input.max = updatedRemainingMarks + currentValue;
        });
        
        // Check if we've exceeded the total marks
        if (updatedRemainingMarks < 0) {
            document.getElementById('remaining_marks_display').classList.add('fw-bold');
        } else {
            document.getElementById('remaining_marks_display').classList.remove('fw-bold');
        }
    }
    
    // Update question types based on selected exam part
    function updateQuestionTypes() {
        let part = document.getElementById('question_part').value;
        let typeDropdown = document.getElementById('question_type');

        let allowedTypes = {
            "Test 1": ["Multiple Choice"],
            "Test 2": ["True/False"],
            "Test 3": ["Fill in the Blanks"],
            "Test 4": ["Essay"]
        };

        // Clear existing options
        typeDropdown.innerHTML = "";

        // Add only allowed question types for the selected part
        allowedTypes[part].forEach(type => {
            let option = document.createElement("option");
            option.value = type;
            option.text = type;
            typeDropdown.appendChild(option);
        });
        
        // Clear existing questions when changing question type
        document.getElementById('questions-container').innerHTML = '';
        questionCounter = 0;
    }

    // Retrieve the existing timer for a given exam part
    function setTimerForExamPart() {
        let examId = document.querySelector('input[name="exam_id"]').value;
        let part = document.querySelector('select[name="question_part"]').value;
        const timerField = document.querySelector('select[name="timer"]');
        
        fetch(`get_timer.php?exam_id=${examId}&question_part=${part}`)
            .then(response => response.json())
            .then(data => {
                const timerMessage = document.getElementById('timer-message');

                if (data.timer !== null) {
                    // Disable all other timer options if a timer is applied
                    Array.from(timerField.options).forEach(option => {
                        if (option.value !== data.timer.toString()) {
                            option.disabled = true;
                        }
                    });

                    // Display message about applied timer
                    timerField.value = data.timer;
                    timerField.setAttribute('readonly', true); // Disable editing the timer
                    timerMessage.innerText = "This timer is applied to all questions in this part.";
                } else {
                    // If no timer applied, enable all options
                    Array.from(timerField.options).forEach(option => {
                        option.disabled = false;
                    });

                    timerField.removeAttribute('readonly'); // Allow editing
                    timerMessage.innerText = "";
                }
            });
    }

    // Validate the form before submission
    function validateForm(event) {
        let valid = true;
        const questionContainers = document.querySelectorAll('.question-container');
        
        if (questionContainers.length === 0) {
            alert('Please add at least one question.');
            valid = false;
        }
        
        const timerSelected = document.querySelector('select[name="timer"]').value !== '';
        if (!timerSelected) {
            document.getElementById('timer-message').textContent = 'Please select a timer duration.';
            valid = false;
        }
        
        // Check total marks
        let totalMarks = 0;
        document.querySelectorAll('.marks-input').forEach(input => {
            if (input.value) {
                totalMarks += parseInt(input.value);
            }
        });
        
        if (totalMarks > <?= $remaining_marks ?>) {
            alert(`Total marks (${totalMarks}) exceed the remaining marks (<?= $remaining_marks ?>).`);
            valid = false;
        }
        
        if (!valid) {
            event.preventDefault();
        }
        
        return valid;
    }

    // Initialize the page
    document.addEventListener("DOMContentLoaded", function() {
        // Set up initial question types
        updateQuestionTypes();
        setTimerForExamPart();
        
        // Add one question by default
        addQuestionContainer();
        
        // Set up event listeners
        document.getElementById('add-question-btn').addEventListener('click', addQuestionContainer);
        document.getElementById('question_part').addEventListener('change', function() {
            updateQuestionTypes();
            setTimerForExamPart();
        });
        document.getElementById('question-form').addEventListener('submit', validateForm);
    });
</script>
<script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>