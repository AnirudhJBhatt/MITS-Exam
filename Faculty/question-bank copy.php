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

	if(isset($_POST['update_question'])) {

		$id = (int)$_POST['edit_id'];
		$type = (int)$_POST['edit_type'];
		$question = $_POST['edit_question'];
		$marks = (int)$_POST['edit_marks'];
		$co = (int)$_POST['edit_co'];

		/* TYPE 3 - MCQ */
		if($type == 3) {

			$correctIndex = (int)$_POST['correct_option'];
			$optionsArray = $_POST['edit_option'];
			$correctValue = $optionsArray[$correctIndex];

			$options = json_encode($optionsArray, JSON_UNESCAPED_UNICODE);
			$correct = json_encode([$correctValue], JSON_UNESCAPED_UNICODE);

			$stmt = $con->prepare("UPDATE question_bank 
				SET Question_Text=?, Options=?, Correct_Answer=?, Marks=?, CO=? 
				WHERE Q_ID=?");

			$stmt->bind_param("sssiii",
				$question, $options, $correct, $marks, $co, $id);
		}

		/* TYPE 1 */
		else if($type == 1) {

			$correct = json_encode([$_POST['edit_correct']], JSON_UNESCAPED_UNICODE);

			$stmt = $con->prepare("UPDATE question_bank 
				SET Question_Text=?, Correct_Answer=?, Marks=?, CO=? 
				WHERE Q_ID=?");

			$stmt->bind_param("ssiii",
				$question, $correct, $marks, $co, $id);
		}

		/* TYPE 2 */
		else if($type == 2) {

			$pairs = [];
			for($i=0; $i<count($_POST['edit_left']); $i++) {
				$pairs[$_POST['edit_left'][$i]] = $_POST['edit_right'][$i];
			}

			$options = json_encode($pairs, JSON_UNESCAPED_UNICODE);

			$stmt = $con->prepare("UPDATE question_bank 
				SET Question_Text=?, Options=?, Correct_Answer=?, Marks=?, CO=? 
				WHERE Q_ID=?");

			$stmt->bind_param("sssiii",
				$question, $options, $options, $marks, $co, $id);
		}

		$stmt->execute();

		echo "<script>
			alert('Question Updated Successfully');
			window.location.href=window.location.href;
		</script>";
	}

	if(isset($_POST['add_question'])) {

		$type = (int)$_POST['edit_type'];
		$question = $_POST['edit_question'];
		$marks = (int)$_POST['edit_marks'];
		$co = (int)$_POST['edit_co'];
		$Course_ID = (int)$_POST['Course_ID'];

		/* TYPE 3 - MCQ */
		if($type == 3) {

			$correctIndex = (int)$_POST['correct_option'];
			$optionsArray = $_POST['edit_option'];
			$correctValue = $optionsArray[$correctIndex];

			$options = json_encode($optionsArray, JSON_UNESCAPED_UNICODE);
			$correct = json_encode([$correctValue], JSON_UNESCAPED_UNICODE);

			$stmt = $con->prepare("INSERT INTO question_bank 
				(Type_ID, Subject_Code, Question_Text, Options, Correct_Answer, Marks, CO)
				VALUES (?, ?, ?, ?, ?, ?, ?)");

			$stmt->bind_param("issssii",
				$type, $Course_ID, $question, $options, $correct, $marks, $co);
		}

		/* TYPE 1 */
		else if($type == 1) {

			$correct = json_encode([$_POST['edit_correct']], JSON_UNESCAPED_UNICODE);

			$stmt = $con->prepare("INSERT INTO question_bank 
				(Type_ID, Subject_Code, Question_Text, Correct_Answer, Marks, CO)
				VALUES (?, ?, ?, ?, ?, ?)");

			$stmt->bind_param("isssii",
				$type, $Course_ID, $question, $correct, $marks, $co);
		}

		/* TYPE 2 */
		else if($type == 2) {

			$pairs = [];
			for($i=0; $i<count($_POST['edit_left']); $i++) {
				$pairs[$_POST['edit_left'][$i]] = $_POST['edit_right'][$i];
			}

			$options = json_encode($pairs, JSON_UNESCAPED_UNICODE);

			$stmt = $con->prepare("INSERT INTO question_bank 
				(Type_ID, Subject_Code, Question_Text, Options, Correct_Answer, Marks, CO)
				VALUES (?, ?, ?, ?, ?, ?, ?)");

			$stmt->bind_param("issssii",
				$type, $Course_ID, $question, $options, $options, $marks, $co);
		}

		$stmt->execute();

		echo "<script>
			alert('Question Added Successfully');
			window.location.href=window.location.href;
		</script>";
	}
?>




<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Faculty - Question Bank</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
	


	<style>
		/* Force MathLive keyboard above Bootstrap modal */
		.ML__keyboard {
			z-index: 9999 !important;
			position: fixed !important;
		}

		/* Make sure modal stays below keyboard */
		.modal {
			z-index: 1055;
		}

		/* Optional: ensure backdrop stays below */
		.modal-backdrop {
			z-index: 1050;
		}
		/* Prevent math overflow */
table td {
    vertical-align: middle;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

/* Allow MathJax SVG to wrap */
mjx-container {
    overflow-x: auto;
    overflow-y: hidden;
    max-width: 100%;
}

/* Prevent huge equation expansion */
mjx-container[jax="SVG"] {
    max-width: 100%;
}

/* Keep inline math aligned */
mjx-container[jax="SVG"][display="false"] {
    display: inline-block !important;
}
	</style>
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
								<select class="form-select" name="Course_ID" required >
									<option value="">---None---</option>
									<?php
										$query = "SELECT * FROM course_mapping cm, courses c WHERE cm.Course_ID=c.Course_ID AND cm.Acad_Year='$Acad_Year' AND cm.Fac_ID='$Fac_ID'";
										$run = mysqli_query($con, $query);
										while($row = mysqli_fetch_array($run)) {
											$selected = (isset($_POST['Course_ID']) && $_POST['Course_ID'] == $row['Course_ID']) ? "selected" : "";
											echo '<option value="'.$row['Course_ID'].'" '.$selected.'>'.$row['Course_Name'].'</option> ';
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
											$selected = (isset($_POST['Exam_Type']) && $_POST['Exam_Type'] == $row['Type_ID']) ? "selected" : "";
											echo '<option value="'.$row['Type_ID'].'" '.$selected.'>'.$row['Type_Name'].'</option>';
										}
									?>
								</select>
							</div>
							<div class="col-md-4">
								<label>File</label>
								<input type="file" class="form-control" name="csv_file" accept=".xlsx">
							</div>
						</div>
						<div class="row mt-3">
							<div class="col-md-6">
								<p class="text-muted" id="templateBox" style="display:none;">
									Click here to download template
									<a id="templateLink" href="#" download>Download Template</a>
								</p>
								<div class="col-md-6 d-flex gap-2">
									<input type="submit" name="Submit" value="Preview Questions" class="btn btn-success">
									<input type="submit" name="view_questions" value="View Questions" class="btn btn-primary">
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<div class="sub-main">
			<div class="row">
				<div class="col-md-12 container-fluid">
					<!-- Preview -->
					<?php
						if(isset($_POST['Submit'])) {
							if(!isset($_FILES["csv_file"]) || $_FILES["csv_file"]["error"] == UPLOAD_ERR_NO_FILE) {
								echo "<div class='alert alert-danger text-center'>Please select a file to upload.</div>";
								exit;
							}
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
					<?php
						if(isset($_POST['view_questions'])) {

							$Type_ID = (int)$_POST['Exam_Type'];
							$Course_ID = $_POST['Course_ID'];

							if(empty($Type_ID) || empty($Course_ID)) {
								echo "<script>alert('Please select Subject and Type');</script>";
							} else {

								$stmt = $con->prepare("
									SELECT Q_ID, Question_Text, Options, Correct_Answer, Marks, CO 
									FROM question_bank 
									WHERE Type_ID=? AND Subject_Code=?
									ORDER BY Q_ID DESC
								");
								$stmt->bind_param("is", $Type_ID, $Course_ID);
								$stmt->execute();
								$result = $stmt->get_result();

								echo '<div class="sub-main mt-4">';
								echo '<h5 class="text-center mb-3">Existing Questions</h5>';
								echo '<div class="d-flex justify-content-between align-items-center mb-3">';
								echo '<button class="btn btn-success btn-sm"
											data-bs-toggle="modal"
											data-bs-target="#editModal"
											id="addQuestionBtn"
											data-type="'.$Type_ID.'"
											data-course="'.$Course_ID.'">
											<i class="bi bi-plus-circle"></i> Add Question
									</button>';
								echo '</div>';
								

								if($result->num_rows > 0) {

									echo '<table class="table table-bordered table-hover text-center">';
									echo '<thead class="table-dark">
											<tr>
												<th>ID</th>
												<th>Question</th>
												<th>Options</th>
												<th>Correct</th>
												<th>Marks</th>
												<th>CO</th>
												<th>Action</th>
											</tr>
										</thead><tbody>';

									while($row = $result->fetch_assoc()) {

										echo '<tr>';
										echo '<td>'.$row['Q_ID'].'</td>';
										// echo '<td class="text-start"><math-field read-only>'.htmlspecialchars($row['Question_Text']).'</math-field></td>';
										echo '<td> '.nl2br(htmlspecialchars($row['Question_Text'])).' </td>';
										// Decode options if exist
										if(!empty($row['Options'])) {
											$options = json_decode($row['Options'], true);
											echo '<td class="text-start">';
											if(is_array($options)) {
												foreach($options as $opt) {
													echo nl2br(htmlspecialchars($opt)).'<br>';
												}
											}
											echo '</td>';
										} else {
											echo '<td>-</td>';
										}

										// Decode correct answer
										if(!empty($row['Correct_Answer'])) {
											$correct = json_decode($row['Correct_Answer'], true);
											echo '<td>';
											if(is_array($correct)) {
												foreach($correct as $ans) {
													echo nl2br(htmlspecialchars($ans)).'<br>';
												}
											}
											echo '</td>';
										} else {
											echo '<td>-</td>';
										}

										echo '<td>'.$row['Marks'].'</td>';
										echo '<td>'.$row['CO'].'</td>';
										echo '<td>
												<div class="d-flex gap-2 justify-content-center">
													<button 
														class="btn btn-sm btn-warning editBtn"
														data-id="'.$row['Q_ID'].'"
														data-type="'.$Type_ID.'"
														data-question="'.htmlspecialchars($row['Question_Text'], ENT_QUOTES).'"
														data-options="'.htmlspecialchars($row['Options'], ENT_QUOTES).'"
														data-correct="'.htmlspecialchars($row['Correct_Answer'], ENT_QUOTES).'"
														data-marks="'.$row['Marks'].'"
														data-co="'.$row['CO'].'"
														data-bs-toggle="modal"
														data-bs-target="#editModal">
														<i class="bi bi-pencil-square"></i>
													</button>
													<a href="../Admin/delete.php?Q_ID='.$row['Q_ID'].'" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure you want to delete this question?\')"><i class="bi bi-trash"></i></a>
												</div>
												</td>';
										echo '</tr>';
									}

									echo '</tbody></table>';

								} else {
									echo '<div class="alert alert-warning text-center">No questions found for this selection.</div>';
								}

								echo '</div>';
							}
						}
					?>
				</div>
			</div>
		</div>
		<!-- EDIT MODAL -->
		<div class="modal fade" id="editModal" tabindex="-1">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<form method="POST" id="questionForm">
						<div class="modal-header bg-dark text-white">
						<h5 class="modal-title">Edit Question</h5>
							<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
						</div>

						<div class="modal-body">

							<input type="hidden" name="edit_id" id="edit_id">
							<input type="hidden" name="edit_type" id="edit_type">
							<input type="hidden" name="Course_ID" id="edit_course_id">
							<input type="hidden" name="mode" id="mode">

							<div class="mb-3">
								<label>Question</label>
								<math-field id="math_question" class="form-control" smart-mode="true" 
    default-mode="text"></math-field>
								<input type="hidden" name="edit_question" id="edit_question">
							</div>

							<!-- Dynamic Area -->
							<div id="dynamicFields"></div>

							<div class="row">
								<div class="col-md-6">
								<label>Marks</label>
									<input type="number" class="form-control" name="edit_marks" id="edit_marks" required>
								</div>
								<div class="col-md-6">
								<label>CO</label>
									<input type="number" class="form-control" name="edit_co" id="edit_co" required>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="submit" id="saveBtn" class="btn btn-success">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</main>

	<script>
		const questionForm = document.getElementById("questionForm");

		/* ====
		Sync MathLive Before Submit
		==== */
		questionForm.addEventListener("submit", function () {

			// Question LaTeX
			document.getElementById("edit_question").value =
				document.getElementById("math_question").getValue();

			// MCQ Options LaTeX
			document.querySelectorAll(".option-math").forEach((field, index) => {
				document.querySelectorAll(".option-hidden")[index].value =
					field.getValue();
			});

			// TYPE 1 Correct Answer
			const mathCorrect = document.getElementById("math_correct");
			if (mathCorrect) {
				document.getElementById("edit_correct").value =
					mathCorrect.getValue();
			}
			
		});


		/* ====
		EDIT BUTTON
		==== */
		document.querySelectorAll(".editBtn").forEach(button => {

			button.addEventListener("click", function () {

				const type = parseInt(this.dataset.type);
				const options = JSON.parse(this.dataset.options || "[]");
				const correct = JSON.parse(this.dataset.correct || "[]")[0] || "";

				document.getElementById("saveBtn").name = "update_question";
				document.getElementById("saveBtn").innerText = "Update Question";

				document.getElementById("edit_id").value = this.dataset.id;
				document.getElementById("edit_type").value = type;
				document.getElementById("edit_marks").value = this.dataset.marks;
				document.getElementById("edit_co").value = this.dataset.co;

				document.getElementById("math_question").setValue(this.dataset.question);

				let dynamicHTML = "";

				/* ===== MCQ ===== */
				if (type === 3) {

					dynamicHTML += `<label class="fw-bold">Options</label>`;

					for (let i = 0; i < 4; i++) {

						let checked = (options[i] === correct) ? "checked" : "";

						dynamicHTML += `
							<div class="input-group mt-2">
								<div class="input-group-text">
									<input type="radio" name="correct_option" value="${i}" ${checked} required>
								</div>
								<math-field class="form-control option-math" smart-mode="true"></math-field>
								<input type="hidden" name="edit_option[]" class="option-hidden">
							</div>`;
					}

					document.getElementById("dynamicFields").innerHTML = dynamicHTML;

					// Load existing option values
					setTimeout(() => {
						document.querySelectorAll(".option-math").forEach((field, index) => {
							field.setValue(options[index] || "");
						});
					}, 0);
				}

				/* ===== TYPE 1 ===== */
				else if (type === 1) {

					dynamicHTML = `
						<label>Correct Answer</label>
						<math-field id="math_correct" class="form-control" smart-mode="true"></math-field>
						<input type="hidden" name="edit_correct" id="edit_correct">
					`;

					document.getElementById("dynamicFields").innerHTML = dynamicHTML;

					setTimeout(() => {
						document.getElementById("math_correct")
							.setValue(correct);
					}, 0);
				}

				/* ===== TYPE 2 ===== */
				else if (type === 2) {

					const pairs = JSON.parse(this.dataset.options || "{}");

					dynamicHTML = `<label>Pairs</label>`;

					for (const key in pairs) {
						dynamicHTML += `
							<div class="d-flex gap-2 mt-2">
								<input type="text" class="form-control"
									name="edit_left[]" value="${key}" required>
								<input type="text" class="form-control"
									name="edit_right[]" value="${pairs[key]}" required>
							</div>`;
					}

					document.getElementById("dynamicFields").innerHTML = dynamicHTML;
				}
			});
		});


		/* ====
		ADD BUTTON
		==== */
		document.addEventListener("click", function (e) {

			const addBtn = e.target.closest("#addQuestionBtn");
			if (!addBtn) return;

			const type = parseInt(addBtn.dataset.type);
			const course = addBtn.dataset.course;

			document.getElementById("saveBtn").name = "add_question";
			document.getElementById("saveBtn").innerText = "Add Question";

			document.getElementById("edit_id").value = "";
			document.getElementById("edit_type").value = type;
			document.getElementById("edit_course_id").value = course;
			document.getElementById("edit_marks").value = "";
			document.getElementById("edit_co").value = "";

			document.getElementById("math_question").setValue("");

			let dynamicHTML = "";

			if (type === 3) {

				dynamicHTML += `<label class="fw-bold">Options</label>`;

				for (let i = 0; i < 4; i++) {
					dynamicHTML += `
						<div class="input-group mt-2">
							<div class="input-group-text">
								<input type="radio" name="correct_option" value="${i}" required>
							</div>
							<math-field class="form-control option-math" smart-mode="true"></math-field>
							<input type="hidden" name="edit_option[]" class="option-hidden">
						</div>`;
				}
			}

			else if (type === 1) {
				dynamicHTML = `
					<label>Correct Answer</label>
					<math-field id="math_correct" class="form-control" smart-mode="true"></math-field>
					<input type="hidden" name="edit_correct" id="edit_correct">
				`;
			}

			else if (type === 2) {
				dynamicHTML = `
					<label>Pairs</label>
					<div class="d-flex gap-2 mt-2">
						<input type="text" class="form-control" name="edit_left[]" required>
						<input type="text" class="form-control" name="edit_right[]" required>
					</div>
				`;
			}

			document.getElementById("dynamicFields").innerHTML = dynamicHTML;
		});

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
	<script>
		document.addEventListener("DOMContentLoaded", function () {
			renderMathInElement(document.body, {
				delimiters: [
					{left: "\\(", right: "\\)", display: false},
					{left: "\\[", right: "\\]", display: true}
				],
				throwOnError: false
			});
		});
		</script>
		<script>
window.MathJax = {
  tex: {
    inlineMath: [['\\(', '\\)'], ['$', '$']]
  },
  svg: {
    fontCache: 'global',
    scale: 0.95   // slightly reduce size for table layout
  }
};
</script>

<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>

	<?php include '../Common/footer.php'; ?>
</body>

</html>