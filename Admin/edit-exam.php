<?php
/**
 * MITS Exam Portal - Admin / Faculty Exam Editor
 * Parameterized PDO queries, CSRF validation, and clean AJAX response
 */
require_once "../Connection/connection.php";
require_once "../Common/auth.php";
require_once "../Common/csrf.php";

// Allow Admin, DeptAdmin, or Faculty
if (!isset($_SESSION['LoginAdmin']) && !isset($_SESSION['LoginFaculty']) && !isset($_SESSION['LoginDeptAdmin'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

header('Content-Type: text/html; charset=utf-8');

// Handle POST Update Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    header('Content-Type: application/json; charset=utf-8');
    verify_csrf();

    $exam_id = trim($_POST['Exam_ID'] ?? '');
    $title = trim($_POST['Exam_Name'] ?? '');
    $duration = (int)($_POST['Duration'] ?? 0);
    $start_time = trim($_POST['Start_Time'] ?? '');
    $end_time = trim($_POST['End_Time'] ?? '');
    $status = trim($_POST['Status'] ?? 'published');

    if (!$exam_id || !$title || $duration <= 0 || !$start_time || !$end_time) {
        echo json_encode(['status' => 'error', 'message' => 'All fields including positive duration are required.']);
        exit;
    }

    try {
        // Update exams table (and sync view if needed)
        $stmt = $pdo->prepare("
            UPDATE exams 
            SET Exam_Name = ?, Duration = ?, Start_Time = ?, End_Time = ?, Status = ?
            WHERE Exam_ID = ?
        ");
        $stmt->execute([$title, $duration, $start_time, $end_time, $status, $exam_id]);

        echo json_encode(['status' => 'success', 'message' => 'Exam schedule updated successfully.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update exam: ' . $e->getMessage()]);
    }
    exit;
}

// Handle GET / POST Load Form Action
$exam_id = trim($_REQUEST['Exam_ID'] ?? '');
if (!$exam_id) {
    exit('<div class="alert alert-danger">Invalid exam identifier requested.</div>');
}

$stmt = $pdo->prepare("SELECT * FROM exams WHERE Exam_ID = ? LIMIT 1");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    // Try fallback to legacy exam table
    $stmt = $pdo->prepare("SELECT * FROM exam WHERE Exam_ID = ? LIMIT 1");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$exam) {
    exit('<div class="alert alert-warning">Exam record not found.</div>');
}
?>

<form id="updateExamForm">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="Exam_ID" value="<?= e($exam['Exam_ID']) ?>">

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label fw-semibold">Exam Title <span class="text-danger">*</span></label>
            <input type="text" name="Exam_Name" class="form-control" value="<?= e($exam['Exam_Name']) ?>" required>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold">Duration (Minutes) <span class="text-danger">*</span></label>
            <input type="number" name="Duration" class="form-control" min="5" max="360" value="<?= (int)$exam['Duration'] ?>" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Start Date & Time <span class="text-danger">*</span></label>
            <input type="datetime-local" name="Start_Time" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($exam['Start_Time'])) ?>" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">End Date & Time <span class="text-danger">*</span></label>
            <input type="datetime-local" name="End_Time" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($exam['End_Time'])) ?>" required>
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold">Status</label>
            <select name="Status" class="form-select">
                <option value="draft" <?= ($exam['Status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft (Hidden from students)</option>
                <option value="published" <?= ($exam['Status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published (Active & Visible)</option>
                <option value="completed" <?= ($exam['Status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
        </div>
    </div>

    <div class="modal-footer bg-light mt-4 -mx-3 -mb-3 px-3 py-2 rounded-bottom">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="saveExamBtn">
            <i class="bi bi-save me-1"></i> Update Exam Schedule
        </button>
    </div>
</form>

<script>
$('#updateExamForm').off('submit').on('submit', function (e) {
    e.preventDefault();
    const btn = $('#saveExamBtn');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

    $.ajax({
        url: '../Admin/edit-exam.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function (res) {
            btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Update Exam Schedule');
            if (res.status === 'success') {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    alert(res.message);
                    location.reload();
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', res.message, 'error');
                } else {
                    alert(res.message);
                }
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Update Exam Schedule');
            alert('Server error occurred during update.');
        }
    });
});
</script>
