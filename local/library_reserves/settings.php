<?php

//====== CONFIG FOR RESERVE SYSTEM ======
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');  /// It must be included from a Moodle page
}

if ($hassiteconfig) {
    // add a setting page
    $settings = new admin_settingpage('local_library_reserves', get_string('pluginname', 'local_library_reserves'));
    $ADMIN->add('localplugins', $settings);

    //link to the API
    //STRY0010333 20140627 mart0969 - Update for new API that includes notes
    $settings->add(new admin_setting_configtext(
            'local_library_reserves/api_url',
            get_string('api_url', 'local_library_reserves'),
            get_string('api_token_desc', 'local_library_reserves'),
            ''
    ));


    // token to access the API
    $settings->add(new admin_setting_configtext(
            'local_library_reserves/api_token',
            get_string('api_token', 'local_library_reserves'),
            get_string('api_token_desc', 'local_library_reserves'),
            ''
    ));
}
