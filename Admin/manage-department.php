<?php  
    session_start();
    if (!$_SESSION["LoginAdmin"]) {
        echo '<script>alert("Unauthorized access!");</script>';
        echo '<script>window.location="../Login/Login.php"</script>';
    }

    require_once "../Connection/connection.php";
    function selected($field, $value) {
        return (isset($_POST[$field]) && $_POST[$field] == $value) ? "selected" : "";
    }

?>

<?php
// Insert Course
if (isset($_POST['Add_Course'])) {
    $Dept_ID = $_POST['Dept_ID'];
    $Dept_Name = $_POST['Dept_Name'];
    $Dept_Username = $_POST['Dept_Username'];
    
    $query1 = "INSERT INTO department (Dept_ID, Dept_Name, Dept_Code) VALUES ('$Dept_ID', '$Dept_Name', '$Dept_Username')";
    $query2 = "INSERT INTO login (ID, User_ID, Password, Role, Status) VALUES ('$Dept_ID','$Dept_Username', 'Dept@123', 'DeptAdmin', 'Activate')";


    $run1 = mysqli_query($con, $query1);
	$run2 = mysqli_query($con, $query2);

	if (!$run1 || !$run2) {
	    echo "<script>alert('Some records failed to insert!'); window.location='manage-department.php';</script>";
		exit;
	}
}
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Department</title>
</head>

<body>
    <?php include '../Common/header.php'; ?>
    <?php include '../Common/admin-sidebar.php'; ?>

    <!-- Add Course Modal -->
    <div class="modal fade" id="AddCourseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Course</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Department ID</label>
                            <input type="text" name="Dept_ID" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Department Name</label>
                            <input type="text" name="Dept_Name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">User Name</label>
                            <input type="text" name="Dept_Username" class="form-control" min="1" max="5" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="Add_Course" class="btn btn-success">Add Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <main>
        <div class="dashboard-header d-flex justify-content-start align-items-center">
            <h4 class="mb-0 fw-bold">Manage Departments</h4>
            <button class="btn btn-sm btn-primary ms-3" data-bs-toggle="modal" data-bs-target="#AddCourseModal">+ Add Department</button>
        </div>
        <div class="sub-main">            
            <?php
                $query = "SELECT * FROM department ORDER BY Dept_Name ASC;";
                $run = mysqli_query($con, $query);
                if (mysqli_num_rows($run) == 0) {
                    echo "<p class='mt-3 text-danger text-center'>No departments found.</p>";
                }   
                else {
            ?>
                    <table class="table table-bordered table-hover text-center border-dark mt-3">
                        <tr class="table-dark text-white">
                            <th>SL No</th>
                            <th>Dept ID</th>
                            <th>Department Name</th>
                            <th>Username</th>
                            <th>Action</th>
                        </tr>

                        <?php 
                            $sl = 1;
                            while ($row = mysqli_fetch_assoc($run)) {
                        ?>
                        <tr>
                            <td><?php echo $sl++; ?></td>
                            <td><?php echo $row['Dept_ID']; ?></td>
                            <td><?php echo $row['Dept_Name']; ?></td>
                            <td><?php echo $row['Dept_Code']; ?></td>

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
