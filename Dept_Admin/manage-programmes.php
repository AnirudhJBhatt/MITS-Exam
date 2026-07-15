<?php  
    session_start();
    if (!$_SESSION["LoginDeptAdmin"]) {
        echo '<script>alert("Unauthorized access!");</script>';
        echo '<script>window.location="../Login/Login.php"</script>';
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

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Porgammes</title>
</head>

<body>
    <?php include '../Common/header.php'; ?>
    <?php include '../Common/deptadmin-sidebar.php'; ?>

    <!-- Add Course Modal -->
    <div class="modal fade" id="AddCourseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Programme</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Programme Name</label>
                            <input type="text" name="Prog_Name" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="Add_Course" class="btn btn-success">Add Programme</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <main>
        <div class="dashboard-header d-flex justify-content-start align-items-center">
            <h4 class="mb-0 fw-bold">Manage Programmes</h4>
            <button class="btn btn-sm btn-primary ms-3" data-bs-toggle="modal" data-bs-target="#AddCourseModal">+ Add Programme</button>
        </div>
        <div class="sub-main">            
            <?php
                $query = "SELECT * FROM programmes WHERE Dept_ID = '$Dept_ID' ORDER BY Prog_Name ASC;";
                $run = mysqli_query($con, $query);
                if (mysqli_num_rows($run) == 0) {
                    echo "<p class='mt-3 text-danger text-center'>No Programmes found.</p>";
                }   
                else {
            ?>
                    <table class="table table-bordered table-hover text-center border-dark mt-3">
                        <tr class="table-dark text-white">
                            <th>SL No</th>
                            <th>Programme Name</th>
                            <th>Action</th>
                        </tr>

                        <?php 
                            $sl = 1;
                            while ($row = mysqli_fetch_assoc($run)) {
                        ?>
                        <tr>
                            <td><?php echo $sl++; ?></td>
                            <td><?php echo $row['Prog_Name']; ?></td>

                            <td>
                                <a href="edit-department.php?Dept_ID=<?php echo $row['Dept_ID']; ?>" 
                                class="btn btn-sm btn-primary">Edit</a>

                                <a href="delete-department.php?Dept_ID=<?php echo $row['Dept_ID']; ?>" 
                                class="btn btn-sm btn-danger"
                                onclick="return confirm('Delete this department?');">
                                Delete
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    </table>
                    <?php 
                        } // end else
                    ?>
                </div>
            </div>
        </div>
    </main>
    <?php include '../Common/footer.php'; ?>
</body>
</html>
