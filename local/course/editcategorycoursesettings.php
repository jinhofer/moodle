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

require_once('../../config.php');
require_once($CFG->dirroot.'/local/course/lib.php');
require_once($CFG->libdir.'/coursecatlib.php');

require_login();

$categoryid = optional_param('categoryid', 0, PARAM_INT);

$url = new moodle_url('/local/course/editcategorycoursesettings.php');
$returnurl = new moodle_url('/course/index.php');

if ($categoryid) {
    $coursecat = coursecat::get($categoryid, MUST_EXIST, true);
    $category = $coursecat->get_db_record();
    $context = context_coursecat::instance($categoryid);

    $url->param('categoryid', $categoryid);
    $strtitle = new lang_string('editcategorycoursesettings', 'local_course');
    $title = $strtitle;
    $fullname = $coursecat->get_formatted_name();

} else {
    redirect($returnurl);
}

require_capability('local/course:managecoursesettings', $context);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($fullname);

$category_course_settings = array_merge(get_category_course_settings($categoryid), array('categoryid' => $categoryid));

require_once 'editcategorycoursesettings_form.php';
$mform = new category_course_settings_form(null, $category_course_settings);

if ($mform->is_cancelled()) {
    if ($categoryid) {
        $returnurl->param('categoryid', $categoryid);
    }
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    foreach ($data as $name => $value) {
        // Skip the categoryid and submitbutton values
        if ($name == 'categoryid' || $name == 'submitbutton') {
            continue;
        }

        save_category_course_setting($categoryid, $name, $value);
    }

    $returnurl->param('categoryid', $categoryid);
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($strtitle);
$mform->display();
echo $OUTPUT->footer();
