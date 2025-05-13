<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'connection.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Teacher") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question_id'])) {
    $question_id = (int)$_POST['question_id'];

    // Fetch the exam_id associated with the question
    $fetch_exam_sql = "SELECT exam_id FROM questions WHERE question_id = ?";
    $stmt_fetch_exam = $conn->prepare($fetch_exam_sql);
    $stmt_fetch_exam->bind_param("i", $question_id);
    $stmt_fetch_exam->execute();
    $result = $stmt_fetch_exam->get_result();
    $question = $result->fetch_assoc();
    
    if ($question) {
        // First delete associated choices
        $delete_choices_sql = "DELETE FROM choices WHERE question_id = ?";
        $stmt_choices = $conn->prepare($delete_choices_sql);
        $stmt_choices->bind_param("i", $question_id);
        $stmt_choices->execute();
        
        // Then delete the question
        $delete_sql = "DELETE FROM questions WHERE question_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $question_id);

        if ($stmt->execute()) {
            echo "<script>alert('Question deleted successfully!'); window.location.href='teacher_questions.php?exam_id=" . $question['exam_id'] . "';</script>";
        } else {
            echo "<script>alert('Error deleting question!');</script>";
        }
    } else {
        echo "<script>alert('Question not found!'); window.location.href='teacher_questions.php?exam_id=" . $question['exam_id'] . "';</script>";
    }
} else {
    echo "<script>alert('Invalid request!'); window.location.href='teacher_questions.php?exam_id=" . $question['exam_id'] . "';</script>";
}
?>
