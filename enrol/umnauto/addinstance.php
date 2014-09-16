<?php

/**
 * Adds new instance of enrol_auto to specified course.  Copied
 * enrol/guest/addinstance.php and replaced 'guest' with 'umnauto'.
 *
 * @package    enrol
 * @subpackage umnauto
 */

require('../../config.php');

$id = required_param('id', PARAM_INT); // course id

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('moodle/course:enrolconfig', $context);
require_sesskey();

$enrol = enrol_get_plugin('umnauto');

if ($enrol->get_newinstance_link($course->id)) {
    $enrol->add_instance($course);
}

redirect(new moodle_url('/enrol/instances.php', array('id'=>$course->id)));
