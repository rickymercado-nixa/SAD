<?php 
require 'connection.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Student") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

if (!isset($_POST['exam_id']) || empty($_POST['exam_id'])) {
    echo '<script>alert("Invalid exam."); window.location.href = "student_subjects.php";</script>';
    exit();
}

$student_id = $_SESSION['user_id'];
$exam_id = mysqli_real_escape_string($conn, $_POST['exam_id']);

if (!isset($_POST['answer']) || empty($_POST['answer'])) {
    echo '<script>alert("No answers submitted."); window.location.href = "student_subjects.php";</script>';
    exit();
}

$answers = $_POST['answer'];

// Check if the exam exists
$exam_check_query = "SELECT * FROM exams WHERE exam_id = '$exam_id'";
$exam_check_result = mysqli_query($conn, $exam_check_query);
if (mysqli_num_rows($exam_check_result) == 0) {
    echo '<script>alert("Exam does not exist."); window.location.href = "student_subjects.php";</script>';
    exit();
}

// Check if the student has already submitted
$check_submission = "SELECT * FROM exam_submissions WHERE student_id = '$student_id' AND exam_id = '$exam_id'";
$result = mysqli_query($conn, $check_submission);
if (mysqli_num_rows($result) > 0) {
    echo '<script>alert("You have already submitted this exam."); window.location.href = "student_dashboard.php";</script>';
    exit();
}

$total_marks = 0;
foreach ($answers as $question_id => $answer) {
    $question_id = mysqli_real_escape_string($conn, $question_id);
    $answer = mysqli_real_escape_string($conn, $answer);

    // Fetch the correct answer
    $correct_query = "SELECT * FROM questions WHERE question_id = '$question_id'";
    $correct_result = mysqli_query($conn, $correct_query);
    $question = mysqli_fetch_assoc($correct_result);

    $is_correct = 0;
    $marks_obtained = 0;

    if ($question['question_type'] == 'Multiple Choice' || $question['question_type'] == 'True/False') {
        $correct_answer_query = "SELECT * FROM choices WHERE question_id = '$question_id' AND is_correct = 1";
        $correct_answer_result = mysqli_query($conn, $correct_answer_query);
        $correct_choice = mysqli_fetch_assoc($correct_answer_result);

        if ($answer == $correct_choice['choice_id']) {
            $is_correct = 1;
            $marks_obtained = $question['marks'];
        }
    } elseif ($question['question_type'] == 'Fill in the Blanks') {
        if (strtolower(trim($answer)) == strtolower(trim($question['correct_answer']))) {
            $is_correct = 1;
            $marks_obtained = $question['marks'];
        }
    }

    // Insert student answer
    $insert_answer = "INSERT INTO student_answers (student_id, exam_id, question_id, answer_text, is_correct, marks_obtained) 
                      VALUES ('$student_id', '$exam_id', '$question_id', '$answer', '$is_correct', '$marks_obtained')";
    mysqli_query($conn, $insert_answer);

    $total_marks += $marks_obtained;
}

// Insert submission record
$submit_time = date("Y-m-d H:i:s");
$insert_submission = "INSERT INTO exam_submissions (student_id, exam_id, submit_time, status) 
                      VALUES ('$student_id', '$exam_id', '$submit_time', 'Submitted')";
if (!mysqli_query($conn, $insert_submission)) {
    echo '<script>alert("Error submitting exam. Please try again."); window.location.href = "student_dashboard.php";</script>';
    exit();
}

echo '<script>alert("Exam submitted successfully!"); window.location.href = "student_dashboard.php";</script>';
?>
