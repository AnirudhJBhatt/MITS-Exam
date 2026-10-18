<?php
// Backward compatibility forwarder to download-qp.php
$examId = $_REQUEST['Exam_ID'] ?? $_REQUEST['exam_id'] ?? '';
if ($examId) {
    header("Location: download-qp.php?Exam_ID=" . urlencode($examId));
    exit;
}
header("Location: manage-exams.php");
exit;