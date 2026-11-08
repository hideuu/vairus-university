-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 16, 2020 at 02:39 AM
-- Server version: 5.1.41
-- PHP Version: 5.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `online_enroll`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses_tbl`
--

CREATE TABLE IF NOT EXISTS `courses_tbl` (
  `course_ID` int(11) NOT NULL AUTO_INCREMENT,
  `course_title` varchar(100) NOT NULL,
  `course_desc` longtext NOT NULL,
  PRIMARY KEY (`course_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `courses_tbl`
--


-- --------------------------------------------------------

--
-- Table structure for table `sched_tbl`
--

CREATE TABLE IF NOT EXISTS `sched_tbl` (
  `sched_ID` int(11) NOT NULL AUTO_INCREMENT,
  `section_ID` int(11) NOT NULL,
  `subject_ID` int(11) NOT NULL,
  `Instructor_ID` int(11) NOT NULL,
  `time_from` varchar(100) NOT NULL,
  `time_to` varchar(100) NOT NULL,
  `room_ID` int(11) NOT NULL,
  PRIMARY KEY (`sched_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `sched_tbl`
--


-- --------------------------------------------------------

--
-- Table structure for table `sections_tbl`
--

CREATE TABLE IF NOT EXISTS `sections_tbl` (
  `section_ID` int(11) NOT NULL AUTO_INCREMENT,
  `section_name` varchar(100) NOT NULL,
  `section_course` varchar(100) NOT NULL,
  `yr_level` varchar(100) NOT NULL,
  `max_num` int(11) NOT NULL,
  `min_num` int(11) NOT NULL,
  PRIMARY KEY (`section_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `sections_tbl`
--


-- --------------------------------------------------------

--
-- Table structure for table `studenttbl`
--

CREATE TABLE IF NOT EXISTS `studenttbl` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `accountID` varchar(11) NOT NULL,
  `studnum` varchar(11) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `minitial` varchar(100) NOT NULL,
  `lname` varchar(100) NOT NULL,
  `address` varchar(500) NOT NULL,
  `contact` varchar(14) NOT NULL,
  `emailaddress` varchar(14) NOT NULL,
  `birthdate` varchar(100) NOT NULL,
  `course` varchar(100) NOT NULL,
  `yearlvl` varchar(100) NOT NULL,
  `gender` varchar(11) NOT NULL,
  `fathername` varchar(100) NOT NULL,
  `mothername` varchar(100) NOT NULL,
  `guardianname` varchar(100) NOT NULL,
  `birthplace` varchar(100) NOT NULL,
  `civilstatus` varchar(100) NOT NULL,
  `elemschool` varchar(100) NOT NULL,
  `dategradelem` varchar(100) NOT NULL,
  `hsschool` varchar(100) NOT NULL,
  `datehsgrad` varchar(100) NOT NULL,
  `lastgrad` varchar(100) NOT NULL,
  `datelastgrad` varchar(100) NOT NULL,
  `addresslastgrad` varchar(100) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `studenttbl`
--

INSERT INTO `studenttbl` (`ID`, `accountID`, `studnum`, `fname`, `minitial`, `lname`, `address`, `contact`, `emailaddress`, `birthdate`, `course`, `yearlvl`, `gender`, `fathername`, `mothername`, `guardianname`, `birthplace`, `civilstatus`, `elemschool`, `dategradelem`, `hsschool`, `datehsgrad`, `lastgrad`, `datelastgrad`, `addresslastgrad`) VALUES
(2, '6', '2014-052', 'Karl Macniel', 'E', 'Flores', 'asdadasdasd', '09312312313', 'karloemilflore', '2015-01-14', 'bsit', 'II-2', 'male', 'Lopez Alcadia', 'Mauricia Magalpok', 'Arutro Manansala', 'Manila', 'single', 'Lacs', '1990', 'Mals', '1990', 'Navs', '1990', 'Navs');

-- --------------------------------------------------------

--
-- Table structure for table `student_subject`
--

CREATE TABLE IF NOT EXISTS `student_subject` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` varchar(100) NOT NULL,
  `subjects` longtext NOT NULL,
  `totalunits` int(11) NOT NULL,
  `approved` varchar(100) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `student_subject`
--

INSERT INTO `student_subject` (`ID`, `studentID`, `subjects`, `totalunits`, `approved`) VALUES
(1, '', 'IT201,', 2, '0'),
(2, '2014-052', 'IT201,IT201,', 2, '0'),
(3, '2014-052', 'IT202,IT201,', 2, '0'),
(4, '2014-052', 'IT202,IT201,', 2, '0'),
(5, '2014-052', 'IT202,IT201,', 2, '0'),
(6, '2014-052', 'IT202,IT201,', 0, '0'),
(7, '2014-052', 'IT202,IT201,', 2, '0'),
(8, '2014-052', ',IT201,IT202', 2, '0');

-- --------------------------------------------------------

--
-- Table structure for table `subjecttbl`
--

CREATE TABLE IF NOT EXISTS `subjecttbl` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `subjectcode` varchar(100) NOT NULL,
  `subjecttitle` varchar(100) NOT NULL,
  `numofunits` varchar(100) NOT NULL,
  `room` varchar(100) NOT NULL,
  `time` varchar(100) NOT NULL,
  `instructor` varchar(100) NOT NULL,
  `day` varchar(100) NOT NULL,
  `islab` int(11) NOT NULL,
  `istutorial` int(11) NOT NULL,
  `description` varchar(100) NOT NULL,
  `course_con` varchar(100) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `subjecttbl`
--

INSERT INTO `subjecttbl` (`ID`, `subjectcode`, `subjecttitle`, `numofunits`, `room`, `time`, `instructor`, `day`, `islab`, `istutorial`, `description`, `course_con`) VALUES
(1, 'IT201', 'File Organization', '2', '', '', '', '', 0, 0, 'asdasdasd', 'bsit'),
(3, 'IT202', 'File Manage', '2', '', '', '', '', 0, 0, 'asdsadsad', 'bsit'),
(4, 'CS 103', 'Farenheit', '3', '', '', '', '', 1, 0, 'sadadsdsad', 'bsit');

-- --------------------------------------------------------

--
-- Table structure for table `usertbl`
--

CREATE TABLE IF NOT EXISTS `usertbl` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(11) NOT NULL,
  `password` varchar(11) NOT NULL,
  `fullname` varchar(20) NOT NULL,
  `user_type` varchar(11) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

--
-- Dumping data for table `usertbl`
--

INSERT INTO `usertbl` (`ID`, `username`, `password`, `fullname`, `user_type`) VALUES
(2, 'admins', 'admins', 'Dennis O. Andes', 'admin'),
(6, 'dennis', '123456', 'Dennis D. Tech', 'student');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
