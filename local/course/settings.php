<?php

if ($hassiteconfig) {

$ADMIN->add('courses',
            new admin_externalpage('local_course_request_category_map',
                                   get_string('configcourserequestcategorymap', 'local_course'),
                                   $CFG->wwwroot.'/local/course/request_category_map.php'));

$ADMIN->add('courses',
            new admin_externalpage('local_course_manage_migration_servers',
                                   get_string('configmanagemigrationservers', 'local_course'),
                                   $CFG->wwwroot.'/local/course/migration_server_list.php'));


// Could go straight to 'courserequest', but more efficient to go through 'courses'.
$courserequestsettings = $ADMIN->locate('courses')->locate('courserequest');

if ($courserequestsettings) {

    // defaultsourcecourseid identifies the course to use as a template when creating a new course
    // from a course request and the requester specified no source course.
    $courserequestsettings->add(new admin_setting_configtext(
                                            'defaultsourcecourseid',
                                            new lang_string('configcourserequestsourcecourseid', 'local_course'),
                                            new lang_string('configcourserequestsourcecourseid2', 'local_course'),
                                            null,
                                            PARAM_INT,
                                            10));

    // courserequestemailsender is the user from whom emails related to course requests will come.
    $courserequestsettings->add(new admin_setting_configtext(
                                            'courserequestemailsender',
                                            new lang_string('configcourserequestemailsender', 'local_course'),
                                            new lang_string('configcourserequestemailsender2', 'local_course'),
                                            null,
                                            PARAM_TEXT,
                                            30));
    // courserequestadditionalroles contains the roles to which a user can add other users
    // through the course request.
    require_once($CFG->dirroot.'/local/course/lib.php');
    $courserequestsettings->add(new local_course_setting_pickcourseroles(
                                           'courserequestadditionalroles',
                                           new lang_string('courserequestadditionalroles1', 'local_course'),
                                           new lang_string('courserequestadditionalroles2', 'local_course'),
                                           array('teacher', 'editingteacher')));

}

}

