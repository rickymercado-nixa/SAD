<?php
include 'connection.php';

$exam_id = $_GET['exam_id'];
$question_part = $_GET['question_part'];

$sql = "SELECT timer FROM exam_part_timers WHERE exam_id = ? AND question_part = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $exam_id, $question_part);
$stmt->execute();
$stmt->bind_result($timer);
$stmt->fetch();
$stmt->close();

echo json_encode(["timer" => $timer]);
?>
