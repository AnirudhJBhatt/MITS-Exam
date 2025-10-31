<?php
// DB Connection
$mysqli = new mysqli("localhost", "root", "", "mits-pms");

// Query 1: Branch-wise placement count
$branch_query = "SELECT branch, COUNT(*) as count FROM placements GROUP BY branch";
$branch_result = $mysqli->query($branch_query);

// Query 2: Year-wise placement count
$year_query = "SELECT year, COUNT(*) as count FROM placements GROUP BY year";
$year_result = $mysqli->query($year_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Placement Stats</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
    // Load Google Charts
    google.charts.load('current', {'packages':['corechart']});

    // Draw charts when ready
    google.charts.setOnLoadCallback(drawCharts);

    function drawCharts() {
        // Branch-wise Pie Chart
        var branch_data = google.visualization.arrayToDataTable([
            ['Branch', 'Placements'],
            <?php
                while($row = $branch_result->fetch_assoc()) {
                    echo "['".$row['branch']."', ".$row['count']."],";
                }
            ?>
        ]);

        var branch_options = {
            title: 'Placements by Branch',
            pieHole: 0.4
        };

        var branch_chart = new google.visualization.PieChart(document.getElementById('branch_chart'));
        branch_chart.draw(branch_data, branch_options);

        // Year-wise Bar Chart
        var year_data = google.visualization.arrayToDataTable([
            ['Year', 'Placements'],
            <?php
                while($row = $year_result->fetch_assoc()) {
                    echo "['".$row['year']."', ".$row['count']."],";
                }
            ?>
        ]);

        var year_options = {
            title: 'Placements by Year',
            hAxis: {title: 'Year'},
            vAxis: {title: 'Number of Students'},
            bars: 'vertical'
        };

        var year_chart = new google.visualization.ColumnChart(document.getElementById('year_chart'));
        year_chart.draw(year_data, year_options);
    }
    </script>
</head>
<body>
    <h2>📊 Admin Dashboard - Placement Statistics</h2>
    <div id="branch_chart" style="width: 600px; height: 400px;"></div>
    <div id="year_chart" style="width: 600px; height: 400px;"></div>
</body>
</html>
