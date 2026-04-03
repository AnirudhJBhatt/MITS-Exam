<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select2 Faculty Example</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
</head>

<body class="p-5">

<h4>Select Faculty</h4>

<select id="facultySelect" class="form-select">
    <option></option>
    <option value="1">Dr. Pramod Kumar M</option>
    <option value="2">Dr. Manoj Kumar K</option>
    <option value="3">Dr. Kurian Antony</option>
    <option value="4">Dr. Anil Joseph</option>
    <option value="5">Ms. Anusha S</option>
    <option value="6">Mr. Rahul R</option>
    <option value="7">Ms. Neethu T</option>
    <option value="8">Dr. Suresh Babu</option>
</select>

<!-- jQuery (MUST come first) -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function () {
    $('#facultySelect').select2({
        placeholder: "Search Faculty Name",
        allowClear: true,
        width: '100%'
    });
});
</script>

</body>
</html>
