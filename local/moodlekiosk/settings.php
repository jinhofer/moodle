<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

if ($hassiteconfig) {
    // add a setting page
    $settings = new admin_settingpage('local_moodlekiosk', get_string('pluginname', 'local_moodlekiosk'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configpasswordunmask(
            'local_moodlekiosk/api_key',
            get_string('api_key', 'local_moodlekiosk'),
            get_string('api_key_descr', 'local_moodlekiosk'),
            ''));

    $settings->add(new admin_setting_configtext(
            'local_moodlekiosk/instance_name',
            get_string('instance_name', 'local_moodlekiosk'),
            get_string('instance_name_descr', 'local_moodlekiosk'),
            ''));

    $settings->add(new admin_setting_configtext(
            'local_moodlekiosk/listener_url',
            get_string('listener_url', 'local_moodlekiosk'),
            get_string('listener_url_descr', 'local_moodlekiosk'),
            ''));
}
