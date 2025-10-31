<?php
// Database connection (Procedural)
$conn = mysqli_connect("localhost", "root", "", "emp");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// When form is submitted
if (isset($_POST['submit'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $filename = $_FILES['csv_file']['tmp_name'];

        if ($_FILES['csv_file']['size'] > 0) {
            $file = fopen($filename, "r");

            $skipHeader = true;

            while (($row = fgetcsv($file, 1000, ",")) !== FALSE) {
                if ($skipHeader) {
                    $skipHeader = false;
                    continue;
                }

                $name = mysqli_real_escape_string($conn, $row[0]);
                $email = mysqli_real_escape_string($conn, $row[1]);
                $age = (int)$row[2];

                $query = "INSERT INTO students (name, email, age) VALUES ('$name', '$email', $age)";
                mysqli_query($conn, $query);
            }

            fclose($file);
            echo "CSV data uploaded successfully.";
        } else {
            echo "Uploaded file is empty.";
        }
    } else {
        echo "Please upload a valid CSV file.";
    }
}
?>

<!-- HTML Form -->
<form method="post" enctype="multipart/form-data">
    <label>Select CSV File:</label>
    <input type="file" name="csv_file" accept=".csv" required>
    <input type="submit" name="submit" value="Upload">
</form>
