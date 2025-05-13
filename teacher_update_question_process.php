<?php
include 'connection.php';

$question_id = (int)$_POST['question_id'];
$question_text = $_POST['question_text'];
$marks = (int)$_POST['marks'];

// Update the question itself
$sql = "UPDATE questions SET question_text = ?, marks = ? WHERE question_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $question_text, $marks, $question_id);
$stmt->execute();

// Update choices or answers
if (isset($_POST['choices'])) {
    foreach ($_POST['choices'] as $choice_id => $choice_text) {
        $is_correct = (in_array($choice_id, $_POST['correct_choices'] ?? [])) ? 1 : 0;
        $sql = "UPDATE choices SET choice_text = ?, is_correct = ? WHERE choice_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $choice_text, $is_correct, $choice_id);
        $stmt->execute();
    }
} elseif (isset($_POST['fill_answers'])) {
    foreach ($_POST['fill_answers'] as $choice_id => $answer_text) {
        $sql = "UPDATE choices SET choice_text = ? WHERE choice_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $answer_text, $choice_id);
        $stmt->execute();
    }
}

echo "<script>alert('Question updated successfully'); window.location.href='teacher_questions.php';</script>";
?>
