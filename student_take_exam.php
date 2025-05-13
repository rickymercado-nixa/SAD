<?php 
require 'connection.php';

// Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle form submission - IMPORTANT part for saving answers
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_answers'])) {
    if (isset($_POST['exam_id'])) {
        $exam_id = $_POST['exam_id'];
        
        // Initialize session array if it doesn't exist
        if (!isset($_SESSION["exam_answers_$exam_id"])) {
            $_SESSION["exam_answers_$exam_id"] = [];
        }
        
        // Save the current answers to session BEFORE redirecting
        if (isset($_POST['answer']) && is_array($_POST['answer'])) {
            foreach ($_POST['answer'] as $q_id => $answer) {
                $_SESSION["exam_answers_$exam_id"][$q_id] = $answer;
            }
        }
        
        // Redirect to requested part
        $next_part = isset($_POST['next_part']) ? $_POST['next_part'] : '';
        header("Location: student_take_exam.php?exam_id=$exam_id&part=" . urlencode($next_part));
        exit();
    }
}

// Ajax endpoint for saving answers without redirecting
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_save'])) {
    if (isset($_POST['exam_id']) && isset($_POST['answers']) && is_array($_POST['answers'])) {
        $exam_id = $_POST['exam_id'];
        
        if (!isset($_SESSION["exam_answers_$exam_id"])) {
            $_SESSION["exam_answers_$exam_id"] = [];
        }
        
        foreach ($_POST['answers'] as $q_id => $answer) {
            $_SESSION["exam_answers_$exam_id"][$q_id] = $answer;
        }
        
        echo json_encode(['success' => true]);
        exit();
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Student") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

if (!isset($_GET['exam_id'])) {
    echo '<script>alert("Invalid exam."); window.location.href = "student_subjects.php";</script>';
    exit();
}

$exam_id = mysqli_real_escape_string($conn, $_GET['exam_id']);
$student_id = $_SESSION["user_id"];

$exam_query = "SELECT * FROM exams WHERE exam_id = '$exam_id'";
$exam_result = mysqli_query($conn, $exam_query);
$exam = mysqli_fetch_assoc($exam_result);

if (!$exam) {
    echo '<script>alert("Exam not found."); window.location.href = "student_subjects.php";</script>';
    exit();
}

// Get all distinct question parts for this exam
$parts_query = "SELECT DISTINCT question_part FROM questions WHERE exam_id = '$exam_id' ORDER BY question_part";
$parts_result = mysqli_query($conn, $parts_query);
$exam_parts = [];
while ($part = mysqli_fetch_assoc($parts_result)) {
    $exam_parts[] = $part['question_part'];
}

// If no parts found, handle error
if (count($exam_parts) == 0) {
    echo "<p>No questions found for this exam.</p>";
    exit();
}

// Determine current part (default to first part if not specified)
$current_part = isset($_GET['part']) && in_array($_GET['part'], $exam_parts) ? $_GET['part'] : $exam_parts[0];

// Get the timer for the current part
$timer_query = "SELECT * FROM exam_part_timers WHERE exam_id = '$exam_id' AND question_part = '" . mysqli_real_escape_string($conn, $current_part) . "'";
$timer_result = mysqli_query($conn, $timer_query);
$part_timer = mysqli_fetch_assoc($timer_result);

if (!$part_timer) {
    echo "<p>Timer not found for this part.</p>";
    exit();
}

// Check if this is the first time accessing this part and set start time if so
$part_session_key = "exam_{$exam_id}_part_{$current_part}_start_time";
if (!isset($_SESSION[$part_session_key])) {
    $_SESSION[$part_session_key] = time();
}

// Calculate remaining time for current part
$part_start_time = $_SESSION[$part_session_key];
$part_duration = $part_timer['timer'] * 60; // Convert minutes to seconds
$part_end_time = $part_start_time + $part_duration;
$remaining_time = $part_end_time - time();

// If time's up for this part, move to the next part or submit
if ($remaining_time <= 0) {
    // Find next part index
    $current_index = array_search($current_part, $exam_parts);
    $next_part = ($current_index < count($exam_parts) - 1) ? $exam_parts[$current_index + 1] : null;
    
    if ($next_part) {
        header("Location: student_take_exam.php?exam_id=$exam_id&part=" . urlencode($next_part));
    } else {
        header("Location: student_submit_exam.php?exam_id=$exam_id");
    }
    exit();
}

// Get questions for current part
$questions_query = "SELECT * FROM questions WHERE exam_id = '$exam_id' AND question_part = '" . mysqli_real_escape_string($conn, $current_part) . "' ORDER BY RAND()";
$questions_result = mysqli_query($conn, $questions_query);
$questions = [];
while ($row = mysqli_fetch_assoc($questions_result)) {
    $questions[] = $row;
}

// Make sure we have the session answers
$stored_answers = isset($_SESSION["exam_answers_$exam_id"]) ? $_SESSION["exam_answers_$exam_id"] : [];

// DEBUG - For troubleshooting, uncomment these lines
// echo "<pre>DEBUG Session Data: ";
// print_r($stored_answers);
// echo "</pre>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($exam['exam_name']); ?> - Take Exam</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <style>
        .part-nav {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .part-nav .btn {
            margin: 0 5px;
        }
        .part-nav .btn.active {
            background-color: #0d6efd;
            color: white;
        }
        #fullscreenWarning {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: #dc3545;
            color: white;
            text-align: center;
            padding: 10px;
            z-index: 1000;
        }
        .answer-status {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 12px;
        }
        .timer-warning {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div id="fullscreenWarning">
        You exited full-screen mode. This is being recorded.
        <button onclick="enterFullScreen()" class="btn btn-sm btn-light">Return to Full Screen</button>
    </div>

    <div class="container mt-4">
        <h1><?php echo htmlspecialchars($exam['exam_name']); ?></h1>
        <div class="d-flex justify-content-between align-items-center">
            <p><strong>Current Part:</strong> <?php echo htmlspecialchars($current_part); ?></p>
            <p><strong>Time Left for This Part:</strong> <span id="timer" class="fw-bold"></span></p>
        </div>
        <div id="saveStatus" class="alert alert-success" style="display:none;">Answers saved</div>
        
        <!-- Part Navigation -->
        <div class="part-nav">
            <?php foreach ($exam_parts as $part) { 
                $active = ($part == $current_part) ? 'active' : '';
                // Check if this part has been started already
                $part_started = isset($_SESSION["exam_{$exam_id}_part_{$part}_start_time"]);
                $status_class = $part_started ? 'btn-outline-success' : 'btn-outline-primary';
                if ($active) $status_class = 'active';
            ?>
            <button type="button" onclick="navigateToPart('<?php echo htmlspecialchars($part); ?>')" 
                class="btn <?php echo $status_class; ?> <?php echo $active; ?>">
                <?php echo htmlspecialchars($part); ?>
            </button>
            <?php } ?>
        </div>

        <h3 class="mt-3 mb-4"><?php echo htmlspecialchars($current_part); ?></h3>
        
        <form id="examForm" method="POST">
            <input type="hidden" name="save_answers" value="1">
            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
            <input type="hidden" name="next_part" id="nextPartInput" value="">
            
            <div class="row">
                <?php foreach ($questions as $index => $question) { 
                    $question_id = $question['question_id'];
                    $has_answer = isset($stored_answers[$question_id]) && !empty($stored_answers[$question_id]);
                ?>
                <div class="col-md-6 mb-4">
                    <div class="card p-3 position-relative">
                        <?php if ($has_answer): ?>
                        <span class="answer-status badge bg-success">Answered</span>
                        <?php endif; ?>
                        
                        <p><strong>Question <?php echo $index + 1; ?>:</strong> <?php echo htmlspecialchars($question['question_text']); ?></p>
                        
                        <?php if ($question['question_type'] == 'Multiple Choice') { 
                            $choices_query = "SELECT * FROM choices WHERE question_id = " . $question['question_id'];
                            $choices_result = mysqli_query($conn, $choices_query);
                            while ($choice = mysqli_fetch_assoc($choices_result)) { 
                                $is_checked = isset($stored_answers[$question_id]) && $stored_answers[$question_id] == $choice['choice_id'];
                            ?>
                                <div class="form-check">
                                    <input class="form-check-input answer-input" type="radio" 
                                        name="answer[<?php echo $question['question_id']; ?>]" 
                                        value="<?php echo $choice['choice_id']; ?>" 
                                        <?php echo $is_checked ? 'checked' : ''; ?>>
                                    <label class="form-check-label"> <?php echo htmlspecialchars($choice['choice_text']); ?> </label>
                                </div>
                            <?php } 
                        } ?>

                        <?php if ($question['question_type'] == 'True/False') { 
                            $true_checked = isset($stored_answers[$question_id]) && $stored_answers[$question_id] == 'True';
                            $false_checked = isset($stored_answers[$question_id]) && $stored_answers[$question_id] == 'False';
                        ?>
                            <div class="form-check">
                                <input class="form-check-input answer-input" type="radio" 
                                    name="answer[<?php echo $question['question_id']; ?>]" 
                                    value="True"
                                    <?php echo $true_checked ? 'checked' : ''; ?>>
                                <label class="form-check-label">True</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input answer-input" type="radio" 
                                    name="answer[<?php echo $question['question_id']; ?>]" 
                                    value="False"
                                    <?php echo $false_checked ? 'checked' : ''; ?>>
                                <label class="form-check-label">False</label>
                            </div>
                        <?php } ?>

                        <?php if ($question['question_type'] == 'Fill in the Blanks') { 
                            $current_answer = isset($stored_answers[$question_id]) ? $stored_answers[$question_id] : '';
                        ?>
                            <input type="text" class="form-control answer-input" 
                                name="answer[<?php echo $question['question_id']; ?>]" 
                                value="<?php echo htmlspecialchars($current_answer); ?>">
                        <?php } ?>
                        
                        <?php if ($question['question_type'] == 'Essay') { 
                            $current_answer = isset($stored_answers[$question_id]) ? $stored_answers[$question_id] : '';
                        ?>
                            <textarea class="form-control answer-input" 
                                name="answer[<?php echo $question['question_id']; ?>]" 
                                rows="4" placeholder="Type your answer here..."><?php echo htmlspecialchars($current_answer); ?></textarea>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
            </div>
            
            <div class="d-flex justify-content-between mt-4 mb-5">
                <?php 
                // Find previous and next part indexes
                $current_index = array_search($current_part, $exam_parts);
                $prev_part = ($current_index > 0) ? $exam_parts[$current_index - 1] : null;
                $next_part = ($current_index < count($exam_parts) - 1) ? $exam_parts[$current_index + 1] : null;
                ?>
                
                <?php if ($prev_part): ?>
                <button type="button" onclick="navigateToPart('<?php echo htmlspecialchars($prev_part); ?>')" class="btn btn-secondary">
                    &laquo; Previous: <?php echo htmlspecialchars($prev_part); ?>
                </button>
                <?php else: ?>
                <div></div> <!-- Empty div for flex spacing -->
                <?php endif; ?>
                
                <?php if ($next_part): ?>
                <button type="button" onclick="navigateToPart('<?php echo htmlspecialchars($next_part); ?>')" class="btn btn-primary">
                    Next: <?php echo htmlspecialchars($next_part); ?> &raquo;
                </button>
                <?php else: ?>
                <button type="button" onclick="submitFinalExam()" class="btn btn-success">
                    Submit Exam
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script>
    // Part-specific timer
    let partEndTime = <?php echo $part_end_time; ?>;
    let timerDisplay = document.getElementById("timer");
    let tabSwitchCount = 0;
    let saveStatus = document.getElementById("saveStatus");
    let answerInputs = document.querySelectorAll('.answer-input');
    
    // Add event listeners to all answer inputs to save on change
    answerInputs.forEach(input => {
        input.addEventListener('change', saveAnswersAjax);
    });

    // Save answers via AJAX without page reload
    function saveAnswersAjax() {
        let formData = new FormData(document.getElementById('examForm'));
        
        // Convert form data to a format suitable for AJAX
        let answers = {};
        for (let pair of formData.entries()) {
            let key = pair[0];
            if (key.startsWith('answer[') && key.endsWith(']')) {
                // Extract question ID from key
                let questionId = key.substring(7, key.length - 1);
                answers[questionId] = pair[1];
            }
        }
        
        // Use fetch API to save answers
        fetch('student_take_exam.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'ajax_save': '1',
                'exam_id': '<?php echo $exam_id; ?>',
                'answers': JSON.stringify(answers)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show save status briefly
                saveStatus.style.display = 'block';
                setTimeout(() => {
                    saveStatus.style.display = 'none';
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error saving answers:', error);
        });
    }

    // Navigate to a different part
    function navigateToPart(part) {
        // Save current answers first
        saveAnswersBeforeNavigation(part);
    }

    // Save answers before navigation
    function saveAnswersBeforeNavigation(nextPart) {
        let form = document.getElementById('examForm');
        document.getElementById('nextPartInput').value = nextPart;
        form.submit();
    }

    function updateTimer() {
        let now = Math.floor(Date.now() / 1000);
        let remaining = partEndTime - now;

        if (remaining <= 0) {
            // Get next part information
            <?php if ($next_part): ?>
            window.location.href = "student_take_exam.php?exam_id=<?php echo $exam_id; ?>&part=<?php echo urlencode($next_part); ?>";
            <?php else: ?>
            window.location.href = "student_submit_exam.php?exam_id=<?php echo $exam_id; ?>";
            <?php endif; ?>
            return;
        }

        let minutes = Math.floor(remaining / 60);
        let seconds = remaining % 60;
        
        // Show warning when less than 2 minutes remain
        if (remaining < 120) {
            timerDisplay.classList.add('timer-warning');
        }
        
        timerDisplay.innerText = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
        setTimeout(updateTimer, 1000);
    }

    window.onload = function() {
        updateTimer();
        // Request fullscreen on page load
        if (!document.fullscreenElement) {
            enterFullScreen();
        }
        
        // Auto-save answers every 30 seconds
        setInterval(saveAnswersAjax, 30000);
    };

    function submitFinalExam() {
        // Save current answers first
        let formData = new FormData(document.getElementById('examForm'));
        
        // Use fetch to save current answers asynchronously
        fetch('student_take_exam.php', {
            method: 'POST',
            body: formData
        }).then(function() {
            // Then redirect to submit page
            window.location.href = "student_submit_exam.php?exam_id=<?php echo $exam_id; ?>";
        });
    }

    function enterFullScreen() {
        let elem = document.documentElement;

        if (elem.requestFullscreen) {
            elem.requestFullscreen().catch(err => {
                console.warn("Fullscreen request failed", err);
            });
        } else if (elem.mozRequestFullScreen) {
            elem.mozRequestFullScreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }

        document.getElementById("fullscreenWarning").style.display = "none";
    }

    document.addEventListener("fullscreenchange", function() {
        if (!document.fullscreenElement) {
            fetch("log_cheating.php", {
                method: "POST",
                body: JSON.stringify({
                    event: "Exited full screen mode",
                    exam_id: <?php echo $exam_id; ?>,
                    student_id: <?php echo $student_id; ?>
                }),
                headers: {
                    "Content-Type": "application/json"
                }
            });
            alert("Warning: You exited full-screen mode. This is being recorded.");
            document.getElementById("fullscreenWarning").style.display = "block";
        }
    });

    document.addEventListener("visibilitychange", function() {
        if (document.hidden) {
            tabSwitchCount++;
            fetch("log_cheating.php", {
                method: "POST",
                body: JSON.stringify({
                    event: "Tab switch detected",
                    count: tabSwitchCount,
                    exam_id: <?php echo $exam_id; ?>,
                    student_id: <?php echo $student_id; ?>
                }),
                headers: {
                    "Content-Type": "application/json"
                }
            });

            alert("Warning: You switched tabs. This is being recorded.");
            document.getElementById("fullscreenWarning").style.display = "block";
        }
    });
    </script>
</body>
</html>