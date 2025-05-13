<?php
session_start();
if (!isset($_POST['exam_id']) || !isset($_POST['answer'])) {
    exit();
}

$exam_id = $_POST['exam_id'];
if (!isset($_SESSION['exam_answers'][$exam_id])) {
    $_SESSION['exam_answers'][$exam_id] = [];
}

// Save each answer in the session
foreach ($_POST['answer'] as $question_id => $answer) {
    $_SESSION['exam_answers'][$exam_id][$question_id] = $answer;
}

echo "Answer saved.";
?>
