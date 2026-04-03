<?php
// Database connection
$conn = mysqli_connect("localhost", "root", "", "mits-pms");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// If form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $manualFileName = mysqli_real_escape_string($conn, $_POST['filename']);
    $fileData = mysqli_real_escape_string($conn, file_get_contents($_FILES['pdf_file']['tmp_name']));
    $sql = "INSERT INTO pdf_files (filename, filedata) VALUES ('$manualFileName', '$fileData')";
    if (mysqli_query($conn, $sql)) {
        echo "PDF uploaded and saved to database.";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>

<!-- HTML Upload Form -->
<form action="" method="post" enctype="multipart/form-data">
    Enter file name:
    <input type="text" name="filename" required><br><br>

    Select PDF to upload:
    <input type="file" name="pdf_file" accept="application/pdf" required><br><br>

    <input type="submit" value="Upload PDF">
</form>


<?php
$conn = mysqli_connect("localhost", "root", "", "mits-pms");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// If form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int) $_POST['id'];
    $manualFileName = mysqli_real_escape_string($conn, $_POST['filename']);
    $fileData = mysqli_real_escape_string($conn, file_get_contents($_FILES['pdf_file']['tmp_name']));
    $sql = "UPDATE pdf_files SET filename = '$manualFileName', filedata = '$fileData' WHERE id = $id";
    
    if (mysqli_query($conn, $sql)) {
        echo "PDF record updated successfully.";
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>

<!-- HTML Update Form -->
<form action="" method="post" enctype="multipart/form-data">
    Enter file ID to update:
    <input type="number" name="id" required><br><br>

    Enter new file name:
    <input type="text" name="filename" required><br><br>

    (Optional) Select new PDF to upload:
    <input type="file" name="pdf_file" accept="application/pdf"><br><br>

    <input type="submit" value="Update PDF">
</form>
