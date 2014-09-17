<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

/**
 * A form for an administrator to reject a course request.
 */
class local_reject_request_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $emailtestonly = $this->_customdata['emailtestonly'];
        $silent  = $this->_customdata['silent'];
        $request = $this->_customdata['request'];

        if ($silent) {
            $mform->addElement('html', get_string('silentrejecthdr', 'local_course'));
        } else {
            $mform->addElement('html', get_string('notsilentrejecthdr', 'local_course'));
        }

        if ($emailtestonly) {
            $mform->addElement('html', get_string('emailtestonly', 'local_course'));
        }

        $mform->addElement('hidden', 'reject', $request->id);
        $mform->setType('reject', PARAM_INT);

        $mform->addElement('hidden', 'silent', $silent);
        $mform->setType('silent', PARAM_BOOL);

        $mform->addElement('hidden', 'emailtestonly', $emailtestonly);
        $mform->setType('emailtestonly', PARAM_BOOL);

        $mform->addElement('header',
                           'coursedetails',
                           get_string('coursereasonforrejecting', 'local_course'));

        $mform->addElement('textarea',
                           'rejectnotice',
                           get_string('coursereasonforrejectingemail', 'local_course'),
                           array('rows'=>'15', 'cols'=>'50'));

        $mform->addRule('rejectnotice', get_string('missingreqreason'), 'required', null, 'client');
        $mform->setType('rejectnotice', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('reject'));
    }
}
