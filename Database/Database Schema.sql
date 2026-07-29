-- ============================================================
--  Exam Builder – Database Schema
-- ============================================================
--
-- MIGRATION NOTE (for existing databases only):
-- The standalone 'diagram' question type has been removed. An image
-- can now be attached to mcq / open / fill questions instead (Image_Path
-- already existed and is now used as a generic optional attachment).
-- If you already have these tables created with the old ENUM that
-- included 'diagram', run the following BEFORE re-running this file,
-- after migrating any existing rows with Question_Type = 'diagram'
-- to whichever type fits them best (commonly 'open'):
--
--   UPDATE `exam_questions` SET `Question_Type` = 'open' WHERE `Question_Type` = 'diagram';
--   ALTER TABLE `exam_questions` MODIFY `Question_Type` ENUM('mcq','match','open','fill') NOT NULL;

--   UPDATE `question_bank` SET `Question_Type` = 'open' WHERE `Question_Type` = 'diagram';
--   ALTER TABLE `question_bank` MODIFY `Question_Type` ENUM('mcq','match','open','fill') NOT NULL;
-- ============================================================

-- Exams table

CREATE TABLE IF NOT EXISTS `exams` (
    `Exam_ID`    INT          NOT NULL AUTO_INCREMENT,
    `Course_ID`  INT          NOT NULL,
    `Fac_ID`     INT          NOT NULL,
    `Exam_Name`  VARCHAR(255) NOT NULL,
    `Duration`   INT          NOT NULL COMMENT 'minutes',
    `Start_Time` DATETIME     DEFAULT NULL,
    `End_Time`   DATETIME     DEFAULT NULL,
    `Status`     ENUM('draft','published') NOT NULL DEFAULT 'draft',
    `Created_At` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `Updated_At` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`Exam_ID`),
    KEY `idx_course` (`Course_ID`),
    KEY `idx_faculty` (`Fac_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Questions attached to an exam

CREATE TABLE IF NOT EXISTS `exam_questions` (
    `Question_ID`   INT           NOT NULL AUTO_INCREMENT,
    `Exam_ID`       INT           NOT NULL,
    `Question_Type` ENUM('mcq','match','open','fill') NOT NULL,
    `Question_Text` TEXT          NOT NULL,
    `Marks`         DECIMAL(5,1)  NOT NULL DEFAULT 1,
    `CO`            INT           NOT NULL DEFAULT 1,
    `Sort_Order`    INT           NOT NULL DEFAULT 0,

    -- MCQ
    `Options_JSON`  JSON          DEFAULT NULL COMMENT '[{letter,text},…]',
    `Correct_Opt`   CHAR(1)       DEFAULT NULL COMMENT 'A/B/C/D',

    -- Open-ended
    `Rubric`        TEXT          DEFAULT NULL,
    `Word_Limit`    INT           DEFAULT NULL,

    -- Fill in the blanks
    `Answers_JSON`  JSON          DEFAULT NULL COMMENT '["ans1","ans2",…]',

    -- Match the following
    `Pairs_JSON`    JSON          DEFAULT NULL COMMENT '[{a,b},…]',

    -- Optional reference / diagram image — available on mcq, open, fill
    -- (NOT applicable to match, since it has its own two-column layout)
    `Image_Path`    VARCHAR(500)  DEFAULT NULL,

    `Created_At`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`Question_ID`),
    KEY `idx_exam` (`Exam_ID`),
    CONSTRAINT `fk_eq_exam` FOREIGN KEY (`Exam_ID`)
        REFERENCES `exams` (`Exam_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Standalone question bank (reusable questions, not tied to one exam)

CREATE TABLE IF NOT EXISTS `question_bank` (
    `Bank_ID`       INT           NOT NULL AUTO_INCREMENT,
    `Course_ID`     INT           DEFAULT NULL,
    `Fac_ID`        INT           NOT NULL,
    `Question_Type` ENUM('mcq','match','open','fill') NOT NULL,
    `Question_Text` TEXT          NOT NULL,
    `Marks`         DECIMAL(5,1)  NOT NULL DEFAULT 1,
    `CO`            INT           NOT NULL DEFAULT 1,

    -- MCQ
    `Options_JSON`  JSON          DEFAULT NULL,
    `Correct_Opt`   CHAR(1)       DEFAULT NULL,

    -- Open-ended
    `Rubric`        TEXT          DEFAULT NULL,
    `Word_Limit`    INT           DEFAULT NULL,

    -- Fill in the blanks
    `Answers_JSON`  JSON          DEFAULT NULL,

    -- Match the following
    `Pairs_JSON`    JSON          DEFAULT NULL,

    -- Optional reference / diagram image — available on mcq, open, fill
    `Image_Path`    VARCHAR(500)  DEFAULT NULL,

    `Created_At`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`Bank_ID`),
    KEY `idx_bank_course`  (`Course_ID`),
    KEY `idx_bank_faculty` (`Fac_ID`),
    FULLTEXT KEY `ft_question_text` (`Question_Text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Results

CREATE TABLE results (
    Result_ID       INT AUTO_INCREMENT PRIMARY KEY,
    Exam_ID         INT NOT NULL,
    Stud_ID    VARCHAR(50) NOT NULL,
    Student_Name    VARCHAR(150) DEFAULT NULL,
    Total_Marks     DECIMAL(6,2) NOT NULL DEFAULT 0,
    Marks_Obtained  DECIMAL(6,2) NOT NULL DEFAULT 0,
    Percentage      DECIMAL(5,2) GENERATED ALWAYS AS (
                        CASE WHEN Total_Marks > 0 
                             THEN ROUND((Marks_Obtained / Total_Marks) * 100, 2) 
                             ELSE 0 END
                    ) STORED,
    Time_Taken_Sec  INT NOT NULL DEFAULT 0,
    Violations      INT NOT NULL DEFAULT 0,
    Auto_Submitted  TINYINT(1) NOT NULL DEFAULT 0,
    Status          ENUM('pending_review','graded','published') NOT NULL DEFAULT 'pending_review',
    Submitted_At    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Graded_At       DATETIME DEFAULT NULL,

    FOREIGN KEY (Exam_ID) REFERENCES exams(Exam_ID) ON DELETE CASCADE,
    UNIQUE KEY uniq_attempt (Exam_ID, Stud_ID)
);

-- Result Details

CREATE TABLE result_answers (
    Answer_ID         INT AUTO_INCREMENT PRIMARY KEY,
    Result_ID         INT NOT NULL,
    Question_ID       INT NOT NULL,
    Question_Type     ENUM('mcq','fill','open','match') NOT NULL,
    Student_Answer    JSON DEFAULT NULL,
    Is_Correct        TINYINT(1) DEFAULT NULL,
    Marks_Awarded     DECIMAL(5,2) NOT NULL DEFAULT 0,
    Max_Marks         DECIMAL(5,2) NOT NULL,
    Answer_Status     ENUM('answered','visited','marked','not') NOT NULL DEFAULT 'not',
    Grader_Comment    TEXT DEFAULT NULL,
    Graded_By         VARCHAR(100) DEFAULT NULL,

    FOREIGN KEY (Result_ID) REFERENCES results(Result_ID) ON DELETE CASCADE,
    FOREIGN KEY (Question_ID) REFERENCES exam_questions(Question_ID) ON DELETE CASCADE,
    UNIQUE KEY uniq_result_question (Result_ID, Question_ID),

    -- Generated/virtual column lets you index inside the JSON for fast lookups
    Answer_Text VARCHAR(500) GENERATED ALWAYS AS (
        JSON_UNQUOTE(JSON_EXTRACT(Student_Answer, '$'))
    ) VIRTUAL

);

-- academic_year	
CREATE TABLE `academic_year` (
 `AY_ID` int(11) NOT NULL AUTO_INCREMENT,
 `AY_Name` varchar(20) NOT NULL,
 PRIMARY KEY (`AY_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci


-- courses	
CREATE TABLE `courses` (
 `Course_ID` int(11) NOT NULL AUTO_INCREMENT,
 `Dept_ID` varchar(10) NOT NULL,
 `Prog_ID` int(11) NOT NULL,
 `Course_Year` varchar(10) NOT NULL,
 `Semester` int(11) NOT NULL,
 `Course_Code` varchar(25) NOT NULL,
 `Course_Name` varchar(100) NOT NULL,
 `Credits` int(11) DEFAULT 4,
 PRIMARY KEY (`Course_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=880 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

-- course_mapping
CREATE TABLE `course_mapping` (
 `Map_ID` int(11) NOT NULL AUTO_INCREMENT,
 `Course_ID` int(11) NOT NULL,
 `Fac_ID` varchar(25) NOT NULL,
 `Dept_ID` varchar(20) DEFAULT NULL,
 `Acad_Year` varchar(25) NOT NULL,
 PRIMARY KEY (`Map_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=478 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci


-- department	
CREATE TABLE `department` (
 `Dept_ID` varchar(25) NOT NULL,
 `Dept_Name` varchar(100) DEFAULT NULL,
 `Dept_Code` varchar(25) DEFAULT NULL,
 PRIMARY KEY (`Dept_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci


-- exam	
CREATE TABLE `exam` (
 `Exam_ID` int(11) NOT NULL AUTO_INCREMENT,
 `Fac_ID` varchar(15) NOT NULL,
 `Course_ID` varchar(100) DEFAULT NULL,
 `Exam_Title` varchar(100) NOT NULL,
 `Exam_Type` enum('Statement Filling','Matching','MCQ','Hint Based','Case Study','Cheat Sheet','Open Ended','Chain Question','Diagram','Code') DEFAULT NULL,
 `Dept` varchar(50) NOT NULL,
 `Batch` varchar(50) DEFAULT NULL,
 `Prog_ID` int(11) NOT NULL,
 `Acad_Year` varchar(25) NOT NULL,
 `Semester` int(11) NOT NULL,
 `Q_IDs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
 `Total_Marks` int(11) DEFAULT 0,
 `Duration` int(11) DEFAULT 60,
 `Start_Time` datetime DEFAULT NULL,
 `End_Time` datetime DEFAULT NULL,
 `Exam_Status` int(1) DEFAULT NULL,
 `Result_Status` tinyint(1) DEFAULT NULL,
 PRIMARY KEY (`Exam_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=384 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

-- exams	
CREATE TABLE `exams` (
 `Exam_ID` int(11) NOT NULL,
 `Course_ID` int(11) NOT NULL,
 `Fac_ID` varchar(50) NOT NULL,
 `Exam_Name` varchar(255) NOT NULL,
 `Duration` int(11) NOT NULL COMMENT 'minutes',
 `Start_Time` datetime DEFAULT NULL,
 `End_Time` datetime DEFAULT NULL,
 `Status` enum('draft','published') NOT NULL DEFAULT 'draft',
 `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
 `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci


-- exam_questions	
CREATE TABLE `exam_questions` (
 `Question_ID` int(11) NOT NULL,
 `Exam_ID` int(11) NOT NULL,
 `Question_Type` enum('mcq','match','open','fill') NOT NULL,
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
 `CO` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

-- faculty	
CREATE TABLE `faculty` (
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

-- login	
CREATE TABLE `login` (
 `ID` varchar(10) NOT NULL,
 `User_ID` varchar(50) NOT NULL,
 `Password` varchar(30) NOT NULL,
 `Role` varchar(10) NOT NULL,
 `Status` varchar(20) NOT NULL,
 PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci

-- new_table	
CREATE TABLE `new_table` (
 `Q_ID` int(11) NOT NULL DEFAULT 0,
 `Type_ID` int(11) DEFAULT NULL,
 `Subject_Code` int(11) NOT NULL,
 `Question_Text` text NOT NULL,
 `Options` longtext DEFAULT NULL,
 `Correct_Answer` longtext DEFAULT NULL,
 `Marks` int(11) DEFAULT 1,
 `Hint` text DEFAULT NULL,
 `Cheat_Sheet` varchar(255) DEFAULT NULL,
 `Next_if_Correct` int(11) DEFAULT NULL,
 `Next_if_Wrong` int(11) DEFAULT NULL,
 `Case_ID` int(11) DEFAULT NULL,
 `Diagram_Image` longblob DEFAULT NULL,
 `Code_Snippet` text DEFAULT NULL,
 `Explanation` text DEFAULT NULL,
 `Is_Math` tinyint(4) NOT NULL DEFAULT 0,
 `CO` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

-- password_resets	
CREATE TABLE `password_resets` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `user_id` varchar(50) DEFAULT NULL,
 `token` varchar(255) DEFAULT NULL,
 `expires_at` datetime DEFAULT NULL,
 `created_at` timestamp NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1578 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci

-- programmes	
CREATE TABLE `programmes` (
 `Prog_ID` int(11) NOT NULL AUTO_INCREMENT,
 `Dept_ID` varchar(10) NOT NULL,
 `Prog_Name` varchar(25) NOT NULL,
 PRIMARY KEY (`Prog_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci


-- question_bank	
CREATE TABLE `question_bank` (
 `Q_ID` int(11) NOT NULL AUTO_INCREMENT,
 `Type_ID` int(11) DEFAULT NULL,
 `Subject_Code` int(11) NOT NULL,
 `Question_Text` text NOT NULL,
 `Options` longtext DEFAULT NULL CHECK (json_valid(`Options`)),
 `Correct_Answer` longtext DEFAULT NULL CHECK (json_valid(`Correct_Answer`)),
 `Marks` int(11) DEFAULT 1,
 `Hint` text DEFAULT NULL,
 `Cheat_Sheet` varchar(255) DEFAULT NULL,
 `Next_if_Correct` int(11) DEFAULT NULL,
 `Next_if_Wrong` int(11) DEFAULT NULL,
 `Case_ID` int(11) DEFAULT NULL,
 `Diagram_Image` longblob DEFAULT NULL,
 `Code_Snippet` text DEFAULT NULL,
 `Explanation` text DEFAULT NULL,
 `Is_Math` tinyint(4) NOT NULL DEFAULT 0,
 `CO` int(11) NOT NULL,
 PRIMARY KEY (`Q_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=7002 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

-- question_bank_new	
CREATE TABLE `question_bank_new` (
 `Q_ID` int(11) NOT NULL DEFAULT 0,
 `Type_ID` int(11) DEFAULT NULL,
 `Subject_Code` int(11) NOT NULL,
 `Question_Text` text NOT NULL,
 `Options` longtext DEFAULT NULL,
 `Correct_Answer` longtext DEFAULT NULL,
 `Marks` int(11) DEFAULT 1,
 `Hint` text DEFAULT NULL,
 `Cheat_Sheet` varchar(255) DEFAULT NULL,
 `Next_if_Correct` int(11) DEFAULT NULL,
 `Next_if_Wrong` int(11) DEFAULT NULL,
 `Case_ID` int(11) DEFAULT NULL,
 `Diagram_Image` longblob DEFAULT NULL,
 `Code_Snippet` text DEFAULT NULL,
 `Explanation` text DEFAULT NULL,
 `Is_Math` tinyint(4) NOT NULL DEFAULT 0,
 `CO` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

-- question_type	
CREATE TABLE `question_type` (
 `Type_ID` int(11) NOT NULL,
 `Type_Name` varchar(50) NOT NULL,
 `Auto_Evaluated` tinyint(1) DEFAULT 1,
 `Description` text DEFAULT NULL,
 PRIMARY KEY (`Type_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci


-- result	
CREATE TABLE `result` (
 `Result_ID` int(11) NOT NULL AUTO_INCREMENT,
 `Stud_ID` varchar(50) DEFAULT NULL,
 `Exam_ID` int(11) DEFAULT NULL,
 `Obtained_Marks` float DEFAULT NULL,
 `Total_Marks` float DEFAULT NULL,
 `Details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`Details`)),
 `CO_Details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`CO_Details`)),
 `Submitted_At` datetime DEFAULT current_timestamp(),
 PRIMARY KEY (`Result_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=12528 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci


-- results	
CREATE TABLE `results` (
 `Result_ID` int(11) NOT NULL,
 `Exam_ID` int(11) NOT NULL,
 `Course_ID` int(11) NOT NULL,
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
 `Graded_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

-- result_answers	
CREATE TABLE `result_answers` (
 `Answer_ID` int(11) NOT NULL,
 `Result_ID` int(11) NOT NULL,
 `Question_ID` int(11) NOT NULL,
 `Question_Type` enum('mcq','fill','open','match') NOT NULL,
 `Student_Answer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`Student_Answer`)),
 `Is_Correct` tinyint(1) DEFAULT NULL,
 `Marks_Awarded` decimal(5,2) NOT NULL DEFAULT 0.00,
 `Max_Marks` decimal(5,2) NOT NULL,
 `Answer_Status` enum('answered','visited','marked','not') NOT NULL DEFAULT 'not',
 `Grader_Comment` text DEFAULT NULL,
 `Graded_By` varchar(100) DEFAULT NULL,
 `Answer_Text` varchar(500) GENERATED ALWAYS AS (json_unquote(json_extract(`Student_Answer`,'$'))) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

-- result_backup	
CREATE TABLE `result_backup` (
 `Result_ID` int(11) NOT NULL DEFAULT 0,
 `Stud_ID` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
  DEFAULT NULL,
 `Exam_ID` int(11) DEFAULT NULL,
 `Obtained_Marks` float DEFAULT NULL,
 `Total_Marks` float DEFAULT NULL,
 `Details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
 `CO_Details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
 `Submitted_At` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

-- student	
CREATE TABLE `student` (
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

-- student_course_mapping	
CREATE TABLE `student_course_mapping` (
 `Map_ID` int(11) NOT NULL AUTO_INCREMENT,
 `Stud_ID` varchar(20) NOT NULL,
 `Course_ID` int(11) NOT NULL,
 `Fac_ID` varchar(20) NOT NULL,
 `Dept_ID` varchar(20) NOT NULL,
 `Semester` int(11) NOT NULL,
 `Acad_Year` varchar(20) NOT NULL,
 PRIMARY KEY (`Map_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=27852 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
