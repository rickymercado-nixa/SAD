<?php 
require 'connection.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Student") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

if (!isset($_GET['exam_id'])) {
    echo '<script>alert("Invalid exam."); window.location.href = "student_subjects.php";</script>';
    exit();
}

$exam_id = mysqli_real_escape_string($conn, $_GET['exam_id']);

// Store submitted answer in session
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['answer'])) {
    foreach ($_POST['answer'] as $question_id => $selected_answer) {
        $_SESSION['exam_answers'][$exam_id][$question_id] = $selected_answer;
    }
}

// Fetch exam details
$exam_query = "SELECT * FROM exams WHERE exam_id = '$exam_id'";
$exam_result = mysqli_query($conn, $exam_query);
$exam = mysqli_fetch_assoc($exam_result);

if (!$exam) {
    echo '<script>alert("Exam not found."); window.location.href = "student_subjects.php";</script>';
    exit();
}

// Fetch all questions and store in session if not already stored
if (!isset($_SESSION['exam_questions'][$exam_id])) {
    $questions_query = "SELECT * FROM questions WHERE exam_id = '$exam_id' ORDER BY RAND()";
    $questions_result = mysqli_query($conn, $questions_query);

    $_SESSION['exam_questions'][$exam_id] = [];
    while ($question = mysqli_fetch_assoc($questions_result)) {
        $_SESSION['exam_questions'][$exam_id][] = $question;
    }
}

// Get total questions
$total_questions = count($_SESSION['exam_questions'][$exam_id]);

$current_question_id = isset($_GET['question_id']) ? (int) $_GET['question_id'] : 0;
$current_index = 0;

foreach ($_SESSION['exam_questions'][$exam_id] as $index => $question) {
    if ($question['question_id'] == $current_question_id) {
        $current_index = $index;
        break;
    }
}

// Get the current question
$current_question = $_SESSION['exam_questions'][$exam_id][$current_index];

// Get saved answer if exists
$saved_answer = $_SESSION['exam_answers'][$exam_id][$current_question['question_id']] ?? null;

if (!isset($_SESSION['exam_start_time'][$exam_id])) {
    $_SESSION['exam_start_time'][$exam_id] = time(); // Store exam start time
}

$exam_duration = $exam['duration'] * 60; // Convert minutes to seconds
$start_time = $_SESSION['exam_start_time'][$exam_id];
$end_time = $start_time + $exam_duration;
$_SESSION['exam_end_time'][$exam_id] = $end_time;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($exam['exam_name']); ?> - Take Exam</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-4">
        <h1><?php echo htmlspecialchars($exam['exam_name']); ?></h1>

        <p><strong>Time Left:</strong> <span id="timer" style="display: none;"></span></p>

        <button id="fullscreenWarning" onclick="enterFullScreen()" class="btn btn-warning"
            style="display: none; position: fixed; top: 10px; right: 10px; z-index: 9999;">
            Re-enter Full Screen
        </button>


 <form method="POST" action="student_take_exam.php?exam_id=<?php echo $exam_id; ?>&question_id=<?php echo $current_question['question_id']; ?>">
            <div class="mb-3">
                <p><strong>Question <?php echo $current_index + 1; ?> of <?php echo $total_questions; ?>:</strong></p>
                <p><?php echo htmlspecialchars($current_question['question_text']); ?></p>

                <?php if ($current_question['question_type'] == 'Multiple Choice') { 
                    $choices_query = "SELECT * FROM choices WHERE question_id = " . $current_question['question_id'];
                    $choices_result = mysqli_query($conn, $choices_query);
                    while ($choice = mysqli_fetch_assoc($choices_result)) { ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio"
                                name="answer[<?php echo $current_question['question_id']; ?>]"
                                value="<?php echo $choice['choice_id']; ?>"
                                <?php echo ($saved_answer == $choice['choice_id']) ? 'checked' : ''; ?> required>
                            <label class="form-check-label"><?php echo htmlspecialchars($choice['choice_text']); ?></label>
                        </div>
                <?php } } ?>

                <?php if ($current_question['question_type'] == 'True/False') { ?>
                <div class="form-check">
                    <input class="form-check-input" type="radio"
                        name="answer[<?php echo $current_question['question_id']; ?>]" value="True"
                        <?php echo ($saved_answer == 'True') ? 'checked' : ''; ?> required>
                    <label class="form-check-label">True</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio"
                        name="answer[<?php echo $current_question['question_id']; ?>]" value="False"
                        <?php echo ($saved_answer == 'False') ? 'checked' : ''; ?> required>
                    <label class="form-check-label">False</label>
                </div>
                <?php } ?>

                <?php if ($current_question['question_type'] == 'Fill in the Blanks') { ?>
                <input type="text" class="form-control" 
                    name="answer[<?php echo $current_question['question_id']; ?>]"
                    value="<?php echo htmlspecialchars($saved_answer ?? ''); ?>" required>
                <?php } ?>
            </div>

            <div class="d-flex justify-content-between">
                <?php if ($current_index > 0) { ?>
                <button type="submit" name="prev" class="btn btn-secondary">Previous</button>
                <?php } ?>

                <?php if ($current_index < $total_questions - 1) { ?>
                <button type="submit" name="next" class="btn btn-primary">Next</button>
                <?php } else { ?>
                <button type="submit" name="submit_exam" class="btn btn-success">Submit Exam</button>
                <?php } ?>
            </div>
        </form>
    </div>
    <script>
    let examEndTime = <?php echo $_SESSION['exam_end_time'][$exam_id]; ?>;
    let tabSwitchCount = 0;

    function updateTimer() {
        let now = Math.floor(Date.now() / 1000);
        let remaining = examEndTime - now;

        if (remaining <= 0) {
            document.getElementById("examForm").submit();
        }

        document.getElementById("timer").innerText = new Date(remaining * 1000).toISOString().substr(14, 5);
        document.getElementById("timeRemaining").value = remaining;

        setTimeout(updateTimer, 1000);
    }

    function startExam() {
        let startBtn = document.getElementById("startExamBtn");
        let timerDisplay = document.getElementById("timer");

        if (startBtn) {
            startBtn.style.display = "none"; // Hide start button
        }
        if (timerDisplay) {
            timerDisplay.style.display = "inline"; // Show timer
        }

        document.documentElement.requestFullscreen(); // Enforce full screen
        updateTimer();
    }

    window.onload = function() {
        document.getElementById("startExamBtn").style.display = "none"; // Hide Start button
        document.getElementById("timer").style.display = "inline"; // Show Timer
        updateTimer();

        // Show full-screen warning button if the user isn't in full-screen mode
        if (!document.fullscreenElement) {
            document.getElementById("fullscreenWarning").style.display = "block";
        }
    };


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

        // Hide the warning button after re-entering full screen
        document.getElementById("fullscreenWarning").style.display = "none";
    }


    document.addEventListener("fullscreenchange", function() {
        if (!document.fullscreenElement) {
            fetch("log_cheating.php", {
                method: "POST",
                body: JSON.stringify({
                    event: "Exited full screen mode"
                }),
                headers: {
                    "Content-Type": "application/json"
                }
            });
            alert("Warning: You exited full-screen mode. This is being recorded.");
            document.getElementById("fullscreenWarning").style.display = "block"; // Show warning button
        }
    });


    // Detect tab switching (cheating prevention)
    document.addEventListener("visibilitychange", function() {
        if (document.hidden) {
            tabSwitchCount++;
            fetch("log_cheating.php", {
                method: "POST",
                body: JSON.stringify({
                    event: "Tab switch detected",
                    count: tabSwitchCount
                }),
                headers: {
                    "Content-Type": "application/json"
                }
            });

            alert("Warning: You switched tabs. This is being recorded.");
            document.getElementById("fullscreenWarning").style.display = "block"; // Show warning button
        }
    });
    </script>
</body>
</html>