<?php

// A hook in the Shibboleth plugin executes this script so that we
// can make adjustments to the attributes before a new user is created
// on login.  The hook is described in the "How to customize the way
// the Shibboleth user data is used in Moodle" section of
// auth/shibboleth/readme.txt.
// The hook is a call to $this->config->convert_data in function get_userinfo
// in class auth_plugin_shibboleth in auth/shibboleth/auth.php.  This script
// is put into effect for that hook by setting the full script path in
// the "Data modification API" field in Shibboleth authentication plugin
// configuration.
// We can avoid manually setting this in each environment by including this
// setting in config.php.
// $CFG->forced_plugin_settings
//       = array('auth/shibboleth'
//            => array('convert_data' => __DIR__.'/local/login/shib_data_manipulation.php',


if (isset($_SERVER['umnPersonType'])) {
    $umnPersonType = explode(';', $_SERVER['umnPersonType']);

    if (in_array('Guest', $umnPersonType)) {
        #error_log('Guest is in array');
        $result['email'] = $_SERVER['preferredRfc822Recipient'];
    }
}

require_once($CFG->dirroot.'/local/user/lib.php');

$default_values = local_user_creator::get_defaults();

// only overwrite user fields
foreach ($this->userfields as $field) {
    if (!isset($result[$field]) || empty($result[$field])) {
        if ( isset($default_values[$field]) ) {
            $result[$field] = $default_values[$field];
        }
    }
}
