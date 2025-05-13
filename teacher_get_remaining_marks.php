<?php
include 'connection.php';

if (!isset($_GET['exam_id'])) {
    echo json_encode(["error" => "Exam ID is required"]);
    exit();
}

$exam_id = (int)$_GET['exam_id'];

// Get total marks of the exam
$sql_total = "SELECT total_marks FROM exams WHERE exam_id = ?";
$stmt_total = $conn->prepare($sql_total);
$stmt_total->bind_param("i", $exam_id);
$stmt_total->execute();
$stmt_total->bind_result($total_marks);
$stmt_total->fetch();
$stmt_total->close();

// Get the sum of current marks from the questions table
$sql_current = "SELECT COALESCE(SUM(marks), 0) FROM questions WHERE exam_id = ?";
$stmt_current = $conn->prepare($sql_current);
$stmt_current->bind_param("i", $exam_id);
$stmt_current->execute();
$stmt_current->bind_result($current_marks);
$stmt_current->fetch();
$stmt_current->close();

// Calculate remaining marks
$remaining_marks = $total_marks - $current_marks;

echo json_encode(["remaining_marks" => $remaining_marks]);
?>
