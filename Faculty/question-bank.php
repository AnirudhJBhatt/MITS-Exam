<?php
	session_start();
	if (!isset($_SESSION["LoginFaculty"])) {
		echo '<script>alert("You are not authorized");</script>';
		echo '<script>window.location="../Login/Login.php"</script>';
		exit;
	}

	require_once "../Connection/connection.php";
	require __DIR__ . '/../vendor/autoload.php';

	use PhpOffice\PhpSpreadsheet\IOFactory;

	/* ---------------- UTF-8 FIX ---------------- */
	mysqli_set_charset($con, "utf8mb4");

	/* ---------------- ERROR REPORTING ---------------- */
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	/* ---------------- NORMALIZE EXCEL TEXT ---------------- */
	function normalizeText($text) {
		if ($text === null) return '';

		// Force UTF-8
		$text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

		// Remove Excel line breaks
		$text = str_replace(["\r\n", "\r", "\n"], " ", $text);

		// Replace Word / Excel formatting symbols
		$text = str_replace(
			["“","”","‘","’","–","—","…","•","\xC2\xA0"], 
			['"','"',"'", "'", "-", "-", "...", "-", " "],
			$text
		);

		// Remove invisible control characters (KEEP math symbols)
		$text = preg_replace('/[\p{Cc}\p{Cf}]/u', '', $text);

		// Normalize spacing
		$text = preg_replace('/\s+/u', ' ', $text);

		return trim($text);
	}


	/* ---------------- FACULTY DETAILS ---------------- */
	$Fac_ID = $_SESSION['LoginFaculty'];
	$res = mysqli_query($con, "SELECT * FROM faculty WHERE Fac_ID='$Fac_ID'");
	$fac = mysqli_fetch_assoc($res);
	$Acad_Year = $fac['Acad_Year'];

	/* ---------------- FILE UPLOAD ---------------- */
	if (isset($_POST["submit_csv"])) {

		$Type_ID = (int)$_POST["Exam_Type"];
		$Course_ID = $_POST["Course_ID"];
		
		if (isset($_POST["csv_file_data"])) {
			$rows = unserialize(base64_decode($_POST["csv_file_data"]));
			// echo '<input type="hidden" name="csv_file_data" value="'.htmlspecialchars(base64_encode(serialize($rows))).'">';

			unset($rows[0]); // remove header
			$count = 0;

			foreach ($rows as $row) {

				if (empty($row[1])) continue;

				$question = normalizeText($row[1]);
				$marks = (int)$row[count($row) - 2];
				$co = (int)$row[count($row) - 1];

				/* ---------- TYPE 1 : FILL UPS ---------- */
				if ($Type_ID == 1) {

					$answer = normalizeText($row[2]);
					$correct = json_encode([$answer], JSON_UNESCAPED_UNICODE);

					$stmt = $con->prepare(
						"INSERT INTO question_bank
						(Type_ID, Subject_Code, Question_Text, Correct_Answer, Marks, CO)
						VALUES (?, ?, ?, ?, ?, ?)"
					);
					$stmt->bind_param("isssii",
						$Type_ID, $Course_ID, $question, $correct, $marks, $co
					);
				}

				/* ---------- TYPE 2 : MATCH ---------- */
				else if ($Type_ID == 2) {

					$pairs = [];
					foreach (explode("|", normalizeText($row[2])) as $pair) {
						$p = explode(":", $pair);
						if (count($p) == 2) {
							$pairs[trim($p[0])] = trim($p[1]);
						}
					}

					$options = json_encode($pairs, JSON_UNESCAPED_UNICODE);

					$stmt = $con->prepare(
						"INSERT INTO question_bank
						(Type_ID, Subject_Code, Question_Text, Options, Correct_Answer, Marks, CO)
						VALUES (?, ?, ?, ?, ?, ?, ?)"
					);
					$stmt->bind_param("issssii",
						$Type_ID, $Course_ID, $question, $options, $options, $marks, $co
					);
				}

				/* ---------- TYPE 3 : MCQ ---------- */
				else if ($Type_ID == 3) {

					$options = json_encode([
						normalizeText($row[2]),
						normalizeText($row[3]),
						normalizeText($row[4]),
						normalizeText($row[5])
					], JSON_UNESCAPED_UNICODE);

					$correct = json_encode(
						[normalizeText($row[6])],
						JSON_UNESCAPED_UNICODE
					);

					$stmt = $con->prepare(
						"INSERT INTO question_bank
						(Type_ID, Subject_Code, Question_Text, Options, Correct_Answer, Marks, CO)
						VALUES (?, ?, ?, ?, ?, ?, ?)"
					);
					$stmt->bind_param("issssii",
						$Type_ID, $Course_ID, $question, $options, $correct, $marks, $co
					);
				}

				/* ---------- TYPE 7 : OPEN ENDED ---------- */
				else if ($Type_ID == 7) {

					$stmt = $con->prepare(
						"INSERT INTO question_bank
						(Type_ID, Subject_Code, Question_Text, Marks, CO)
						VALUES (?, ?, ?, ?, ?)"
					);
					$stmt->bind_param("issii",
						$Type_ID, $Course_ID, $question, $marks, $co
					);
				}

				if ($stmt->execute()) {
					$count++;
				}
			}

			echo "<script>
					alert('Successfully uploaded $count questions!');
					window.location='question-bank.php';
					if (window.history.replaceState) {
						window.history.replaceState(null, null, window.location.href);
					}
				</script>";

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
											// echo '<option value="'.$row['Course_ID'].'">'.$row['Course_Name'] .'</option>';
											echo '<option value="'.$row['Course_ID'].'">'.$row['Course_Name'].' - '.$row['Dept_ID'].'</option>';
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
								<input type="submit" name="Submit" value="Preview Questions" class="btn btn-success">
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<div class="sub-main mt-4">
			<div class="row">
				<div class="col-md-12 container-fluid">
					<!-- Preview -->
					<?php
						if(isset($_POST['Submit'])) {
							echo '<form method="POST">';
							echo '<input type="hidden" name="Exam_Type" value="'.htmlspecialchars($_POST['Exam_Type']).'">';
							echo '<input type="hidden" name="Course_ID" value="'.htmlspecialchars($_POST['Course_ID']).'">';
							
							$spreadsheet = IOFactory::load($_FILES["csv_file"]["tmp_name"]);
							$rows = $spreadsheet->getActiveSheet()->toArray();

							echo '<input type="hidden" name="csv_file_data" value="'.htmlspecialchars(base64_encode(serialize($rows))).'">';

							echo '<h5 class="mb-3 text-center">Preview Questions</h5>';
							echo '<table class="w-100 table table-bordered border-dark table-hover text-center" cellpadding="5"><thead><tr class="table-dark text-white">';
							foreach ($rows[0] as $header) {
								echo '<th>' . htmlspecialchars($header) . '</th>';
							}
							echo '</tr></thead><tbody>';
							for ($i = 1; $i < count($rows); $i++) {
								echo '<tr>';
								foreach ($rows[$i] as $cell) {
									echo '<td>' . htmlspecialchars(normalizeText($cell)) . '</td>';
								}
								echo '</tr>';
							}
							echo '</tbody></table>';
							echo '	<div class="text-center mb-5">
										<input type="submit" name="submit_csv" value="Confirm & Save" class="btn btn-success">
									</div>';
							echo '</form>';
						}
					?>
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

</html>