<?php
$conn = mysqli_connect("localhost", "root", "", "mits-pms");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// If form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int) $_POST['id'];
    $manualFileName = mysqli_real_escape_string($conn, $_POST['filename']);
    $sql = "UPDATE pdf_files SET filename = '$manualFileName', filedata = '$fileData' WHERE id = $id";
    if (isset($_FILES['pdf_file'])) {
            $fileType = $_FILES['pdf_file']['type'];
            if ($fileType === "application/pdf") {
                $fileData = mysqli_real_escape_string($conn, file_get_contents($_FILES['pdf_file']['tmp_name']));
                $sql = "UPDATE pdf_files SET filename = '$manualFileName', filedata = '$fileData' WHERE id = $id";
            } else {
                echo "Only PDF files are allowed.";
                mysqli_close($conn);
                exit;
            }
    } else {
            // No new file uploaded, only update filename
            $sql = "UPDATE pdf_files SET filename = '$manualFileName' WHERE id = $id";
    }

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
