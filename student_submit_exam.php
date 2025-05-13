<?php
require 'connection.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if student is logged in
if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Student") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

// Validate exam_id
if (!isset($_GET['exam_id'])) {
    echo '<script>alert("Invalid exam."); window.location.href = "student_subjects.php";</script>';
    exit();
}

$exam_id = mysqli_real_escape_string($conn, $_GET['exam_id']);
$student_id = $_SESSION["user_id"];

// Fetch exam details
$exam_query = "SELECT * FROM exams WHERE exam_id = '$exam_id'";
$exam_result = mysqli_query($conn, $exam_query);
$exam = mysqli_fetch_assoc($exam_result);

if (!$exam) {
    echo '<script>alert("Exam not found."); window.location.href = "student_subjects.php";</script>';
    exit();
}

// Retrieve stored answers from session
$student_answers = isset($_SESSION["exam_answers_$exam_id"]) ? $_SESSION["exam_answers_$exam_id"] : [];
$total_score = 0;
$total_possible_score = 0;

// Process each question in the exam
$questions_query = "SELECT * FROM questions WHERE exam_id = '$exam_id'";
$questions_result = mysqli_query($conn, $questions_query);

while ($question = mysqli_fetch_assoc($questions_result)) {
    $question_id = $question['question_id'];
    $question_type = $question['question_type'];
    $marks = $question['marks'];
    $is_correct = 0;
    $total_possible_score += $marks;
    
    // Retrieve correct answers based on question type
    if ($question_type === 'True/False') {
        // Fetch the correct True/False choice
        $correct_query = "SELECT choice_id, choice_text FROM choices WHERE question_id = '$question_id' AND is_correct = 1";
        $correct_result = mysqli_query($conn, $correct_query);
        $correct_choice_data = mysqli_fetch_assoc($correct_result);
    
        if (!$correct_choice_data) {
            continue; // Skip if no correct answer found
        }
    
        // Get the correct answer text (True or False)
        $correct_answer_text = $correct_choice_data['choice_text'];
        
        // Get student's answer
        $student_answer = isset($student_answers[$question_id]) ? $student_answers[$question_id] : "";
        
        // Debug information if needed
        // error_log("Q$question_id: Student answer: '$student_answer', Correct: '$correct_answer_text'");
        
        // Compare the text values directly (True/False)
        if ($student_answer === $correct_answer_text) {
            $is_correct = 1;
            $total_score += $marks;
        }
    } elseif ($question_type === 'Multiple Choice') {
        // Fetch correct choices
        $correct_query = "SELECT choice_id FROM choices WHERE question_id = '$question_id' AND is_correct = 1";
        $correct_result = mysqli_query($conn, $correct_query);
        $correct_choices = [];
        while ($row = mysqli_fetch_assoc($correct_result)) {
            $correct_choices[] = (string)$row['choice_id']; // Convert to string for consistent comparison
        }

        // Check if the answer is correct for single-choice or multiple-choice questions
        if (isset($student_answers[$question_id])) {
            if (is_array($student_answers[$question_id])) {
                // Multiple correct answers scenario
                $student_answer_array = array_map('strval', $student_answers[$question_id]); // Convert all to strings
                sort($student_answer_array);
                sort($correct_choices);
                if ($student_answer_array === $correct_choices) {
                    $is_correct = 1;
                    $total_score += $marks;
                }
            } else {
                // Single correct answer scenario
                $student_answer = (string)$student_answers[$question_id];
                if (in_array($student_answer, $correct_choices)) {
                    $is_correct = 1;
                    $total_score += $marks;
                }
            }
        }
    } elseif ($question_type === 'Fill in the Blanks' || $question_type === 'Enumeration') {
        // Fetch all correct answer variations
        $correct_query = "SELECT choice_text FROM choices WHERE question_id = '$question_id' AND is_correct = 1";
        $correct_result = mysqli_query($conn, $correct_query);
        $correct_texts = [];
        while ($row = mysqli_fetch_assoc($correct_result)) {
            $correct_texts[] = strtolower(trim($row['choice_text']));
        }

        $student_answer = isset($student_answers[$question_id]) ? strtolower(trim($student_answers[$question_id])) : "";
        if (in_array($student_answer, $correct_texts)) {
            $is_correct = 1;
            $total_score += $marks;
        }
    }
    
    // Store student answer in database
    if (isset($student_answers[$question_id])) {
        $answer_text = '';
        
        // Handle different types of answers (array, string)
        if (is_array($student_answers[$question_id])) {
            $answer_text = implode(',', array_map(function($val) use ($conn) {
                return mysqli_real_escape_string($conn, $val);
            }, $student_answers[$question_id]));
        } else {
            $answer_text = mysqli_real_escape_string($conn, $student_answers[$question_id]);
        }
        
        $insert_answer = "INSERT INTO student_answers (student_id, exam_id, question_id, answer_text, is_correct, marks_obtained) 
                          VALUES ('$student_id', '$exam_id', '$question_id', '$answer_text', '$is_correct', IF('$is_correct' = 1, '$marks', 0)) 
                          ON DUPLICATE KEY UPDATE answer_text='$answer_text', is_correct='$is_correct', marks_obtained=IF('$is_correct' = 1, '$marks', 0)";
        mysqli_query($conn, $insert_answer);
    }
}

// Update exam submission status
$update_submission = "INSERT INTO exam_submissions (student_id, exam_id, submit_time, taken) 
                      VALUES ('$student_id', '$exam_id', NOW(), 1) 
                      ON DUPLICATE KEY UPDATE submit_time=NOW(), taken=1";
mysqli_query($conn, $update_submission);

// Calculate percentage score
$percentage = ($total_possible_score > 0) ? round(($total_score / $total_possible_score) * 100, 2) : 0;

// Clear session data for this exam
unset($_SESSION["exam_answers_$exam_id"]);

// Clear all part-specific timers for this exam
foreach ($_SESSION as $key => $value) {
    if (strpos($key, "exam_{$exam_id}_part_") === 0) {
        unset($_SESSION[$key]);
    }
}

// Redirect to results page
header("Location: student_done_exam.php?exam_id=$exam_id&score=$total_score&percentage=$percentage&total=$total_possible_score");
exit();
?>