<?php
    require_once "../Connection/connection.php";
    
    $Exam_ID = $_GET['Exam_ID'] ?? 4;
    $Exam_Name = $_GET['Exam_Name'] ?? "Exam";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename='.$Exam_Name.'_Results.csv');

    $coQuery = mysqli_query($con,"SELECT DISTINCT CO FROM exam_questions WHERE Exam_ID='$Exam_ID' ORDER BY CO");

    $coColumns = "";

    $headers = array("SL No","Stud ID","Name");
    
    while($row = mysqli_fetch_assoc($coQuery)){
        $co = $row['CO'];
        $coColumns .= ", SUM(CASE WHEN q.CO=$co THEN ra.Marks_Awarded ELSE 0 END) AS CO$co";
        $headers[] = "CO".$co;
    }
    
    $headers[] = "Total Marks";

    $sql = "SELECT r.Stud_ID, s.Stud_Name $coColumns, r.Marks_Obtained AS Total_Marks FROM results r
            INNER JOIN student s ON r.Stud_ID=s.Stud_ID
            INNER JOIN result_answers ra ON r.Result_ID=ra.Result_ID
            INNER JOIN exam_questions q ON ra.Question_ID=q.Question_ID
            WHERE r.Exam_ID='$Exam_ID'
            GROUP BY r.Result_ID, r.Stud_ID, s.Stud_Name, r.Marks_Obtained
            ORDER BY s.Stud_Name";

    $result = mysqli_query($con,$sql);

    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);

    $sl = 1;

    while($row = mysqli_fetch_assoc($result)){
        $csvRow = array(
            $sl++,
            $row['Stud_ID'],
            $row['Stud_Name']
        );

        // Add CO columns
        foreach($headers as $header){
            if(strpos($header,'CO') === 0){
                $csvRow[] = isset($row[$header]) ? $row[$header] : 0;
            }
        }

        // Total Marks at the end
        $csvRow[] = $row['Total_Marks'];

        fputcsv($output, $csvRow);
    }

    fclose($output);
    exit();
?>