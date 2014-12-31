<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * setting portal prefs for a course
 */
class local_myu_course_prefs_form extends moodleform {
    function definition () {
        $mform     = & $this->_form;
        $course_id = $this->_customdata['course_id'];

        $mform->addElement('hidden', 'courseid', $course_id);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'action', 'update_course_prefs');
        $mform->setType('action', PARAM_ALPHANUMEXT);

        $mform->addElement('header', 'course_prefs', get_string('course_prefs', 'local_myu'));

        $mform->addElement('checkbox', 'skip_portal', get_string('skip_portal', 'local_myu'));
        $mform->setDefault('skip_portal', false);

        $this->add_action_buttons(true, get_string('submit_course_prefs', 'local_myu'));
    }
}

