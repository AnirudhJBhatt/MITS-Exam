<?php
    session_start();
    require_once "../Connection/connection.php";

    header('Content-Type: application/json');

    if (!isset($_POST['exam_id'], $_POST['status'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    $exam_id = (int) $_POST['exam_id'];
    $current_status = (int) $_POST['status'];

    // Toggle logic
    if ($current_status === 1) {
        // UnPublish → NULL
        $sql = "UPDATE exam SET Result_Status = NULL WHERE Exam_ID = ?";
        $new_status = 0;
    } else {
        // Publish → 1
        $sql = "UPDATE exam SET Result_Status = 1 WHERE Exam_ID = ?";
        $new_status = 1;
    }

    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $exam_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'new_status' => $new_status
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database update failed'
        ]);
    }
    $stmt->close();
?>
