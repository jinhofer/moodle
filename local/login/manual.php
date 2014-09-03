<?php

/**
 * Custom manual login page for UMN Moodle 2 implementation.
 * Intended for use by the "admin" user.  Not very friendly in
 * some ways, but nobody is likely to stumble across it anyway.
 */

# TODO: Need to go through this and check for completeness again login/index.php.

require_once('../../config.php');
require_once($CFG->libdir.'/formslib.php');

$PAGE->https_required();
$PAGE->set_url("$CFG->httpswwwroot/local/login/manual.php");
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$loginsite = get_string("loginsite");
$PAGE->navbar->add($loginsite);

// Redirect to https if not on https (but only if $CFG->loginhttps
// set on; see admin/settings.php?section=httpsecurity).
$PAGE->verify_https_required();

$strtitle = get_string('manualpagetitle', 'local_login');
$PAGE->set_title($strtitle);
$PAGE->set_heading(get_site()->fullname);

class local_login_manual_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
        $attributes = array();
        // Intentionally not using setType because we are checking
        // separately.
        $mform->addElement('text', 'username', get_string('username'), $attributes);
        $mform->addRule('username', null, 'required', null, 'client');
        $mform->setType('username', PARAM_RAW);
        $mform->addElement('password', 'password', get_string('password'), $attributes);
        $mform->addRule('password', null, 'required', null, 'client');
        $this->add_action_buttons(true, get_string('login'));
    }

    function validation($data, $files) {
        global $DB;

        $errors = array();
        $username = $data['username'];

        // We ensure that user actually exists in the database before we attempt
        // authentication so that we don't create new users with this form.
        // LDAP authentication appears to
        // create new user, and we don't intend to allow authentication with this
        // login form unless the user already exists.

        // We are taking the password directly from the input data.  This should be
        // safe because Moodle hashes the password before making any database calls with
        // it.  The core Moodle file login/index.php also does not restrict.

        if ($username !== clean_param($username, PARAM_USERNAME)) {
            debugging('bad username characters');
            $errors['username'] = get_string('invalidusername');
        } else if (! $DB->record_exists('user', array('username'=>$username))) {
            debugging("username $username does not exist");
            $errors['username'] = get_string('invalidlogin');
        } else if (! $user = authenticate_user_login($username, $data['password'])) {
            debugging('Authentication failed');
            $errors['username'] = get_string('invalidlogin');
        } else if (isguestuser($user)) {
            $errors['username'] = get_string('guestusernotpermitted');
        } else if (empty($user->confirmed)) {
            $errors['username'] = get_string("mustconfirm");
        }

        return $errors;
    }
}

$form = new local_login_manual_form();

if ($formdata = $form->get_data()) {

    $username = $formdata->username;

    $user = $DB->get_record('user', array('username'=>$username), '*', MUST_EXIST);

    if (!empty($user->lang)) {
        unset($SESSION->lang);
    }

    add_to_log(SITEID, 'user', 'login', "view.php?id=$USER->id&course=".SITEID,
                   $user->id, 0, $user->id);

    complete_user_login($user, true); // sets the username cookie

    redirect($CFG->wwwroot.'/');

} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strtitle);

    if (isloggedin() and !isguestuser()) {

        // The user should log out first.
        // This branch taken almost directly from similar in login/index.php.
        echo $OUTPUT->box_start();

        $logout = new single_button(new moodle_url($CFG->httpswwwroot.'/login/logout.php',
                                                   array('sesskey'=>sesskey(),'loginpage'=>1)),
                                    get_string('logout'));

        $continue = new single_button(new moodle_url($CFG->httpswwwroot.'/login/index.php',
                                                     array('cancel'=>1)),
                                      get_string('cancel'),
                                      'get');

        echo $OUTPUT->confirm(get_string('alreadyloggedin', 'error', fullname($USER)),
                              $logout,
                              $continue);

        echo $OUTPUT->box_end();

    } else {
        // This is where we actually display the login form.
        $form->display();
    }
    echo $OUTPUT->footer();
}

