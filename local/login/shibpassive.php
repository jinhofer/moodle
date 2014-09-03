<?php

/**
 * This page is intended to be used as a $CFG->alternateloginurl destination.
 * If the user is guest user, we send them to a non-passive Shibboleth
 * login page.  If the user is not logged in as either a guest or a real
 * user, we attempt a passive login.  The local/login/checkshibauth.php
 * handles the result of the passive auth attempt.
 */

require('../../config.php');
require_once('lib.php');

$nonpassiveloginurl = $CFG->wwwroot.'/auth/shibboleth/index.php';

// We want to know as soon as possible if this is not configured.
$shibloginhandlerurl = $CFG->shibboleth_login_handler;
if (empty($shibloginhandlerurl)) {
    throw new Exception('Missing $CFG->shibboleth_login_handler');
}

if (isguestuser()) {
    redirect($nonpassiveloginurl);
}

if (!isloggedin()) {
    // If we know that a guest can't access the URL the user is trying to reach, then
    // we can avoid an extra guest login by going directly to a non-passive Shibboleth
    // login.  It would work to always use passive login at this point, but that would
    // result in unnecessary temporary guest logins and redirects.
    if (local_login\is_wantsurl_possibly_ok_for_guest()) {
        $checkshibauth = $CFG->wwwroot.'/local/login/checkshibauth.php';
        $fullpassiveurl = $shibloginhandlerurl.'?isPassive=true&target='.$checkshibauth;
        redirect($fullpassiveurl);
    } else {
        redirect($shibloginhandlerurl.'?target='.$nonpassiveloginurl);
    }
}    

// The remainder of the code on this page is just in case the user gets
// here when already logged in as a non-guest user.  Tell them to logout first
// if they want to log in as somebody else.

$context = context_system::instance();

// Setting URL to the original Moodle login page so that function login_info
// in lib/oputputrenderers treats sets $loginapge (sic) to true for this
// page. A bit of a hack but avoids the need to modify core code. Another solution
// might be to create a custom renderer.
$PAGE->set_url("$CFG->httpswwwroot/login/index.php");

$PAGE->set_context($context);
$PAGE->set_pagelayout('login');

$site = get_site();
$loginsite = get_string("loginsite");
$PAGE->navbar->add($loginsite);

$PAGE->set_title("$site->fullname: $loginsite");
$PAGE->set_heading("$site->fullname");

echo $OUTPUT->header();
echo $OUTPUT->box_start();
$logout = new single_button(new moodle_url($CFG->httpswwwroot.'/login/logout.php', array('sesskey'=>sesskey(),'loginpage'=>1)), get_string('logout'), 'post');
$continue = new single_button(new moodle_url($CFG->httpswwwroot.'/login/index.php', array('cancel'=>1)), get_string('cancel'), 'get');
echo $OUTPUT->confirm(get_string('alreadyloggedin', 'error', fullname($USER)), $logout, $continue);
echo $OUTPUT->box_end();
echo $OUTPUT->footer();

