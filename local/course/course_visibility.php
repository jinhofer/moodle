<?php

require_once('../../config.php');
require_once('lib.php');

$id          = required_param('id', PARAM_INT);
$courseshow  = optional_param('courseshow', -1, PARAM_BOOL);
$confirm     = optional_param('confirm', '', PARAM_ALPHANUM);

$params = array('id' => $id);

$course = $DB->get_record('course', $params, '*', MUST_EXIST);

$PAGE->set_url('/local/course/course_visibility.php', array('id' => $course->id));

$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);

// Fix course format if it is no longer installed
$course->format = course_get_format($course)->get_format();

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);

if (has_capability('moodle/course:visibility', $context)) {

    if ($courseshow < 0) {
        // This is the cancel condition.
        redirect('/course/view.php?id='.$course->id);
    }
    if ($confirm == md5($course->id)) {
        /* The user has submitted their confirmation operation. Toggle the
         * course's visibility and return the user to the course view page.
         */
        require_sesskey();
        course_change_visibility($course->id, $courseshow);
        redirect('/course/view.php?id='.$course->id);
    } else {
        /* The user has requested to hide or show the course. Display
         * the page confirming they wish to hide or show the course.
         */
        $PAGE->set_title(get_string('course') . ': ' . $course->fullname);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('coursechangevisibility', 'local_course'), 2, null, 'course_visibility_heading');
        $showhide = $courseshow ? 'show' : 'hide';
        echo $OUTPUT->confirm(
            get_string('coursecheck'.$showhide, 'local_course'),
            new single_button(
                new moodle_url($PAGE->url, array(
                    'courseshow' => $courseshow,
                    'confirm'    => md5($course->id)
                )),
                get_string($showhide),
                'post'
            ),
            $PAGE->url
        );
        echo $OUTPUT->footer();
    }
}
