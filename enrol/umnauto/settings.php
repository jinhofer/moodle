<?php

defined('MOODLE_INTERNAL') || die();

include_once('settingslib.php');

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('enrol_umn_settings', '', get_string('pluginname_desc', 'enrol_umnauto')));

    $settings->add(new admin_setting_configcheckbox('enrol_umnauto/defaultenrol',
        get_string('defaultenrol', 'enrol'), get_string('defaultenrol_desc', 'enrol'), 1));

    // TODO: Consider whether user should be able to override this and cause
    //       auto-enrollment to put users in a different role.
    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect('enrol_umnauto/roleid',
            get_string('defaultrole', 'role'), '', $student->id, $options));

        $settings->add(new admin_setting_umnautoterms('enrol_umnauto/terms',
            get_string('umnautoterms', 'enrol_umnauto'), '', null));

    }

}

