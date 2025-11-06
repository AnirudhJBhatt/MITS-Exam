<?php  
	session_start();
	if (!$_SESSION["LoginFaculty"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location = "../Login/Login.php"</script>';
	}

	require_once "../Connection/connection.php";

    $Fac_ID=$_SESSION['LoginFaculty'];
	$query = "SELECT * FROM `faculty` WHERE `Fac_ID` = '$Fac_ID' ";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_array($run);
	$Fac_Dept=$row['Fac_Dept'];

	if (isset($_POST["Submit"])) {
		$Type_ID = $_POST["Exam_Type"];
		$Subject = $_POST["Subject"];

		if (isset($_FILES["csv_file"]["tmp_name"]) && $_FILES["csv_file"]["size"] > 0) {

			$filename = $_FILES["csv_file"]["tmp_name"];
			$Exam_ID = $_POST["Exam_ID"]; 

			// Open the CSV file
			$file = fopen($filename, "r");

			// Skip the header row
			fgetcsv($file);

			$count = 0;
			while (($row = fgetcsv($file)) !== false) {
				// Read CSV columns
				$question = mysqli_real_escape_string($con, $row[1]);
				$opt1 = mysqli_real_escape_string($con, $row[2]);
				$opt2 = mysqli_real_escape_string($con, $row[3]);
				$opt3 = mysqli_real_escape_string($con, $row[4]);
				$opt4 = mysqli_real_escape_string($con, $row[5]);
				$answer = mysqli_real_escape_string($con, $row[6]);
				$marks = (int)$row[7];

				// Prepare JSON arrays for options and correct answer
				$options = json_encode([$opt1, $opt2, $opt3, $opt4]);
				$correct_answer = json_encode([$answer]);

				// Insert into question_bank
				$query = "INSERT INTO question_bank (Subject_Code, Type_ID, Question_Text, Options, Correct_Answer, Marks)
					VALUES ('$Subject', '$Type_ID', '$question', '$options', '$correct_answer', '$marks')";

				if (mysqli_query($con, $query)) {
					$count++;
				}
			}
			fclose($file);
			echo "<script>alert('Successfully uploaded $count questions!'); window.location='question-bank.php';</script>";

		} else {
			echo "<script>alert('Please select a valid CSV file.');</script>";
		}
	}
?>

<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin - Manage Students</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
	<?php include '../Common/header.php'; ?>
	<?php include '../Common/faculty-sidebar.php'; ?>

	<main>
		<div class="dashboard-header">
			<h4 class="mb-0 fw-bold">Question Bank</h4>
		</div>

		<div class="sub-main">
			<div class="row">
				<div class="col-md-12 container-fluid">
					<form method="POST" enctype="multipart/form-data">
						<div class="row mt-3">
							<div class="col-md-4">
								<label>Type</label>
								<select class="form-select" name="Exam_Type" id="Exam_Type" required>
									<option value="">---None---</option>
									<?php
											$query = "SELECT * FROM question_type;";
											$run = mysqli_query($con, $query);
											while($row = mysqli_fetch_array($run)) {
												echo '<option value="'.$row['Type_ID'].'">'.$row['Type_Name'].'</option>';
											}
										?>
								</select>
							</div>
							<div class="col-md-4">
								<label>Subject</label>
								<input type="text" name="Subject" class="form-control" required>
							</div>
							<div class="col-md-4">
								<label>File</label>
								<input type="file" class="form-control" name="csv_file" accept=".csv" required>
							</div>
						</div>
						<div class="row mt-3">
							<div class="col-md-6">
								<input type="submit" name="Submit" value="Add Questions" class="btn btn-success">
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</main>

	<?php include '../Common/footer.php'; ?>
</body>

</html>