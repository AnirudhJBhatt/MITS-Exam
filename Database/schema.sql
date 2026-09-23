academic_year	CREATE TABLE `academic_year` (
 `AY_ID` int(11) NOT NULL AUTO_INCREMENT,
 `AY_Name` varchar(20) NOT NULL,
 PRIMARY KEY (`AY_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

courses	CREATE TABLE `courses` (
 `Course_ID` int(11) NOT NULL AUTO_INCREMENT,
 `Dept_ID` varchar(10) NOT NULL,
 `Prog_ID` int(11) NOT NULL,
 `Course_Year` varchar(10) NOT NULL,
 `Semester` int(11) NOT NULL,
 `Course_Code` varchar(25) NOT NULL,
 `Course_Name` varchar(100) NOT NULL,
 `Credits` int(11) DEFAULT 4,
 PRIMARY KEY (`Course_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=887 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

course_mapping	CREATE TABLE `course_mapping` (
 `Map_ID` int(11) NOT NULL AUTO_INCREMENT,
 `Course_ID` int(11) NOT NULL,
 `Fac_ID` varchar(50) NOT NULL,
 `Dept_ID` varchar(25) DEFAULT NULL,
 `Prog_ID` int(11) DEFAULT NULL,
 `Acad_Year` varchar(25) NOT NULL,
 PRIMARY KEY (`Map_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=652 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

department	CREATE TABLE `department` (
 `Dept_ID` varchar(25) NOT NULL,
 `Dept_Name` varchar(100) DEFAULT NULL,
 `Dept_Code` varchar(25) DEFAULT NULL,
 PRIMARY KEY (`Dept_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

exams	CREATE TABLE `exams` (
 `Exam_ID` int(11) NOT NULL AUTO_INCREMENT,
 `Course_ID` int(11) NOT NULL,
 `Fac_ID` varchar(50) NOT NULL,
 `Exam_Name` varchar(255) NOT NULL,
 `Total_Marks` int(11) NOT NULL DEFAULT 0,
 `Duration` int(11) NOT NULL COMMENT 'minutes',
 `Start_Time` datetime DEFAULT NULL,
 `End_Time` datetime DEFAULT NULL,
 `Status` enum('draft','published') NOT NULL DEFAULT 'draft',
 `Result_Status` int(5) DEFAULT NULL,
 `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
 `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 `Acad_Year` varchar(15) NOT NULL,
 PRIMARY KEY (`Exam_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

exam_questions	CREATE TABLE `exam_questions` (
 `Question_ID` int(11) NOT NULL AUTO_INCREMENT,
 `Exam_ID` int(11) NOT NULL,
 `Question_Type` enum('mcq','match','open','fill','multiselect') NOT NULL,
 `Question_Text` text NOT NULL,
 `Marks` decimal(5,1) NOT NULL DEFAULT 1.0,
 `Sort_Order` int(11) NOT NULL DEFAULT 0,
 `Options_JSON` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '[{letter,text},…]' CHECK (json_valid(`Options_JSON`)),
 `Correct_Opt` char(1) DEFAULT NULL COMMENT 'A/B/C/D',
 `Rubric` text DEFAULT NULL,
 `Word_Limit` int(11) DEFAULT NULL,
 `Answers_JSON` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '["ans1","ans2",…]' CHECK (json_valid(`Answers_JSON`)),
 `Pairs_JSON` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '[{a,b},…]' CHECK (json_valid(`Pairs_JSON`)),
 `Image_Path` varchar(500) DEFAULT NULL,
 `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
 `CO` int(11) NOT NULL DEFAULT 1,
 PRIMARY KEY (`Question_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

faculty	CREATE TABLE `faculty` (
 `Fac_ID` varchar(50) NOT NULL,
 `Fac_Name` varchar(50) DEFAULT NULL,
 `Fac_DOB` varchar(15) DEFAULT NULL,
 `Fac_Gender` varchar(50) DEFAULT NULL,
 `Fac_Email` varchar(50) DEFAULT NULL,
 `Fac_Mob` varchar(11) DEFAULT NULL,
 `Fac_Image` longblob DEFAULT NULL,
 `Fac_Dept` varchar(50) DEFAULT NULL,
 `Acad_Year` varchar(20) DEFAULT NULL,
 `Fac_Desg` varchar(50) DEFAULT NULL,
 PRIMARY KEY (`Fac_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

login	CREATE TABLE `login` (
 `ID` varchar(10) NOT NULL,
 `User_ID` varchar(50) NOT NULL,
 `Password` varchar(30) NOT NULL,
 `Role` varchar(10) NOT NULL,
 `Status` varchar(20) NOT NULL,
 PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci

new_question_bank	CREATE TABLE `new_question_bank` (
 `Bank_ID` int(11) NOT NULL AUTO_INCREMENT,
 `Course_ID` int(11) DEFAULT NULL,
 `Fac_ID` varchar(10) NOT NULL,
 `Question_Type` enum('mcq','match','open','fill','multiselect') NOT NULL,
 `Question_Text` text NOT NULL,
 `Marks` decimal(5,1) NOT NULL DEFAULT 1.0,
 `Options_JSON` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`Options_JSON`)),
 `Correct_Opt` char(1) DEFAULT NULL,
 `Rubric` text DEFAULT NULL,
 `Word_Limit` int(11) DEFAULT NULL,
 `Answers_JSON` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`Answers_JSON`)),
 `Pairs_JSON` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`Pairs_JSON`)),
 `Image_Path` varchar(500) DEFAULT NULL,
 `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
 `CO` int(11) NOT NULL DEFAULT 1,
 PRIMARY KEY (`Bank_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=658 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

password_resets	CREATE TABLE `password_resets` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `user_id` varchar(50) DEFAULT NULL,
 `token` varchar(255) DEFAULT NULL,
 `expires_at` datetime DEFAULT NULL,
 `created_at` timestamp NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1621 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci

programmes	CREATE TABLE `programmes` (
 `Prog_ID` int(11) NOT NULL AUTO_INCREMENT,
 `Dept_ID` varchar(10) NOT NULL,
 `Prog_Name` varchar(25) NOT NULL,
 PRIMARY KEY (`Prog_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

results	CREATE TABLE `results` (
 `Result_ID` int(11) NOT NULL AUTO_INCREMENT,
 `Exam_ID` int(11) NOT NULL,
 `Course_ID` int(11) DEFAULT NULL,
 `Stud_ID` varchar(50) NOT NULL,
 `Student_Name` varchar(150) DEFAULT NULL,
 `Total_Marks` decimal(6,2) NOT NULL DEFAULT 0.00,
 `Marks_Obtained` decimal(6,2) NOT NULL DEFAULT 0.00,
 `Percentage` decimal(5,2) GENERATED ALWAYS AS (case when `Total_Marks` > 0 then round(`Marks_Obtained` / `Total_Marks` * 100,2) else 0 end) STORED,
 `Time_Taken_Sec` int(11) NOT NULL DEFAULT 0,
 `Violations` int(11) NOT NULL DEFAULT 0,
 `Auto_Submitted` tinyint(1) NOT NULL DEFAULT 0,
 `Status` enum('pending_review','graded','published') NOT NULL DEFAULT 'pending_review',
 `Submitted_At` datetime NOT NULL DEFAULT current_timestamp(),
 `Graded_At` datetime DEFAULT NULL,
 PRIMARY KEY (`Result_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=148 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

result_answers	CREATE TABLE `result_answers` (
 `Answer_ID` int(11) NOT NULL AUTO_INCREMENT,
 `Result_ID` int(11) NOT NULL,
 `Question_ID` int(11) NOT NULL,
 `Question_Type` enum('mcq','fill','open','match','multiselect') NOT NULL,
 `Student_Answer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`Student_Answer`)),
 `Is_Correct` tinyint(1) DEFAULT NULL,
 `Marks_Awarded` decimal(5,2) NOT NULL DEFAULT 0.00,
 `Max_Marks` decimal(5,2) NOT NULL,
 `Answer_Status` enum('answered','visited','marked','not') NOT NULL DEFAULT 'not',
 `Grader_Comment` text DEFAULT NULL,
 `Graded_By` varchar(100) DEFAULT NULL,
 `Answer_Text` varchar(500) GENERATED ALWAYS AS (json_unquote(json_extract(`Student_Answer`,'$'))) VIRTUAL,
 PRIMARY KEY (`Answer_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=1213 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

student	CREATE TABLE `student` (
 `Stud_ID` varchar(50) NOT NULL,
 `Stud_Name` varchar(50) NOT NULL,
 `Stud_Email` varchar(50) NOT NULL,
 `Stud_Dept` varchar(25) NOT NULL,
 `Stud_Prog` varchar(50) NOT NULL,
 `Stud_Branch` varchar(25) NOT NULL,
 `Stud_Sem` int(10) NOT NULL DEFAULT 1,
 `Stud_Year` varchar(10) NOT NULL,
 `Curr_AY` varchar(20) DEFAULT NULL,
 PRIMARY KEY (`Stud_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci

student_course_mapping	CREATE TABLE `student_course_mapping` (
 `Map_ID` int(11) NOT NULL AUTO_INCREMENT,
 `Stud_ID` varchar(50) NOT NULL,
 `Course_ID` int(11) NOT NULL,
 `Fac_ID` varchar(50) NOT NULL,
 `Dept_ID` varchar(25) NOT NULL,
 `Semester` int(11) NOT NULL,
 `Acad_Year` varchar(20) NOT NULL,
 PRIMARY KEY (`Map_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=45479 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci