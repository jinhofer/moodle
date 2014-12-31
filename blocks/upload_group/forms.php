<?php

defined('MOODLE_INTERNAL') || exit();

require_once($CFG->libdir.'/formslib.php');

/**
 * upload a CSV file for importing
 */
class block_upload_group_upload_form extends moodleform {

    function definition () {

        $mform = & $this->_form;
        $data = $this->_customdata;

        $mform->addElement('hidden', 'action', 'upload_group_data');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'id', $data['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('html', get_string('upload_help', 'block_upload_group'));
        $mform->addElement('header', 'upload_group_data', get_string('upload_group_data', 'block_upload_group'));

        // add a file manager
        $mform->addElement('filepicker', 'group_data', '');

        // add the encoding option
        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'block_upload_group'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        // add the delimiter option
        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter', get_string('delimiter', 'block_upload_group'), $choices);

        if (array_key_exists('cfg', $choices)) {

            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {

            $mform->setDefault('delimiter_name', 'semicolon');
        } else {

            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
        $mform->addElement('select', 'preview_num', get_string('row_preview_num', 'block_upload_group'), $choices);
        $mform->setType('preview_num', PARAM_INT);

        $this->add_action_buttons(true, get_string('submit_group_data', 'block_upload_group'));
    }
}

/**
 * display list of roles to choose from
 */
class block_upload_group_confirm_form extends moodleform {

    function definition () {

        global $DB;

        $mform = & $this->_form;
        $data = $this->_customdata;

        $mform->addElement('hidden', 'action', 'process_group_data');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'id', $data['id']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'iid', $data['iid']);
        $mform->setType('iid', PARAM_INT);

        // get all the course-level roles
        $role_ids = array_flip(get_roles_for_contextlevels(CONTEXT_COURSE));
        $choices = role_fix_names($role_ids, null, ROLENAME_ALIAS, true);

        // add the role option
        $mform->addElement('select', 'role', get_string('role_desc', 'block_upload_group'), $choices);
        $mform->setDefault('role', 5);

        $this->add_action_buttons(true, get_string('process_group_data', 'block_upload_group'));
    }
}
