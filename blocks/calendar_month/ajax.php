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
 *
 * Calls calendar functions to obtain HTML of calendar_month
 *
 * @since      Moodle 2.9
 * @package    block_calendar_month
 * @copyright  2015
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Joseph Inhofer <jinhofer@umn.edu>
 */

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/calendar/lib.php');

require_login();
$PAGE->set_url('/blocks/calendar_month/ajax.php');
$PAGE->set_context(context_system::instance());

// Unlock session during potentially long curl request.
\core\session\manager::write_close();

$courseid = optional_param('courseID', 1, PARAM_INT);
$time = optional_param('time', 0, PARAM_INT);

$html = '';

if (!empty($calm) && (!empty($caly))) {
    $time = make_timestamp($caly, $calm, 1);
} else if (empty($time)) {
    $time = time();
}

$issite = ($courseid == SITEID);

if ($issite) {
    $filtercourse = calendar_get_default_courses();
} else {
    $filtercourse = array($courseid => $PAGE->course);
}

list($courses, $group, $user) = calendar_set_filters($filtercourse);

$html .= calendar_get_mini($courses, $group, $user, false, false, 'frontpage', $courseid, $time);

echo $html;

echo $OUTPUT->header();

die();
