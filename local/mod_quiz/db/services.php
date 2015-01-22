<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local mod_quiz external functions and service definitions.
 *
 * @package    local
 * @subpackage webservice
 * @copyright  University of Minnesota 2013
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
    'local_mod_quiz_get_attempts' => array(
        'classname'   => 'local_mod_quiz_external',
        'methodname'  => 'get_attempts',
        'classpath'   => 'local/mod_quiz/externallib.php',
        'description' => 'Get Moodle quiz attempts of specific quizzes and students',
        'type'        => 'read',
        'capabilities'=> 'moodle/grade:viewall'
    ),
    'local_mod_quiz_delete_attempts' => array(
        'classname'   => 'local_mod_quiz_external',
        'methodname'  => 'delete_attempts',
        'classpath'   => 'local/mod_quiz/externallib.php',
        'description' => 'Delete Moodle quiz attempts of specific quizzes and students',
        'type'        => 'write',
        'capabilities'=> 'mod/quiz:deleteattempts'
    )
);
