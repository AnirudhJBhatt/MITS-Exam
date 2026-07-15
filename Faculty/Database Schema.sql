<<<<<<< HEAD
<<<<<<< HEAD
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
=======
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
>>>>>>> f1e265abf03ca415a8e766b8518d8c076d9bf836
=======
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
>>>>>>> f1e265abf03ca415a8e766b8518d8c076d9bf836
);