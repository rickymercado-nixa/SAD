-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2025 at 01:50 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pnhs`
--

-- --------------------------------------------------------

--
-- Table structure for table `cheating_logs`
--

CREATE TABLE `cheating_logs` (
  `log_id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `event_type` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cheating_logs`
--

INSERT INTO `cheating_logs` (`log_id`, `exam_id`, `student_id`, `event_type`, `timestamp`) VALUES
(382, 28, 21, 'Tab switch detected', '2025-05-12 02:23:05');

-- --------------------------------------------------------

--
-- Table structure for table `choices`
--

CREATE TABLE `choices` (
  `choice_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `choice_text` text NOT NULL,
  `is_correct` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `choices`
--

INSERT INTO `choices` (`choice_id`, `question_id`, `choice_text`, `is_correct`) VALUES
(335, 118, 'asd', 1),
(336, 118, 'asd', 0),
(337, 118, 'asd', 0),
(338, 118, 'asd', 0),
(380, 136, '2', 1),
(381, 136, '4', 0),
(382, 136, '3', 0),
(383, 136, '5', 0),
(388, 138, '4', 1),
(389, 138, 'Four', 1),
(390, 138, '6', 0),
(391, 138, '2', 0);

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `sub_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `sub_id`, `enrolled_at`) VALUES
(1, 11, 7, '2025-03-20 00:46:17'),
(2, 14, 7, '2025-03-20 01:16:27'),
(3, 11, 6, '2025-03-28 11:42:45'),
(4, 15, 6, '2025-03-30 22:54:17'),
(5, 16, 6, '2025-03-30 23:06:20'),
(6, 17, 6, '2025-04-02 10:44:21'),
(7, 18, 6, '2025-04-02 11:01:39'),
(8, 21, 14, '2025-04-07 14:56:14'),
(9, 21, 7, '2025-04-08 03:27:58'),
(10, 24, 7, '2025-04-08 05:36:07'),
(11, 24, 14, '2025-04-08 05:36:25'),
(12, 21, 16, '2025-04-30 11:42:48'),
(13, 21, 17, '2025-05-01 05:14:20'),
(14, 21, 15, '2025-05-07 10:46:39');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `exam_id` int(11) NOT NULL,
  `exam_name` varchar(255) NOT NULL,
  `sub_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `total_marks` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('Upcoming','Ongoing','Completed') DEFAULT 'Upcoming'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`exam_id`, `exam_name`, `sub_id`, `teacher_id`, `total_marks`, `start_time`, `end_time`, `status`) VALUES
(17, 'First Quarter Exam', 6, 12, 10, '2025-04-27 10:47:00', '2025-04-28 10:47:00', 'Completed'),
(24, 'First Quarter Exam - Mathematics', 14, 19, 50, '2025-05-12 06:35:00', '2025-05-12 07:35:00', 'Completed'),
(25, 'First Quarter Exam - Science', 15, 19, 50, '2025-05-07 19:36:00', '2025-05-07 20:36:00', 'Completed'),
(27, 'First Quarter Exam - Physics', 16, 19, 50, '2025-05-07 18:36:00', '2025-05-07 18:37:00', 'Completed'),
(28, 'First Quarter Exam - Science', 15, 19, 50, '2025-05-12 16:21:00', '2025-05-12 18:21:00', 'Completed'),
(29, 'First Quarter Exam', 14, 19, 50, '2025-05-13 07:38:00', '2025-05-13 09:38:00', 'Ongoing');

-- --------------------------------------------------------

--
-- Table structure for table `exam_part_timers`
--

CREATE TABLE `exam_part_timers` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `question_part` varchar(50) DEFAULT NULL,
  `timer` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_part_timers`
--

INSERT INTO `exam_part_timers` (`id`, `exam_id`, `question_part`, `timer`) VALUES
(24, 17, 'Test 1', 5),
(36, 24, 'Test 1', 15),
(37, 28, 'Test 4', 10);

-- --------------------------------------------------------

--
-- Table structure for table `exam_submissions`
--

CREATE TABLE `exam_submissions` (
  `examsub_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `submit_time` datetime NOT NULL,
  `taken` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_submissions`
--

INSERT INTO `exam_submissions` (`examsub_id`, `student_id`, `exam_id`, `submit_time`, `taken`) VALUES
(107, 21, 24, '2025-05-07 18:47:29', 1),
(108, 21, 28, '2025-05-12 16:23:38', 1);

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `question_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_part` enum('Test 1','Test 2','Test 3','Test 4') NOT NULL,
  `question_type` enum('Multiple Choice','True/False','Fill in the Blanks','Essay') NOT NULL,
  `marks` int(11) NOT NULL,
  `timer` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`question_id`, `exam_id`, `question_text`, `question_part`, `question_type`, `marks`, `timer`) VALUES
(118, 17, 'asd', 'Test 1', 'Multiple Choice', 10, 5),
(136, 24, '1 + 1 =', 'Test 1', 'Multiple Choice', 1, 15),
(138, 24, '2 + 2 =', 'Test 1', 'Multiple Choice', 1, 15),
(139, 28, 'Who Am I?', 'Test 4', 'Essay', 50, 10);

-- --------------------------------------------------------

--
-- Table structure for table `student_answers`
--

CREATE TABLE `student_answers` (
  `students_answer_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `marks_obtained` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_answers`
--

INSERT INTO `student_answers` (`students_answer_id`, `student_id`, `exam_id`, `question_id`, `answer_text`, `is_correct`, `marks_obtained`) VALUES
(591, 21, 24, 136, '380', 1, 1),
(592, 21, 24, 138, '389', 1, 1),
(593, 21, 28, 139, 'A paragraph is a series of sentences that are organized and coherent, and are all related to a single topic. Almost every piece of writing you do that is longer than a few sentences should be organized into paragraphs. This is because paragraphs show a reader where the subdivisions of an essay begin and end, and thus help the reader see the organization of the essay and grasp its main points.\r\n\r\nParagraphs can contain many different kinds of information. A paragraph could contain a series of brief examples or a single long illustration of a general point. It might describe a place, character, or process; narrate a series of events; compare or contrast two or more things; classify items into categories; or describe causes and effects. Regardless of the kind of information they contain, all paragraphs share certain characteristics. One of the most important of these is a topic sentence.\r\n\r\nTOPIC SENTENCES\r\nA well-organized paragraph supports or develops a single controlling idea, which is expressed in a sentence called the topic sentence. A topic sentence has several important functions: it substantiates or supports an essay’s thesis statement; it unifies the content of a paragraph and directs the order of the sentences; and it advises the reader of the subject to be discussed and how the paragraph will discuss it. Readers generally look to the first few sentences in a paragraph to determine the subject and perspective of the paragraph. That’s why it’s often best to put the topic sentence at the very beginning of the paragraph. In some cases, however, it’s more effective to place another sentence before the topic sentence—for example, a sentence linking the current paragraph to the previous one, or one providing background information.\r\n\r\nAlthough most paragraphs should have a topic sentence, there are a few situations when a paragraph might not need a topic sentence. For example, you might be able to omit a topic sentence in a paragraph that narrates a series of events, if a paragraph continues developing an idea that you introduced (with a topic sentence) in the previous paragraph, or if all the sentences and details in a paragraph clearly refer—perhaps indirectly—to a main point. The vast majority of your paragraphs, however, should have a topic sentence.\r\n\r\nPARAGRAPH STRUCTURE\r\nMost paragraphs in an essay have a three-part structure—introduction, body, and conclusion. You can see this structure in paragraphs whether they are narrating, describing, comparing, contrasting, or analyzing information. Each part of the paragraph plays an important role in communicating your meaning to your reader.\r\n\r\nIntroduction: the first section of a paragraph; should include the topic sentence and any other sentences at the beginning of the paragraph that give background information or provide a transition.\r\n\r\nBody: follows the introduction; discusses the controlling idea, using facts, arguments, analysis, examples, and other information.\r\n\r\nConclusion: the final section; summarizes the connections between the information discussed in the body of the paragraph and the paragraph’s controlling idea.\r\n\r\nThe following paragraph illustrates this pattern of organization. In this paragraph the topic sentence and concluding sentence (CAPITALIZED) both help the reader keep the paragraph’s main point in mind.\r\n\r\nSCIENTISTS HAVE LEARNED TO SUPPLEMENT THE SENSE OF SIGHT IN NUMEROUS WAYS. In front of the tiny pupil of the eye they put, on Mount Palomar, a great monocle 200 inches in diameter, and with it see 2000 times farther into the depths of space. Or they look through a small pair of lenses arranged as a microscope into a drop of water or blood, and magnify by as much as 2000 diameters the living creatures there, many of which are among man’s most dangerous enemies. Or, if we want to see distant happenings on earth, they use some of the previously wasted electromagnetic waves to carry television images which they re-create as light by whipping tiny crystals on a screen with electrons in a vacuum. Or they can bring happenings of long ago and far away as colored motion pictures, by arranging silver atoms and color-absorbing molecules to force light waves into the patterns of original reality. Or if we want to see into the center of a steel casting or the chest of an injured child, they send the information on a beam of penetrating short-wave X rays, and then convert it back into images we can see on a screen or photograph. THUS ALMOST EVERY TYPE OF ELECTROMAGNETIC RADIATION YET DISCOVERED HAS BEEN USED TO EXTEND OUR SENSE OF SIGHT IN SOME WAY.\r\n\r\nGeorge Harrison, “Faith and the Scientist”\r\n\r\nCOHERENCE\r\nIn a coherent paragraph, each sentence relates clearly to the topic sentence or controlling idea, but there is more to coherence than this. If a paragraph is coherent, each sentence flows smoothly into the next without obvious shifts or jumps. A coherent paragraph also highlights the ties between old information and new information to make the structure of ideas or arguments clear to the reader.\r\n\r\nAlong with the smooth flow of sentences, a paragraph’s coherence may also be related to its length. If you have written a very long paragraph, one that fills a double-spaced typed page, for example, you should check it carefully to see if it should start a new paragraph where the original paragraph wanders from its controlling idea. On the other hand, if a paragraph is very short (only one or two sentences, perhaps), you may need to develop its controlling idea more thoroughly, or combine it with another paragraph.\r\n\r\nA number of other techniques that you can use to establish coherence in paragraphs are described below.\r\n\r\nRepeat key words or phrases. Particularly in paragraphs in which you define or identify an important idea or theory, be consistent in how you refer to it. This consistency and repetition will bind the paragraph together and help your reader understand your definition or description.\r\n\r\nCreate parallel structures. Parallel structures are created by constructing two or more phrases or sentences that have the same grammatical structure and use the same parts of speech. By creating parallel structures you make your sentences clearer and easier to read. In addition, repeating a pattern in a series of consecutive sentences helps your reader see the connections between ideas. In the paragraph above about scientists and the sense of sight, several sentences in the body of the paragraph have been constructed in a parallel way. The parallel structures (which have been emphasized) help the reader see that the paragraph is organized as a set of examples of a general statement.\r\n\r\nBe consistent in point of view, verb tense, and number. Consistency in point of view, verb tense, and number is a subtle but important aspect of coherence. If you shift from the more personal \"you\" to the impersonal “one,” from past to present tense, or from “a man” to “they,” for example, you make your paragraph less coherent. Such inconsistencies can also confuse your reader and make your argument more difficult to follow.\r\n\r\nUse transition words or phrases between sentences and between paragraphs. Transitional expressions emphasize the relationships between ideas, so they help readers follow your train of thought or see connections that they might otherwise miss or misunderstand. The following paragraph shows how carefully chosen transitions (CAPITALIZED) lead the reader smoothly from the introduction to the conclusion of the paragraph.', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `sub_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_code` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`sub_id`, `subject_name`, `teacher_id`, `subject_code`) VALUES
(6, 'Math', 12, 'PL1WN5'),
(7, 'Science', 12, 'G4NCTK'),
(9, 'Filipino', 12, 'S2NYP3'),
(14, 'Math', 19, 'YIB6AD'),
(15, 'Science', 19, 'C4FNQ6'),
(16, 'Physics', 19, 'QNUZFR'),
(17, 'Class', 19, 'J3SVNZ');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `Username` varchar(100) NOT NULL,
  `Password` varchar(100) NOT NULL,
  `Fname` varchar(100) NOT NULL,
  `Mname` varchar(100) NOT NULL,
  `Lname` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `User_role` enum('Student','Teacher','Admin','') NOT NULL,
  `Status` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `Username`, `Password`, `Fname`, `Mname`, `Lname`, `Email`, `User_role`, `Status`) VALUES
(11, 'Ricky', '$2y$10$xTqljypuMMXRULByZ0UuDuRF7/z9L8.uQ6ZkLTUlGbn7gCQkh9jha', 'Ricky', 'Borja', 'Mercado', 'rickymerdo@gmail.com', 'Student', 0),
(12, 'Syna', '$2y$10$yOSKNv/nozycAkw3EQd.QOBdfErve4CKA8xxTcc/6TJbg2K2TMPs2', 'Syna', 'Meme', 'Siocon', 'rickymerdo@gmail.com', 'Teacher', 1),
(13, 'Jalmer', '$2y$10$BQ7/MR.I5suz9Zx3kv6Sx.vLJN9M9Wuwunhtjzgli4PsP1sFogv4a', 'Jalmer', 'Borja', 'Mercado', 'jalmer@ggg', 'Teacher', 0),
(14, 'Reiki', '$2y$10$r5d4jCkkClUESlvHhJmGGu7eWRyLvLf33zldLd4Po0eTs67w5m.ai', 'Re', 're', 're', 're@re', 'Student', 1),
(15, '123', '$2y$10$FIasRa2WD9JWVV4KfvpZ2eSh6yFyU8yN.6dbBFtmOZA7Rj1P7rlg2', '123', '123', '123', '123@123', 'Student', 0),
(16, '33', '$2y$10$0d/e7eqbQsJWP8dTuj68r.fvZgcCYolj7aBfFOUq.4vyumtAdCe02', '333', '333', '333', '33z2@333', 'Student', 0),
(17, '2', '$2y$10$NGfzdJS41iJ7c/zlPyImbuzxUEvMT1mb74T6iInaLmUhfnxWwqLN6', '2', '2', '2', '22@2', 'Student', 0),
(18, '21', '$2y$10$AqPZtg3iWsEj.ZGqkBsc/u0wDzyE/c9f4sOXvOti3aq6i2G3FXhwy', '21', '21', '21', '21@21', 'Student', 1),
(19, 'Vann', '$2y$10$XU3IOGA9ZuBa5PQcnDE2JOqPHeVQZ3laL3Lx961zeh2QRqQuRWcNy', 'Syna', 'Sy', 'Barera', 'syna@123', 'Teacher', 1),
(21, '208506100163', '$2y$10$82TYCu6DlCwD21HlDdC4B.6flLU5Vt9iCR2a1TDq4B6bqJwz/myqy', 'Ricky', 'Borja', 'Mercado', 'rick@123', 'Student', 1),
(22, '1', '$2y$10$yQhOKtUYSWF9XdE4WMQA.eCMcspatdqCthewwbT.wbU9SuYBpFkjq', '1', '1', '1', 'rick@123', 'Student', 1),
(23, '11aa', '$2y$10$w4rXDXO/l.QbPUIWqut5oeLJehIuW87qpDNPR3wyO9S5YYAx8exGq', '11', '11', '11', '11@22', 'Teacher', 0),
(24, '208507100163', '$2y$10$oQ7EEBSb8SRcetiYsQmXs.nnFkKPNM.B5iaInOw.bh6WzV3YZyY/W', 'Jay', 'Jame', 'Jim', 'jim@gmail.com', 'Student', 1),
(25, '', '$2y$10$2cLzToMa3BMGhRLBm8UhZeUgD1gpA//MtFFYMzNmm1sNV3ykbqCVC', '123', '123', '333', 'rickymercado701@gmail.com', 'Student', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cheating_logs`
--
ALTER TABLE `cheating_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `cheating_logs_ibfk_1` (`exam_id`);

--
-- Indexes for table `choices`
--
ALTER TABLE `choices`
  ADD PRIMARY KEY (`choice_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `sub_id` (`sub_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `subject_id` (`sub_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `exam_part_timers`
--
ALTER TABLE `exam_part_timers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exam_id` (`exam_id`,`question_part`);

--
-- Indexes for table `exam_submissions`
--
ALTER TABLE `exam_submissions`
  ADD PRIMARY KEY (`examsub_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD PRIMARY KEY (`students_answer_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`sub_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cheating_logs`
--
ALTER TABLE `cheating_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=383;

--
-- AUTO_INCREMENT for table `choices`
--
ALTER TABLE `choices`
  MODIFY `choice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=392;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `exam_part_timers`
--
ALTER TABLE `exam_part_timers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `exam_submissions`
--
ALTER TABLE `exam_submissions`
  MODIFY `examsub_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT for table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `students_answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=594;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `sub_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cheating_logs`
--
ALTER TABLE `cheating_logs`
  ADD CONSTRAINT `cheating_logs_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cheating_logs_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `choices`
--
ALTER TABLE `choices`
  ADD CONSTRAINT `choices_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`sub_id`) REFERENCES `subjects` (`sub_id`);

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`sub_id`) REFERENCES `subjects` (`sub_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_part_timers`
--
ALTER TABLE `exam_part_timers`
  ADD CONSTRAINT `exam_part_timers_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_submissions`
--
ALTER TABLE `exam_submissions`
  ADD CONSTRAINT `exam_submissions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_submissions_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD CONSTRAINT `student_answers_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_answers_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_answers_ibfk_3` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
