<!---------------- Session starts form here ----------------------->
<?php  
	session_start();
	if (!$_SESSION["LoginStudent"]){
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location="../login/login.php"</script>';
	}
	require_once "../connection/connection.php";
	$Stud_ID=$_SESSION['LoginStudent'];
    $Exam_ID = $_GET['Exam_ID'];
?>
<!---------------- Session Ends form here ------------------------>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Exam</title>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .exam-container {
            max-width: 700px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
        }
        .question {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .option {
            background: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 5px;
            cursor: pointer;
        }
        .option:hover {
            background: #d4edda;
        }
        .btn-submit {
            width: 100%;
            padding: 10px;
            font-size: 18px;
        }
    </style>
</head>
<body>
    
<div class="exam-container">
    <h2 class="text-center mb-4">Attempt Exam</h2>

    <form method="POST">
        <input type="hidden" name="exam_id" value="<?php echo $Exam_ID; ?>">

        <?php
        // Fetch questions for this exam
        $sql = "SELECT * FROM questions WHERE Exam_ID = '$Exam_ID'";
        $result = mysqli_query($con, $sql);

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<div class='mb-3'>";
            echo "<p class='question'>" . htmlspecialchars($row['Question']) . "</p>";
            echo "<div class='form-check option'>
                    <input class='form-check-input' type='radio' name='q" . $row['Q_ID'] . "' value='A'>
                    <label class='form-check-label'>" . htmlspecialchars($row['O1']) . "</label>
                  </div>";
            echo "<div class='form-check option'>
                    <input class='form-check-input' type='radio' name='q" . $row['Q_ID'] . "' value='B'>
                    <label class='form-check-label'>" . htmlspecialchars($row['O2']) . "</label>
                  </div>";
            echo "<div class='form-check option'>
                    <input class='form-check-input' type='radio' name='q" . $row['Q_ID'] . "' value='C'>
                    <label class='form-check-label'>" . htmlspecialchars($row['O3']) . "</label>
                  </div>";
            echo "<div class='form-check option'>
                    <input class='form-check-input' type='radio' name='q" . $row['Q_ID'] . "' value='D'>
                    <label class='form-check-label'>" . htmlspecialchars($row['O4']) . "</label>
                  </div>";
            echo "</div>";
        }
        ?>

        <button type="submit" name="submit_exam" class="btn btn-success btn-submit">Submit Exam</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
if (isset($_POST['submit_exam'])) {
    $Score = 0;
    // Retrieve correct answers
    $sql = "SELECT * FROM questions WHERE Exam_ID = '$Exam_ID'";
    $result = mysqli_query($con, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
        $qid = "q" . $row['Q_ID'];

        if (isset($_POST[$qid])) {
            $selected_option = $_POST[$qid]; // A, B, C, or D

            // Map option letter to actual text
            $option_map = [
                'A' => $row['O1'],
                'B' => $row['O2'],
                'C' => $row['O3'],
                'D' => $row['O4']
            ];

            // Check if the selected option matches the correct answer
            if ($option_map[$selected_option] == $row['Answer']) {
                $Score++;
            }
        }
    }

    // Store the exam attempt in the database
    $insert_query = "INSERT INTO result (Exam_ID, Stud_ID, Score) VALUES ('$Exam_ID', '$Stud_ID', '$Score')";

    if (mysqli_query($con, $insert_query)) {
        echo "<script>alert('Exam submitted! Your Score: $Score'); window.location='student-exams.php';</script>";
    } else {
        echo "Error: " . mysqli_error($con);
    }
}
?>

</body>
</html>