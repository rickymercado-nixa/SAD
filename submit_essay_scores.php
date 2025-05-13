<?php
require 'connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['marks'])) {
    foreach ($_POST['marks'] as $studans_id => $score) {
        $stmt = $conn->prepare("UPDATE student_answers SET marks_obtained = ?, is_correct = 1 WHERE students_answer_id = ?");
        $stmt->bind_param("ii", $score, $studans_id);
        $stmt->execute();
    }

    $exam_id = $_POST['exam_id'];
    echo "<script>alert('Essay scores submitted successfully!'); window.location.href='teacher_check_essay.php?exam_id=$exam_id';</script>";
    exit();
}
?>
