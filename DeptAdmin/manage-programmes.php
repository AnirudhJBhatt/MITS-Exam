<?php  
    session_start();
    if (!$_SESSION["LoginDeptAdmin"]) {
        echo '<script>alert("Unauthorized access!");</script>';
        echo '<script>window.location="../index.php"</script>';
    }
    
    $Dept_ID=$_SESSION['LoginDeptAdmin'];

    require_once "../Connection/connection.php";
    function selected($field, $value) {
        return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
    }

?>

<?php
    // Insert Programme
    if (isset($_POST['Add_Course'])) {
        $Prog_Name = $_POST['Prog_Name'];
        $query1 = "INSERT INTO programmes (Dept_ID, Prog_Name) VALUES ('$Dept_ID', '$Prog_Name')";
        $run1 = mysqli_query($con, $query1);

        if (!$run1) {
            // echo "<script>alert('Some records failed to insert!'); window.location='manage-programmes.php';</script>";
            // exit;
            echo "Error: " . mysqli_error($con);
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dept Admin - Manage Programmes</title>
    <!-- Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../Common/style.css">
</head>

<body>
    <?php include '../Common/header.php'; ?>
    <?php include '../Common/deptadmin-sidebar.php'; ?>

    <!-- Add Programme Modal -->
    <div class="modal fade" id="AddCourseModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form method="POST">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark"><i class="ti ti-plus me-2 text-primary"></i>Add Programme</h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body px-4 py-4">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary mb-1">Programme Name</label>
                            <input type="text" name="Prog_Name" class="form-control shadow-none border-secondary-subtle" placeholder="e.g., Computer Science" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="Add_Course" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm"><i class="ti ti-check me-1"></i>Add Programme</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <main>
        <div class="container-fluid py-4">
            <div class="dashboard-header d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <h4 class="mb-1">Manage Programmes</h4>
                    <p class="text-muted mb-0">View and add academic programmes for your department.</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm hover-elevate" data-bs-toggle="modal" data-bs-target="#AddCourseModal">
                    <i class="ti ti-plus me-1"></i>Add Programme
                </button>
            </div>

            <div class="card shadow-sm border-0 rounded-4 mb-4 hover-elevate">
                <div class="card-header bg-white border-bottom-0 py-3 px-4">
                    <h6 class="text-uppercase fw-bold text-primary mb-0"><i class="ti ti-list me-2"></i>Existing Programmes</h6>
                </div>
                <div class="card-body px-4 pb-4 pt-0">            
                    <?php
                        $query = "SELECT * FROM programmes WHERE Dept_ID = '$Dept_ID' ORDER BY Prog_Name ASC;";
                        $run = mysqli_query($con, $query);
                        if (mysqli_num_rows($run) == 0) {
                            echo "<div class='alert alert-warning border-0 rounded-3 mt-3'><i class='ti ti-alert-circle me-2'></i>No Programmes found.</div>";
                        }   
                        else {
                    ?>
                        <div class="table-responsive border rounded-4 mt-2">
                            <table class="table table-hover align-middle mb-0 text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th class="py-3 text-secondary fw-semibold">SL No</th>
                                        <th class="py-3 text-secondary fw-semibold">Programme Name</th>
                                        <th class="py-3 text-secondary fw-semibold">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php 
                                    $sl = 1;
                                    while ($row = mysqli_fetch_assoc($run)) {
                                ?>
                                <tr>
                                    <td class="text-muted fw-bold" style="width: 10%;"><?php echo $sl++; ?></td>
                                    <td class="fw-semibold text-dark text-start ps-5"><?php echo $row['Prog_Name']; ?></td>

                                    <td style="width: 25%;">
                                        <a href="edit-programme.php?Prog_ID=<?php echo $row['Prog_ID']; ?>" 
                                        class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold"><i class="ti ti-edit me-1"></i>Edit</a>

                                        <a href="delete-programme.php?Prog_ID=<?php echo $row['Prog_ID']; ?>" 
                                        class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-semibold ms-1"
                                        onclick="return confirm('Delete this programme?');"><i class="ti ti-trash me-1"></i>Delete</a>
                                    </td>
                                </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php 
                        } // end else
                    ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../Common/footer.php'; ?>
</body>
</html>
