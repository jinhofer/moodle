<?php

class block_forumnav_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        global $CFG;

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text',
                           'config_num_before_after',
                           get_string('before_after_label', 'block_forumnav'),
                           array('size' => 5));

        if (empty($CFG->block_rss_client_num_entries)) {
            $mform->setDefault('config_num_before_after', FORUMNAV_DEFAULT_BEFORE_AFTER_NUM);
        } else {
            $mform->setDefault('config_num_before_after', $CFG->block_forumnav_num_before_after);
        }
        $mform->setType('config_num_before_after', PARAM_INT);
    }

}

