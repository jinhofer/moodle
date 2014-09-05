<?php

// STRY0010016 20130805 kerzn002
// This file is a UMN Moodle extension.  It's used to create users directly from the
// directory when an instructor needs to add a single student.
// This is based on Moodle core's user/selector/search.php.
// Code to search for users ldap in response to an ajax call from an ldap searcher.

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/user/selector/lib.php');
require_once($CFG->dirroot . '/local/user/lib.php');

// Since we're adding people to the system context...
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/user/add_from_ldap.php');

// We don't support a debugging mode here.
header('Content-type: application/json; charset=utf-8');

// Check access.
if (!isloggedin()) {;
    print_error('mustbeloggedin');
}
if (!confirm_sesskey()) {
    print_error('invalidsesskey');
}

// Get the search parameter.
$search = required_param('search', PARAM_RAW);
$courseid = required_param('courseid', PARAM_RAW);

// Since we're adding people to the system context...
$uc = new local_user_creator();

$moodle_id = null;
$json = array();


try {
    // Verify that the user is authorized in this context.
    if (has_capability('local/user:createfromdirectory', get_context_instance(CONTEXT_COURSE, $courseid))) {
        $moodle_id = $uc->create_from_x500($search);
    }
}
catch (Exception $e) {
    $json['status'] = 'EX';
    $json['user'] = $search;
    $moodle_id = -1;

    if ($e instanceof dml_write_exception) {
        // This is usually indicative of trying to add someone who already exist in Moodle
        // but the DB layer doesn't tell you why something went wrong.
        // We should never get here since count($rs) above should detect users who already exist.
        $json['msg'] = "Did not create user $search: probably already exists";
    }
    elseif ($e instanceof local_user_notinldap_exception) {
        // Just because we couldn't find the user in LDAP doesn't mean the user isn't already in the database.
        $json['msg'] = "Did not create user: " . $e->getMessage();
    }
    else {
        $json['msg'] = "Did not create user $search.  Something unexpected happened: " . $e->getMessage();
    }
}

if (! is_null($moodle_id) and $moodle_id != -1) {
    $json = array('status' => 'OK', 'user' => $search);
    add_to_log($courseid, 'user', 'create', $PAGE->url->get_path(), "$search ($moodle_id)");
}
else {
    // This would happen if the user does not have the user:createfromdirectory capability in this course context.
    $json = array('status' => 'EX', 'user' => $search, 'msg' => "No permission to import new users.");
}

echo json_encode(array('results' => $json));
