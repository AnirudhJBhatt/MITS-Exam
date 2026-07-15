<?php  
	session_start();
	if (!isset($_SESSION['LoginStudent'])) {
        echo "<script>alert('You Are Not Authorize Person For This link'); window.location.href='../index.php';</script>";
        exit;
    }
	require_once "../Connection/connection.php";
    $Stud_ID = $_SESSION['LoginStudent'];
    $query = "SELECT * FROM student s, department d, programmes p WHERE s.Stud_ID = '$Stud_ID' AND s.Stud_Dept = d.Dept_ID AND s.Stud_Branch = p.Prog_ID";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_array($run);
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student - Dashboard</title>
</head>

<body>

    <!-- NAVBAR -->
    <?php include '../Common/header.php'; ?>
    <!-- SIDEBAR -->
    <?php include '../Common/student-sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main>
        <div class="dashboard-header">
            <h4 class="mb-0 fw-bold">Dashboard</h4>
        </div>
        <!-- Profile Card -->
        <div class="card shadow-lg border-0 rounded-4">
            <!-- Card Header -->
            <div class="card-header bg-dark text-white text-center rounded-top-4">
                <h4 class="mb-0">Student Profile</h4>
            </div>

            <!-- Card Body -->
            <div class="card-body p-4">
                <div class="row g-4 align-items-center">
                    <!-- Profile Image -->
                    <div class="col-md-4 text-center">
                        <div class="rounded-circle bg-light border border-3 shadow-sm d-flex align-items-center justify-content-center" style="height:200px; width:200px; margin:auto;">
                            <i class="bi bi-person-fill text-secondary" style="font-size:100px;"></i>
                        </div>
                        <h5 class="mt-3 fw-bold">
                            <?php echo $row['Stud_Name']; ?>
                        </h5>
                    </div>
                    <!-- Profile Details -->
                    <div class="col-md-8">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle ">
                                <thead class="table-dark text-center">
                                    <tr>
                                        <th colspan="2">Personal Information</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <th class="bg-light w-50">Admission No</th>
                                        <td><?php echo $row['Stud_ID']; ?></td>
                                    </tr>

                                    <tr>
                                        <th class="bg-light">Name</th>
                                        <td><?php echo $row['Stud_Name']; ?></td>
                                    </tr>

                                    <tr>
                                        <th class="bg-light">Department</th>
                                        <td><?php echo $row['Dept_Name']; ?></td>
                                    </tr>

                                    <tr>
                                        <th class="bg-light">Programme</th>
                                        <td><?php echo $row['Stud_Prog'] . '  ' . $row['Prog_Name']; ?></td>
                                    </tr>

                                    <tr>
                                        <th class="bg-light">Email</th>
                                        <td><?php echo $row['Stud_Email']; ?></td>
                                    </tr>
                                    
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include '../Common/footer.php'; ?>
</body>

</html>