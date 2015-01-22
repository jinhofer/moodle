<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com     //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Unit tests for mod_quiz/externallib
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package local
 * @subpackage mod_quiz
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}


global $CFG;
require_once($CFG->dirroot . '/local/mod_quiz/externallib.php');


/**
 *
 */
class local_mod_quiz_test extends advanced_testcase {


    /**
     * Data:
     *    - two quizzes Q1 and Q2, each has max score of 100
     *    - three students: S1, S2, S3
     *    - S1 has two finished attempts on Q1: score 67 and 98
     *    - S2 has three attempts on Q1: unfinished, score 34 and 45
     *    - S3 has no attempt
     *    - Q2 has no attempt
     *
     * test cases:
     *    1. Get all attempts on Q1 (5 attempts)
     *    2. Get all attempts on Q2 (0 attempts)
     *    3. Get attempts of S1 on Q1 (2 attempts)
     *    4. Get attempts of S1 and S2 on Q1 (5 attempts)
     *    5. Get attempts of S3 on Q1 (0 attempts)
     *    6. Get attempts of S1 on Q2 (0 attempts)
     *    7. Get all attempts on Q1, id_type = username
     *    8. Get all attempts on Q1, id_type = idnumber
     *
     *    20. Delete all attempts of S1 on Q1
     *    21. Delete all attempts of S2 on Q1
     *    22. Delete all attempts of S3 on Q1
     *    23. Delete all attempts of S1 on Q2
     *
     *    50. Invalid quiz ID on get attempts
     *    51. Invalid student ID on get attempts
     *    52. invalid id_type on get attempts
     *
     *    60. Invalid quiz ID on delete attempts
     *    61. Invalid student ID on delete attempts
     *    62. Invalid id_type on delete attempts
     */
    function test_local_mod_quiz_ws() {
        global $DB, $CFG;

        $this->resetAfterTest();

        //===== SET UP SCENARIO =====
        $gen = $this->getDataGenerator();

        $course = $gen->create_course(array('fullname' => 'Course A', 'shortname' => 'course_a'));
    }


}
