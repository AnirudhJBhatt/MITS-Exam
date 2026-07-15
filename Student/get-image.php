<?php
require "../Connection/connection.php";

$id = $_GET['id'];

$sql = "SELECT Diagram_Image FROM question_bank WHERE Q_ID='$id'";
$res = mysqli_query($con, $sql);
$row = mysqli_fetch_assoc($res);

header("Content-Type: image/jpeg");   // or png based on your upload
echo $row['Diagram_Image'];
exit;
?>
