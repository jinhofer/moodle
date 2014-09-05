<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * simple form to submit list of x500s
 */
class local_user_bulk_create_form extends moodleform {
    function definition () {
        $bulk_limit = get_config('local/user', 'bulk_limit');    // how many usernames can be submitted at once

        if (!$bulk_limit)
            $bulk_limit = 1000;    // fall-back default value if no config found

        $mform = & $this->_form;
        $mform->addElement('html', '<h2>' . get_string('input_header', 'local_user') . '</h2>');

        $mform->addElement('html', get_string('instruction',
                                              'local_user',
                                              array('limit' => number_format($bulk_limit))));

        $mform->addElement('html', '<br><br>');

        // x500s input
        $mform->addElement('textarea', 'x500s', get_string('x500_input', 'local_user'),
                           array('rows' => '20', 'cols' => '60', 'wrap' => 'virtual'));
        $mform->setType('x500s', PARAM_TEXT);
        $mform->addRule('x500s', null, 'required', null, 'client');

        $this->add_action_buttons(false, get_string('submit_bulk_creation', 'local_user'));
    }
}
