<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Tailwind</title>

    <!-- Tailwind CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        * { font-family: "Solway", serif; }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">  
    <link rel="stylesheet" href="../Css/style.css">
</head>

<body class="bg-gray-100">

    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="ml-64 p-6 transition-all duration-300">

        <!-- Header -->
        <div class="bg-red-600 text-white rounded-xl px-6 py-4 shadow-md mb-6">
            <h2 class="text-xl font-bold">Admin Dashboard</h2>
        </div>

        <!-- Dashboard Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Card 1 -->
            <div class="bg-white shadow-md rounded-xl p-6 text-center hover:-translate-y-1 transition">
                <i class="bi bi-people-fill text-4xl text-red-600"></i>
                <h3 class="text-lg font-semibold mt-3">Total Students</h3>
                <p class="text-gray-500 text-sm">1,250 Registered</p>
            </div>

            <!-- Card 2 -->
            <div class="bg-white shadow-md rounded-xl p-6 text-center hover:-translate-y-1 transition">
                <i class="bi bi-pencil-square text-4xl text-red-600"></i>
                <h3 class="text-lg font-semibold mt-3">Ongoing Exams</h3>
                <p class="text-gray-500 text-sm">3 Active Exams</p>
            </div>

            <!-- Card 3 -->
            <div class="bg-white shadow-md rounded-xl p-6 text-center hover:-translate-y-1 transition">
                <i class="bi bi-bar-chart-fill text-4xl text-red-600"></i>
                <h3 class="text-lg font-semibold mt-3">Reports Generated</h3>
                <p class="text-gray-500 text-sm">540 Reports</p>
            </div>

        </div>
    </main>

</body>
</html>
