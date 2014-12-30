<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    include_once($CFG->dirroot.'/blocks/forumnav/constants.php');

    $settings->add(new admin_setting_configtext('block_forumnav_num_before_after',
                                                get_string('configbeforeafter'     , 'block_forumnav'),
                                                get_string('configbeforeafter_desc', 'block_forumnav'),
                                                FORUMNAV_DEFAULT_BEFORE_AFTER_NUM,
                                                PARAM_INT));

}

