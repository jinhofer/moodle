<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/local/course/lib.php');

/**
 * A form for an administrator to approve a course request.
 */
class local_approve_request_form extends moodleform {
    function definition() {
        global $DB;

        $mform =& $this->_form;

        $emailtestonly = $this->_customdata['emailtestonly'];
        $silent    = $this->_customdata['silent'];
        $request   = $this->_customdata['request'];
        $requester = $DB->get_record('user', array('id' => $request->requesterid));

        $request->requester_username = $requester->username;
        $request->requester_name = fullname($requester);
        $manager = get_course_request_manager();
        $request->assignedroles = $manager->build_assigned_roles_email_string($request);
        $message = get_string('courseapprovedemail_common', 'local_course', $request);

        if ($silent) {
            $mform->addElement('html', get_string('silentapprovehdr', 'local_course'));
        } else {
            $mform->addElement('html', get_string('notsilentapprovehdr', 'local_course'));
        }

        if ($emailtestonly) {
            $mform->addElement('html', get_string('emailtestonly', 'local_course'));
        }

        $mform->addElement('hidden', 'approve', $request->id);
        $mform->setType('approve', PARAM_INT);

        $mform->addElement('hidden', 'silent', $silent);
        $mform->setType('silent', PARAM_BOOL);

        $mform->addElement('hidden', 'emailtestonly', $emailtestonly);
        $mform->setType('emailtestonly', PARAM_BOOL);

        $mform->addElement('header',
                           'coursedetails',
                           get_string('coursereasonforapproving', 'local_course'));

        $mform->addElement('textarea',
                           'approvenotice',
                           get_string('coursereasonforapprovingemail', 'local_course'),
                           array('rows'=>'15', 'cols'=>'50'));

        $mform->addRule('approvenotice', get_string('missingreqreason'), 'required', null, 'client');
        $mform->setType('approvenotice', PARAM_TEXT);
        $mform->setDefault('approvenotice', $message);

        $this->add_action_buttons(true, get_string('approve'));
    }
}



