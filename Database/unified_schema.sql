-- ============================================================
-- MITS Internal Assessment Portal - Unified Database Schema v2.0
-- High-Performance, Relational Schema with OBE & Anti-Cheat Support
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:30";

-- ------------------------------------------------------------
-- 1. ACADEMIC YEARS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `academic_year` (
    `AY_ID` INT AUTO_INCREMENT PRIMARY KEY,
    `AY_Name` VARCHAR(20) NOT NULL UNIQUE,
    `Is_Active` TINYINT(1) NOT NULL DEFAULT 0,
    `Created_At` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. DEPARTMENTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `department` (
    `Dept_ID` VARCHAR(25) NOT NULL PRIMARY KEY,
    `Dept_Name` VARCHAR(100) NOT NULL,
    `Dept_Code` VARCHAR(25) DEFAULT NULL,
    `Created_At` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. PROGRAMMES (Degrees / Specializations)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `programmes` (
    `Prog_ID` INT AUTO_INCREMENT PRIMARY KEY,
    `Dept_ID` VARCHAR(25) NOT NULL,
    `Prog_Name` VARCHAR(50) NOT NULL,
    `Created_At` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_prog_dept` FOREIGN KEY (`Dept_ID`) 
        REFERENCES `department` (`Dept_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. COURSES (Subjects)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `courses` (
    `Course_ID` INT AUTO_INCREMENT PRIMARY KEY,
    `Dept_ID` VARCHAR(25) NOT NULL,
    `Prog_ID` INT NOT NULL,
    `Course_Year` VARCHAR(10) NOT NULL,
    `Semester` INT NOT NULL,
    `Course_Code` VARCHAR(25) NOT NULL,
    `Course_Name` VARCHAR(120) NOT NULL,
    `Credits` INT DEFAULT 4,
    `Created_At` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_course_dept` (`Dept_ID`),
    KEY `idx_course_prog` (`Prog_ID`),
    CONSTRAINT `fk_course_dept` FOREIGN KEY (`Dept_ID`) 
        REFERENCES `department` (`Dept_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_course_prog` FOREIGN KEY (`Prog_ID`) 
        REFERENCES `programmes` (`Prog_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. FACULTY DIRECTORY
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `faculty` (
    `Fac_ID` VARCHAR(50) NOT NULL PRIMARY KEY,
    `Fac_Name` VARCHAR(100) NOT NULL,
    `Fac_DOB` VARCHAR(20) DEFAULT NULL,
    `Fac_Gender` VARCHAR(20) DEFAULT NULL,
    `Fac_Email` VARCHAR(100) NOT NULL UNIQUE,
    `Fac_Mob` VARCHAR(20) DEFAULT NULL,
    `Fac_Image` LONGBLOB DEFAULT NULL,
    `Fac_Dept` VARCHAR(25) DEFAULT NULL,
    `Acad_Year` VARCHAR(25) DEFAULT NULL,
    `Fac_Desg` VARCHAR(60) DEFAULT NULL,
    `Created_At` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_faculty_dept` (`Fac_Dept`),
    CONSTRAINT `fk_fac_dept` FOREIGN KEY (`Fac_Dept`) 
        REFERENCES `department` (`Dept_ID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. STUDENTS DIRECTORY
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student` (
    `Stud_ID` VARCHAR(50) NOT NULL PRIMARY KEY,
    `Stud_Name` VARCHAR(100) NOT NULL,
    `Stud_Email` VARCHAR(100) NOT NULL UNIQUE,
    `Stud_Dept` VARCHAR(25) NOT NULL,
    `Stud_Prog` VARCHAR(50) NOT NULL,
    `Stud_Branch` VARCHAR(50) NOT NULL,
    `Stud_Sem` INT NOT NULL DEFAULT 1,
    `Stud_Year` VARCHAR(10) NOT NULL,
    `Curr_AY` VARCHAR(25) DEFAULT NULL,
    `Created_At` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_stud_dept` (`Stud_Dept`),
    CONSTRAINT `fk_stud_dept` FOREIGN KEY (`Stud_Dept`) 
        REFERENCES `department` (`Dept_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. COURSE-FACULTY ALLOCATION (Mapping)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `course_mapping` (
    `Map_ID` INT AUTO_INCREMENT PRIMARY KEY,
    `Course_ID` INT NOT NULL,
    `Fac_ID` VARCHAR(50) NOT NULL,
    `Dept_ID` VARCHAR(25) DEFAULT NULL,
    `Acad_Year` VARCHAR(25) NOT NULL,
    `Created_At` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_cmap_course` (`Course_ID`),
    KEY `idx_cmap_faculty` (`Fac_ID`),
    CONSTRAINT `fk_cmap_course` FOREIGN KEY (`Course_ID`) 
        REFERENCES `courses` (`Course_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cmap_faculty` FOREIGN KEY (`Fac_ID`) 
        REFERENCES `faculty` (`Fac_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. STUDENT-COURSE ENROLLMENT
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_course_mapping` (
    `Map_ID` INT AUTO_INCREMENT PRIMARY KEY,
    `Stud_ID` VARCHAR(50) NOT NULL,
    `Course_ID` INT NOT NULL,
    `Fac_ID` VARCHAR(50) DEFAULT NULL,
    `Dept_ID` VARCHAR(25) NOT NULL,
    `Semester` INT NOT NULL,
    `Acad_Year` VARCHAR(25) NOT NULL,
    `Created_At` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_enrollment` (`Stud_ID`, `Course_ID`, `Acad_Year`),
    KEY `idx_sc_stud` (`Stud_ID`),
    KEY `idx_sc_course` (`Course_ID`),
    CONSTRAINT `fk_sc_stud` FOREIGN KEY (`Stud_ID`) 
        REFERENCES `student` (`Stud_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sc_course` FOREIGN KEY (`Course_ID`) 
        REFERENCES `courses` (`Course_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. LOGIN & AUTHENTICATION (Bcrypt 255-char hash)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login` (
    `ID` VARCHAR(50) NOT NULL,
    `User_ID` VARCHAR(100) NOT NULL UNIQUE,
    `Password` VARCHAR(255) NOT NULL,
    `Role` ENUM('Admin', 'DeptAdmin', 'Faculty', 'Student') NOT NULL,
    `Status` VARCHAR(20) NOT NULL DEFAULT 'Activate',
    `Last_Login` DATETIME DEFAULT NULL,
    `Created_At` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10. PASSWORD RESETS (Secure Tokens)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(100) NOT NULL,
    `token` VARCHAR(255) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_reset_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 11. NEW QUESTION BANK (Reusable Course-Mapped Questions)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `new_question_bank` (
    `Bank_ID` INT AUTO_INCREMENT PRIMARY KEY,
    `Course_ID` INT DEFAULT NULL,
    `Fac_ID` VARCHAR(50) NOT NULL,
    `Question_Type` ENUM('mcq', 'match', 'open', 'fill', 'multiselect') NOT NULL,
    `Question_Text` LONGTEXT NOT NULL,
    `Marks` DECIMAL(5,1) NOT NULL DEFAULT 1.0,
    `CO` INT NOT NULL DEFAULT 1 COMMENT 'Course Outcome (1-5)',
    `Bloom_Level` VARCHAR(20) DEFAULT 'Understand' COMMENT 'Remember, Understand, Apply, etc.',
    `Options_JSON` JSON DEFAULT NULL COMMENT '[{letter, text}]',
    `Correct_Opt` VARCHAR(50) DEFAULT NULL COMMENT 'A, B or JSON array for multiselect',
    `Rubric` TEXT DEFAULT NULL,
    `Word_Limit` INT DEFAULT NULL,
    `Answers_JSON` JSON DEFAULT NULL COMMENT 'Accepted strings for fill in blanks',
    `Pairs_JSON` JSON DEFAULT NULL COMMENT '[{a, b}] for matching pairs',
    `Image_Path` VARCHAR(500) DEFAULT NULL,
    `Created_At` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_nqb_course` (`Course_ID`),
    KEY `idx_nqb_fac` (`Fac_ID`),
    FULLTEXT KEY `ft_nqb_text` (`Question_Text`),
    CONSTRAINT `fk_nqb_course` FOREIGN KEY (`Course_ID`) 
        REFERENCES `courses` (`Course_ID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 12. EXAMS (Assessments Header & Configuration)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exams` (
    `Exam_ID` INT AUTO_INCREMENT PRIMARY KEY,
    `Course_ID` INT NOT NULL,
    `Fac_ID` VARCHAR(50) NOT NULL,
    `Exam_Name` VARCHAR(255) NOT NULL,
    `Exam_Type` VARCHAR(50) DEFAULT 'Internal Assessment',
    `Duration` INT NOT NULL COMMENT 'Minutes',
    `Start_Time` DATETIME DEFAULT NULL,
    `End_Time` DATETIME DEFAULT NULL,
    `Shuffle_Questions` TINYINT(1) NOT NULL DEFAULT 0,
    `Shuffle_Options` TINYINT(1) NOT NULL DEFAULT 0,
    `Status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    `Result_Published` TINYINT(1) NOT NULL DEFAULT 0,
    `Created_At` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `Updated_At` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_exam_course` (`Course_ID`),
    KEY `idx_exam_fac` (`Fac_ID`),
    CONSTRAINT `fk_exam_course` FOREIGN KEY (`Course_ID`) 
        REFERENCES `courses` (`Course_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 13. EXAM QUESTIONS (Questions bound to an Exam)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_questions` (
    `Question_ID` INT AUTO_INCREMENT PRIMARY KEY,
    `Exam_ID` INT NOT NULL,
    `Question_Type` ENUM('mcq', 'match', 'open', 'fill', 'multiselect') NOT NULL,
    `Question_Text` LONGTEXT NOT NULL,
    `Marks` DECIMAL(5,1) NOT NULL DEFAULT 1.0,
    `CO` INT NOT NULL DEFAULT 1 COMMENT 'Course Outcome (1-5)',
    `Sort_Order` INT NOT NULL DEFAULT 0,
    `Options_JSON` JSON DEFAULT NULL,
    `Correct_Opt` VARCHAR(50) DEFAULT NULL,
    `Rubric` TEXT DEFAULT NULL,
    `Word_Limit` INT DEFAULT NULL,
    `Answers_JSON` JSON DEFAULT NULL,
    `Pairs_JSON` JSON DEFAULT NULL,
    `Image_Path` VARCHAR(500) DEFAULT NULL,
    `Created_At` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_eq_exam` (`Exam_ID`),
    CONSTRAINT `fk_eq_exam` FOREIGN KEY (`Exam_ID`) 
        REFERENCES `exams` (`Exam_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 14. EXAM RESULTS (Student Attempt Header)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `results` (
    `Result_ID` INT AUTO_INCREMENT PRIMARY KEY,
    `Exam_ID` INT NOT NULL,
    `Stud_ID` VARCHAR(50) NOT NULL,
    `Student_Name` VARCHAR(150) DEFAULT NULL,
    `Total_Marks` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    `Marks_Obtained` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    `Percentage` DECIMAL(5,2) GENERATED ALWAYS AS (
        CASE WHEN `Total_Marks` > 0 THEN ROUND((`Marks_Obtained` / `Total_Marks`) * 100, 2) ELSE 0.00 END
    ) STORED,
    `Attempt_Started_At` DATETIME DEFAULT NULL,
    `Submitted_At` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `Time_Taken_Sec` INT NOT NULL DEFAULT 0,
    `Violations` INT NOT NULL DEFAULT 0,
    `Auto_Submitted` TINYINT(1) NOT NULL DEFAULT 0,
    `Status` ENUM('in_progress', 'pending_review', 'graded', 'published') NOT NULL DEFAULT 'pending_review',
    `Graded_At` DATETIME DEFAULT NULL,
    UNIQUE KEY `uniq_student_exam_attempt` (`Exam_ID`, `Stud_ID`),
    KEY `idx_res_exam` (`Exam_ID`),
    KEY `idx_res_stud` (`Stud_ID`),
    CONSTRAINT `fk_res_exam` FOREIGN KEY (`Exam_ID`) 
        REFERENCES `exams` (`Exam_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_res_stud` FOREIGN KEY (`Stud_ID`) 
        REFERENCES `student` (`Stud_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 15. RESULT ANSWERS (Per-Question Itemized Responses)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `result_answers` (
    `Answer_ID` INT AUTO_INCREMENT PRIMARY KEY,
    `Result_ID` INT NOT NULL,
    `Question_ID` INT NOT NULL,
    `Question_Type` ENUM('mcq', 'match', 'open', 'fill', 'multiselect') NOT NULL,
    `Student_Answer` JSON DEFAULT NULL,
    `Is_Correct` TINYINT(1) DEFAULT NULL,
    `Marks_Awarded` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `Max_Marks` DECIMAL(5,2) NOT NULL,
    `Answer_Status` ENUM('answered', 'visited', 'marked', 'not') NOT NULL DEFAULT 'not',
    `Grader_Comment` TEXT DEFAULT NULL,
    `Graded_By` VARCHAR(100) DEFAULT NULL,
    `Updated_At` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_result_question` (`Result_ID`, `Question_ID`),
    KEY `idx_ra_result` (`Result_ID`),
    KEY `idx_ra_question` (`Question_ID`),
    CONSTRAINT `fk_ra_result` FOREIGN KEY (`Result_ID`) 
        REFERENCES `results` (`Result_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ra_question` FOREIGN KEY (`Question_ID`) 
        REFERENCES `exam_questions` (`Question_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 16. EXAM DRAFTS (Student Real-Time Autosave Buffer)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_drafts` (
    `Draft_ID` INT AUTO_INCREMENT PRIMARY KEY,
    `Exam_ID` INT NOT NULL,
    `Stud_ID` VARCHAR(50) NOT NULL,
    `Question_ID` INT NOT NULL,
    `Student_Answer` LONGTEXT DEFAULT NULL,
    `Answer_Status` VARCHAR(20) DEFAULT 'answered',
    `Updated_At` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_draft` (`Exam_ID`, `Stud_ID`, `Question_ID`),
    KEY `idx_draft_exam` (`Exam_ID`),
    KEY `idx_draft_stud` (`Stud_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 17. SAFE MIGRATION COMMANDS FOR EXISTING DATABASES
-- ------------------------------------------------------------
-- Run these if updating an existing schema:
ALTER TABLE `login` MODIFY `Password` VARCHAR(255) NOT NULL;
ALTER TABLE `exams` ADD COLUMN IF NOT EXISTS `Shuffle_Questions` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `exams` ADD COLUMN IF NOT EXISTS `Shuffle_Options` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `exams` ADD COLUMN IF NOT EXISTS `Result_Published` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `results` ADD COLUMN IF NOT EXISTS `Attempt_Started_At` DATETIME DEFAULT NULL;

SET FOREIGN_KEY_CHECKS = 1;

