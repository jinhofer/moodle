<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once $CFG->libdir.'/formslib.php';

class edit_moodleinstance_form extends moodleform {
    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('moodleinstance', 'local_instances'));

        // We should always have the current instance in the list of instances, so prevent changing it
        // to something else.
        $disablewwwroot = ($CFG->wwwroot == $this->_customdata['wwwroot']);
        $wwwroot_attributes = $disablewwwroot ? array('disabled'=>'disabled') : array();

        $mform->addElement('text', 'wwwroot', get_string('wwwroot', 'local_instances'), $wwwroot_attributes);
        $mform->setType('wwwroot', PARAM_URL);

        $mform->addElement('advcheckbox', 'isupgradeserver', get_string('isupgradeserver', 'local_instances'));
        ####$mform->addHelpButton('isupgradeserver', '');

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }

    function validation($data, $files) {
        $mform =& $this->_form;
        $errors = parent::validation($data, $files);

        // TODO: This duplicates some of the wwwroot logic in local/course/helpers and
        //       local/instances/edit.php.  Ideally,
        //       we should have the logic in one place--probably in a local/instances/lib.php.

        if (!preg_match('|^https://([\w\./-]+[\w])$|', $data['wwwroot'], $matches)) {
            $errors['wwwroot'] = get_string('wwwrootinvalid', 'local_instances');
        }

        return $errors;
    }
}

