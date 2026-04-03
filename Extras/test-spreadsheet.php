<?php
    require __DIR__ . '/../vendor/autoload.php';


    echo "PhpSpreadsheet working on PHP " . PHP_VERSION;
?>
<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$data = [];

if (isset($_POST['upload'])) {

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] != 0) {
        die("File upload failed");
    }

    $fileExt = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));

    if ($fileExt !== 'xlsx') {
        die("Only .xlsx files are allowed");
    }

    $tmpPath = $_FILES['excel_file']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($tmpPath);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

    } catch (Exception $e) {
        die("Error reading Excel file: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Excel Upload (No DB)</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>

<h3>Upload Excel File (.xlsx)</h3>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="excel_file" accept=".xlsx" required>
    <button type="submit" name="upload">Upload</button>
</form>

<?php if (!empty($data)): ?>
    <h3>Excel Data Preview</h3>
    <table>
        <?php foreach ($data as $rowIndex => $row): ?>
            <tr>
                <?php foreach ($row as $cell): ?>
                    <?php if ($rowIndex === 0): ?>
                        <th><?= htmlspecialchars($cell) ?></th>
                    <?php else: ?>
                        <td><?= htmlspecialchars($cell) ?></td>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

</body>
</html>
