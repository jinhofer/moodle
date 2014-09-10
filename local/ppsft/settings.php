<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

if ($hassiteconfig) {

    $settings = new admin_settingpage('local_ppsft', get_string('pluginname', 'local_ppsft'));
    $ADMIN->add('localplugins', $settings);

    // The following two grade link settings are intended for use by grade/export/ppsftlink,
    // which enables instructors to post grades to a PeopleSoft page.

    $settings->add(new admin_setting_configtext(
            'local_ppsft/gradelinkaccessurl',
            get_string('gradelinkaccessurl', 'local_ppsft'),
            get_string('gradelinkaccessurldesc', 'local_ppsft'),
            '',
            PARAM_URL,
            60));

    $settings->add(new admin_setting_configtext(
            'local_ppsft/gradelinkgradeposturl',
            get_string('gradelinkgradeposturl', 'local_ppsft'),
            get_string('gradelinkgradeposturldesc', 'local_ppsft'),
            '',
            PARAM_URL,
            60));

    // Page to manage vanished enrollments.  Not really settings.
    $ADMIN->add('localplugins',
                new admin_externalpage('manage_vanished_ppsft_enrollments',
                get_string('managevanishedenrollments', 'local_ppsft'),
                $CFG->wwwroot.'/local/ppsft/vanished_enrollments.php'));
}

