<?php

$string['pluginname'] = 'Custom UMN user plugin';
$string['user:view_idnumber'] =  'view UMN emplid (Moodle idnumber)';
$string['user:usebulk'] =  'use the bulk creation tool';

// STRY0010016 20130805 kerzn002
// Add individual user from directory.
// These strings are used for creating single users from the directory via UI.
// Even though these are mostly used in enrol/manual/{manage.php,yui/quickenrolment/quickenrolment.js}
// they're here for the sake of not modifying Moodle core.
$string['user:createfromdirectory'] = 'Create users by searching the directory.';
$string['ldapsearch'] = 'Query Directory ';
$string['usersearch_help'] = 'First, use this box to find users in the Moodle system.';
$string['usersearch_ldap'] = "If you can't find the user you're looking for in Moodle, use this to search the university directory by Internet ID (eg. user000) and import the user.";
$string['ldapsearch_collapse_help'] = "If you can't find the user you're looking for in Moodle, use this to search the university directory by Internet ID (eg. user000) and import the user.";
$string['ldapsearch_collapse_caption'] = "Query Directory";

// bulk_creation forms
$string['input_header'] = 'Bulk user creation';
$string['instruction'] = 'To add new users to Moodle, enter a list of up to {$a->limit} Internet IDs (x500s) separated by commas, spaces, or newlines.';
$string['x500_input'] = 'x500s';
$string['submit_bulk_creation'] = 'Add Users';
$string['result_summary'] = 'Creation summary:';
$string['result_created'] = 'The following users have been created:';
$string['result_existed'] = 'The following users already exist:';
$string['result_skipped'] = 'The following users have been skipped (limit exceeded):';
$string['result_error'] = 'The following errors have occurred:';
$string['result_status_success'] = 'created with Moodle ID';


// errors
$string['e_empty_x500_input'] = 'No X500 submitted';
