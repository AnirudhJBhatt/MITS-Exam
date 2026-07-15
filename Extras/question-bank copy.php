<<<<<<< HEAD
<<<<<<< HEAD
<?php  
	session_start();
	if (!$_SESSION["LoginFaculty"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location = "../Login/Login.php"</script>';
	}

	require_once "../Connection/connection.php";
	require __DIR__ . '/../vendor/autoload.php';
	use PhpOffice\PhpSpreadsheet\IOFactory;

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

    $Fac_ID=$_SESSION['LoginFaculty'];
	$query = "SELECT * FROM `faculty` WHERE `Fac_ID` = '$Fac_ID' ";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_array($run);
	$Fac_Dept=$row['Fac_Dept'];
	$Acad_Year=$row['Acad_Year'];

	if (isset($_POST["Submit"])) {
		$Type_ID = $_POST["Exam_Type"];
		$Course_ID = $_POST["Course_ID"];

		if (isset($_FILES["csv_file"]["tmp_name"]) && $_FILES["csv_file"]["size"] > 0) {

			$filePath = $_FILES["csv_file"]["tmp_name"];

			$spreadsheet = IOFactory::load($filePath);
			$sheet = $spreadsheet->getActiveSheet();
			$rows = $sheet->toArray();

			unset($rows[0]); // Remove header row
			$count = 0;

			foreach ($rows as $row) {

				if (empty($row[1])) continue; // Skip empty rows

				$question = mysqli_real_escape_string($con, $row[1]);
				$marks = (int)$row[count($row) - 1];

				// ---------- FILL-UP TYPE (Type 1) ----------
				// format: QNo, Question, Answer, Marks
				if ($Type_ID == 1) {
					$answer = mysqli_real_escape_string($con, $row[2]);
					$correct_answer = json_encode([$answer]);

					$query = "INSERT INTO question_bank 
					(Type_ID, Subject_Code, Question_Text, Correct_Answer, Marks)
					VALUES ('$Type_ID', '$Course_ID', '$question', '$correct_answer', '$marks')";
				}

				// ---------- MATCH THE FOLLOWING (Type 2) ----------
				// format: QNo, Question, Pair1|Pair2|Pair3..., Marks
				else if ($Type_ID == 2) {

					$raw_pairs = $row[2];
					$pair_items = explode("|", $raw_pairs);
					$pairs_array = [];

					foreach ($pair_items as $pair) {
						$parts = explode(":", trim($pair));
						if (count($parts) == 2) {
							$pairs_array[trim($parts[0])] = trim($parts[1]);
						}
					}

					$options = json_encode($pairs_array);

					$query = "INSERT INTO question_bank 
					(Type_ID, Subject_Code, Question_Text, Options, Correct_Answer, Marks)
					VALUES ('$Type_ID', '$Course_ID', '$question', '$options', '$options', '$marks')";
				}

				// ---------- MCQ TYPE (Type 3) ----------
				// format: QNo, Question, Option1, Option2, Option3, Option4, Answer, Marks
				else if ($Type_ID == 3) {

					$opt1 = mysqli_real_escape_string($con, $row[2]);
					$opt2 = mysqli_real_escape_string($con, $row[3]);
					$opt3 = mysqli_real_escape_string($con, $row[4]);
					$opt4 = mysqli_real_escape_string($con, $row[5]);
					$answer = mysqli_real_escape_string($con, $row[6]);

					$options = json_encode([$opt1, $opt2, $opt3, $opt4]);
					$correct_answer = json_encode([$answer]);

					$query = "INSERT INTO question_bank 
					(Type_ID, Subject_Code, Question_Text, Options, Correct_Answer, Marks)
					VALUES ('$Type_ID', '$Course_ID', '$question', '$options', '$correct_answer', '$marks')";
				}

				// ---------- OPEN ENDED (Type 7) ----------
				// format: QNo, Question, Marks
				else if ($Type_ID == 7) {

					$query = "INSERT INTO question_bank 
					(Type_ID, Subject_Code, Question_Text, Marks)
					VALUES ('$Type_ID', '$Course_ID', '$question', '$marks')";
				}

				if (mysqli_query($con, $query)) {
					$count++;
				}
			}

			echo "<script>alert('Successfully uploaded $count questions!'); window.location='question-bank.php';</script>";

		} else {
			echo "<script>alert('Please select a valid file');</script>";
		}
	}
?>

<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Faculty - Question Bank</title>
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
						<div class="row">
							<div class="col-md-4">
								<label>Subject</label>
								<select class="form-select" name="Course_ID" required>
									<option value="">---None---</option>
									<?php
										$query = "SELECT * FROM course_mapping cm, courses c WHERE cm.Course_ID=c.Course_ID AND cm.Acad_Year='$Acad_Year' AND cm.Fac_ID='$Fac_ID'";
										$run = mysqli_query($con, $query);
										while($row = mysqli_fetch_array($run)) {
											echo '<option value="'.$row['Course_ID'].'">'.$row['Course_Name'].'</option>';
										}
									?>
								</select>
							</div>
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
								<label>File</label>
								<input type="file" class="form-control" name="csv_file" accept=".xlsx" required>
							</div>
						</div>
						<div class="row mt-3">
							<div class="col-md-6">
								<p class="text-muted" id="templateBox" style="display:none;">
									Click here to download template
									<a id="templateLink" href="#" download>Download Template</a>
								</p>
								<input type="submit" name="Submit" value="Add Questions" class="btn btn-success">
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</main>
	<script>
		document.getElementById("Exam_Type").addEventListener("change", function () {
			const type = this.value;
			const templateBox = document.getElementById("templateBox");
			const templateLink = document.getElementById("templateLink");

			// Mapping Type_ID → Template file
			const templates = {
				1: "Statement_Filling_Template.xlsx",
				2: "Matching_Template.xlsx",
				3: "MCQ_Template.xlsx",
				4: "Hint_Based_Template.xlsx",
				5: "Case_Study_Template.xlsx",
				6: "Cheat_Sheet_Template.xlsx",
				7: "Open_Ended_Template.xlsx",
				8: "Chain_Template.xlsx",
				9: "Diagram_Template.xlsx",
				10: "Code_Template.xlsx"
			};

			if (templates[type]) {
				templateLink.href = "../Templates/" + templates[type];
				templateBox.style.display = "block";
			} else {
				templateBox.style.display = "none";
				templateLink.href = "#";
			}
		});
	</script>


	<?php include '../Common/footer.php'; ?>
</body>

=======
<?php  
	session_start();
	if (!$_SESSION["LoginFaculty"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location = "../Login/Login.php"</script>';
	}

	require_once "../Connection/connection.php";
	require __DIR__ . '/../vendor/autoload.php';
	use PhpOffice\PhpSpreadsheet\IOFactory;

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

    $Fac_ID=$_SESSION['LoginFaculty'];
	$query = "SELECT * FROM `faculty` WHERE `Fac_ID` = '$Fac_ID' ";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_array($run);
	$Fac_Dept=$row['Fac_Dept'];
	$Acad_Year=$row['Acad_Year'];

	if (isset($_POST["Submit"])) {
		$Type_ID = $_POST["Exam_Type"];
		$Course_ID = $_POST["Course_ID"];

		if (isset($_FILES["csv_file"]["tmp_name"]) && $_FILES["csv_file"]["size"] > 0) {

			$filePath = $_FILES["csv_file"]["tmp_name"];

			$spreadsheet = IOFactory::load($filePath);
			$sheet = $spreadsheet->getActiveSheet();
			$rows = $sheet->toArray();

			unset($rows[0]); // Remove header row
			$count = 0;

			foreach ($rows as $row) {

				if (empty($row[1])) continue; // Skip empty rows

				$question = mysqli_real_escape_string($con, $row[1]);
				$marks = (int)$row[count($row) - 1];

				// ---------- FILL-UP TYPE (Type 1) ----------
				// format: QNo, Question, Answer, Marks
				if ($Type_ID == 1) {
					$answer = mysqli_real_escape_string($con, $row[2]);
					$correct_answer = json_encode([$answer]);

					$query = "INSERT INTO question_bank 
					(Type_ID, Subject_Code, Question_Text, Correct_Answer, Marks)
					VALUES ('$Type_ID', '$Course_ID', '$question', '$correct_answer', '$marks')";
				}

				// ---------- MATCH THE FOLLOWING (Type 2) ----------
				// format: QNo, Question, Pair1|Pair2|Pair3..., Marks
				else if ($Type_ID == 2) {

					$raw_pairs = $row[2];
					$pair_items = explode("|", $raw_pairs);
					$pairs_array = [];

					foreach ($pair_items as $pair) {
						$parts = explode(":", trim($pair));
						if (count($parts) == 2) {
							$pairs_array[trim($parts[0])] = trim($parts[1]);
						}
					}

					$options = json_encode($pairs_array);

					$query = "INSERT INTO question_bank 
					(Type_ID, Subject_Code, Question_Text, Options, Correct_Answer, Marks)
					VALUES ('$Type_ID', '$Course_ID', '$question', '$options', '$options', '$marks')";
				}

				// ---------- MCQ TYPE (Type 3) ----------
				// format: QNo, Question, Option1, Option2, Option3, Option4, Answer, Marks
				else if ($Type_ID == 3) {

					$opt1 = mysqli_real_escape_string($con, $row[2]);
					$opt2 = mysqli_real_escape_string($con, $row[3]);
					$opt3 = mysqli_real_escape_string($con, $row[4]);
					$opt4 = mysqli_real_escape_string($con, $row[5]);
					$answer = mysqli_real_escape_string($con, $row[6]);

					$options = json_encode([$opt1, $opt2, $opt3, $opt4]);
					$correct_answer = json_encode([$answer]);

					$query = "INSERT INTO question_bank 
					(Type_ID, Subject_Code, Question_Text, Options, Correct_Answer, Marks)
					VALUES ('$Type_ID', '$Course_ID', '$question', '$options', '$correct_answer', '$marks')";
				}

				// ---------- OPEN ENDED (Type 7) ----------
				// format: QNo, Question, Marks
				else if ($Type_ID == 7) {

					$query = "INSERT INTO question_bank 
					(Type_ID, Subject_Code, Question_Text, Marks)
					VALUES ('$Type_ID', '$Course_ID', '$question', '$marks')";
				}

				if (mysqli_query($con, $query)) {
					$count++;
				}
			}

			echo "<script>alert('Successfully uploaded $count questions!'); window.location='question-bank.php';</script>";

		} else {
			echo "<script>alert('Please select a valid file');</script>";
		}
	}
?>

<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Faculty - Question Bank</title>
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
						<div class="row">
							<div class="col-md-4">
								<label>Subject</label>
								<select class="form-select" name="Course_ID" required>
									<option value="">---None---</option>
									<?php
										$query = "SELECT * FROM course_mapping cm, courses c WHERE cm.Course_ID=c.Course_ID AND cm.Acad_Year='$Acad_Year' AND cm.Fac_ID='$Fac_ID'";
										$run = mysqli_query($con, $query);
										while($row = mysqli_fetch_array($run)) {
											echo '<option value="'.$row['Course_ID'].'">'.$row['Course_Name'].'</option>';
										}
									?>
								</select>
							</div>
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
								<label>File</label>
								<input type="file" class="form-control" name="csv_file" accept=".xlsx" required>
							</div>
						</div>
						<div class="row mt-3">
							<div class="col-md-6">
								<p class="text-muted" id="templateBox" style="display:none;">
									Click here to download template
									<a id="templateLink" href="#" download>Download Template</a>
								</p>
								<input type="submit" name="Submit" value="Add Questions" class="btn btn-success">
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</main>
	<script>
		document.getElementById("Exam_Type").addEventListener("change", function () {
			const type = this.value;
			const templateBox = document.getElementById("templateBox");
			const templateLink = document.getElementById("templateLink");

			// Mapping Type_ID → Template file
			const templates = {
				1: "Statement_Filling_Template.xlsx",
				2: "Matching_Template.xlsx",
				3: "MCQ_Template.xlsx",
				4: "Hint_Based_Template.xlsx",
				5: "Case_Study_Template.xlsx",
				6: "Cheat_Sheet_Template.xlsx",
				7: "Open_Ended_Template.xlsx",
				8: "Chain_Template.xlsx",
				9: "Diagram_Template.xlsx",
				10: "Code_Template.xlsx"
			};

			if (templates[type]) {
				templateLink.href = "../Templates/" + templates[type];
				templateBox.style.display = "block";
			} else {
				templateBox.style.display = "none";
				templateLink.href = "#";
			}
		});
	</script>


	<?php include '../Common/footer.php'; ?>
</body>

>>>>>>> f1e265abf03ca415a8e766b8518d8c076d9bf836
=======
<?php  
	session_start();
	if (!$_SESSION["LoginFaculty"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location = "../Login/Login.php"</script>';
	}

	require_once "../Connection/connection.php";
	require __DIR__ . '/../vendor/autoload.php';
	use PhpOffice\PhpSpreadsheet\IOFactory;

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

    $Fac_ID=$_SESSION['LoginFaculty'];
	$query = "SELECT * FROM `faculty` WHERE `Fac_ID` = '$Fac_ID' ";
    $run = mysqli_query($con, $query);
    $row = mysqli_fetch_array($run);
	$Fac_Dept=$row['Fac_Dept'];
	$Acad_Year=$row['Acad_Year'];

	if (isset($_POST["Submit"])) {
		$Type_ID = $_POST["Exam_Type"];
		$Course_ID = $_POST["Course_ID"];

		if (isset($_FILES["csv_file"]["tmp_name"]) && $_FILES["csv_file"]["size"] > 0) {

			$filePath = $_FILES["csv_file"]["tmp_name"];

			$spreadsheet = IOFactory::load($filePath);
			$sheet = $spreadsheet->getActiveSheet();
			$rows = $sheet->toArray();

			unset($rows[0]); // Remove header row
			$count = 0;

			foreach ($rows as $row) {

				if (empty($row[1])) continue; // Skip empty rows

				$question = mysqli_real_escape_string($con, $row[1]);
				$marks = (int)$row[count($row) - 1];

				// ---------- FILL-UP TYPE (Type 1) ----------
				// format: QNo, Question, Answer, Marks
				if ($Type_ID == 1) {
					$answer = mysqli_real_escape_string($con, $row[2]);
					$correct_answer = json_encode([$answer]);

					$query = "INSERT INTO question_bank 
					(Type_ID, Subject_Code, Question_Text, Correct_Answer, Marks)
					VALUES ('$Type_ID', '$Course_ID', '$question', '$correct_answer', '$marks')";
				}

				// ---------- MATCH THE FOLLOWING (Type 2) ----------
				// format: QNo, Question, Pair1|Pair2|Pair3..., Marks
				else if ($Type_ID == 2) {

					$raw_pairs = $row[2];
					$pair_items = explode("|", $raw_pairs);
					$pairs_array = [];

					foreach ($pair_items as $pair) {
						$parts = explode(":", trim($pair));
						if (count($parts) == 2) {
							$pairs_array[trim($parts[0])] = trim($parts[1]);
						}
					}

					$options = json_encode($pairs_array);

					$query = "INSERT INTO question_bank 
					(Type_ID, Subject_Code, Question_Text, Options, Correct_Answer, Marks)
					VALUES ('$Type_ID', '$Course_ID', '$question', '$options', '$options', '$marks')";
				}

				// ---------- MCQ TYPE (Type 3) ----------
				// format: QNo, Question, Option1, Option2, Option3, Option4, Answer, Marks
				else if ($Type_ID == 3) {

					$opt1 = mysqli_real_escape_string($con, $row[2]);
					$opt2 = mysqli_real_escape_string($con, $row[3]);
					$opt3 = mysqli_real_escape_string($con, $row[4]);
					$opt4 = mysqli_real_escape_string($con, $row[5]);
					$answer = mysqli_real_escape_string($con, $row[6]);

					$options = json_encode([$opt1, $opt2, $opt3, $opt4]);
					$correct_answer = json_encode([$answer]);

					$query = "INSERT INTO question_bank 
					(Type_ID, Subject_Code, Question_Text, Options, Correct_Answer, Marks)
					VALUES ('$Type_ID', '$Course_ID', '$question', '$options', '$correct_answer', '$marks')";
				}

				// ---------- OPEN ENDED (Type 7) ----------
				// format: QNo, Question, Marks
				else if ($Type_ID == 7) {

					$query = "INSERT INTO question_bank 
					(Type_ID, Subject_Code, Question_Text, Marks)
					VALUES ('$Type_ID', '$Course_ID', '$question', '$marks')";
				}

				if (mysqli_query($con, $query)) {
					$count++;
				}
			}

			echo "<script>alert('Successfully uploaded $count questions!'); window.location='question-bank.php';</script>";

		} else {
			echo "<script>alert('Please select a valid file');</script>";
		}
	}
?>

<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Faculty - Question Bank</title>
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
						<div class="row">
							<div class="col-md-4">
								<label>Subject</label>
								<select class="form-select" name="Course_ID" required>
									<option value="">---None---</option>
									<?php
										$query = "SELECT * FROM course_mapping cm, courses c WHERE cm.Course_ID=c.Course_ID AND cm.Acad_Year='$Acad_Year' AND cm.Fac_ID='$Fac_ID'";
										$run = mysqli_query($con, $query);
										while($row = mysqli_fetch_array($run)) {
											echo '<option value="'.$row['Course_ID'].'">'.$row['Course_Name'].'</option>';
										}
									?>
								</select>
							</div>
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
								<label>File</label>
								<input type="file" class="form-control" name="csv_file" accept=".xlsx" required>
							</div>
						</div>
						<div class="row mt-3">
							<div class="col-md-6">
								<p class="text-muted" id="templateBox" style="display:none;">
									Click here to download template
									<a id="templateLink" href="#" download>Download Template</a>
								</p>
								<input type="submit" name="Submit" value="Add Questions" class="btn btn-success">
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</main>
	<script>
		document.getElementById("Exam_Type").addEventListener("change", function () {
			const type = this.value;
			const templateBox = document.getElementById("templateBox");
			const templateLink = document.getElementById("templateLink");

			// Mapping Type_ID → Template file
			const templates = {
				1: "Statement_Filling_Template.xlsx",
				2: "Matching_Template.xlsx",
				3: "MCQ_Template.xlsx",
				4: "Hint_Based_Template.xlsx",
				5: "Case_Study_Template.xlsx",
				6: "Cheat_Sheet_Template.xlsx",
				7: "Open_Ended_Template.xlsx",
				8: "Chain_Template.xlsx",
				9: "Diagram_Template.xlsx",
				10: "Code_Template.xlsx"
			};

			if (templates[type]) {
				templateLink.href = "../Templates/" + templates[type];
				templateBox.style.display = "block";
			} else {
				templateBox.style.display = "none";
				templateLink.href = "#";
			}
		});
	</script>


	<?php include '../Common/footer.php'; ?>
</body>

>>>>>>> f1e265abf03ca415a8e766b8518d8c076d9bf836
</html>