<?php  
	session_start();
	if (!($_SESSION["LoginAdmin"] || $_SESSION["LoginFaculty"] || $_SESSION["LoginDeptAdmin"])) {
		echo '<script> alert("Your Are Not Authorize Person For This link");</script>';
        echo '<script>window.location="../Login/Login.php"</script>';
	}
	require_once "../Connection/connection.php";
?>
<!------------------------------Delete Student------------------------------>
<?php 
	if (isset($_GET['Stud_ID'])) {
		$Stud_ID=$_GET['Stud_ID'];
		$query1="delete from student where Stud_ID='$Stud_ID'";
		$run1=mysqli_query($con,$query1);
		if ($run1) {
			echo "<script>alert('Student Successfully Deleted'); history.back();</script>";
		}
		else{
			echo "<script>alert('Record not deleted'); history.back();</script>";
		}
	}
?>

<!------------------------------Delete Faculty------------------------------>
<?php 
	if (isset($_GET['Fac_ID'])) {
		$Fac_ID=$_GET['Fac_ID'];
		$query2="delete from faculty where Fac_ID='$Fac_ID'";
		$run2=mysqli_query($con,$query2);
		if ($run2) {
			echo "<script>alert('Faculty Successfully Deleted'); history.back();</script>";
		}
		else{	
			echo "<script>alert('Record not deleted'); history.back();</script>";
		}
	}
?>

<!------------------------------Delete Exam------------------------------>
<?php 
	if (isset($_GET['Exam_ID'])) {
		$Exam_ID=$_GET['Exam_ID'];
		$query3="delete from exam where Exam_ID='$Exam_ID'";
		$run3=mysqli_query($con,$query3);
		if ($run3) {
			echo "<script>alert('Exam Successfully Deleted'); window.location.href='../Faculty/manage-exam.php';</script>";
		}
		else{	
			echo "<script>alert('Record not deleted'); history.back();</script>";
		}
	}
?>

<!------------------------------Delete Questions------------------------------>
<?php 
	if (isset($_GET['Q_ID'])) {
		$Q_ID=$_GET['Q_ID'];
		$query3="delete from question_bank where Q_ID='$Q_ID'";
		$run3=mysqli_query($con,$query3);
		if ($run3) {
			echo "<script>alert('Question Successfully Deleted'); window.location.href='../Faculty/question-bank.php';</script>";
		}
		else{	
			echo "<script>alert('Record not deleted'); history.back();</script>";
		}
	}
?>