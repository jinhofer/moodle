<?php

# Much of the initial code (but not the form and form-handling) is
# based on enrol/self/edit.php

# For form handling, see mod/quiz/edit.php for one approach.
# See class action_link in lib/outputcomponents.php for "Remove?" link.

require_once('../../config.php');
require_once("locallib.php");
require_once("$CFG->dirroot/local/ppsft/lib.php");
require_once("$CFG->dirroot/local/ppsft/ppsft_data_adapter.class.php");
require_once("$CFG->dirroot/local/ppsft/ppsft_data_updater.class.php");

$enrolid = required_param('enrolid', PARAM_INT);
$instance = $DB->get_record('enrol', array('id'=>$enrolid, 'enrol'=>'umnauto'), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$instance->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

$PAGE->set_url('/enrol/umnauto/manage.php', array('enrolid' => $enrolid));


require_login($course->id);
require_capability('enrol/umnauto:manage', $context);

$return = new moodle_url('/enrol/instances.php', array('id'=>$course->id));
if (!enrol_is_enabled('umnauto')) {
    redirect($return);
}

# TODO: Look over before deleting
#if ($instanceid) {
#    $instance = $DB->get_record('enrol',
#                                array('courseid'=>$courseid,
#                                      'enrol'=>'umnauto',
#                                      'id'=>$instanceid),
#                                '*',
#                                MUST_EXIST);
#} else {
#    require_capability('moodle/course:enrolconfig', $context);
#    // no instance yet, we have to add new instance
#    // TODO: Colin. Not sure what is the point of this next call.
#    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', array('id'=>$courseid)));
#    $instance = new stdClass();
#    $instance->id       = null;
#    $instance->courseid = $courseid;
#}

# ################# CONTINUE FROM HERE ################

# TODO: Set up a capability (or capabilities) in local/ppsft/db/access.php.
#       In 1.9, we named the capability "enrol/umn_auto:fetchanystudents" for admin
#       users who need to set up autoenrollment for any class and student.  For
#       others, authorization is based on the classes for which they are listed
#       as instructors in PeopleSoft.
#require_capability(

$errors = array();

# TODO: Might be able to simplify logic by using exceptions.

if ($data = data_submitted() and confirm_sesskey()) {  # and has_capability... ???
    #error_log(var_export(get_object_vars($data), true));
    #validate_param($data->classnbr, PARAM_INT, NULL_NOT_ALLOWED, 'invalid classnbr');

    require_once("$CFG->dirroot/enrol/umnauto/manage_handler.class.php");
    $manage_handler = new enrol_umnauto_manage_handler($instance);

    if (isset($data->add_triplet)) {

        $manage_handler->add_ppsft_class_by_triplet_data($data);

            # TODO: Move this to occur only on demand for all associated classes (as in 1.9).
            # TODO: Should we change signature to require only id?
            #$ppsft_updater->update_student_enrollment($ppsftclass);

    } else if (isset($data->update_course_enrollment)) {

        $manage_handler->set_enrollment_syncer(new enrol_umnauto_syncer($course->id));
        $manage_handler->update_course_enrollment();

        # TODO: This is just a temporary solution for forcing an instructor
        #       class refresh. Need to settle on a permanent solution, if
        #       one is needed.
        $manage_handler->refresh_instructor_classes();

    } else if ($found = preg_grep('/^add_\d{4}\w{5}\d{5}$/', array_keys(get_object_vars($data)))) {

        #error_log("found: ".var_export($found,true));
        preg_match('/^add_(\d{4})(\w{5})(\d{5})$/', array_shift($found), $matches);
        $term = $matches[1];
        $institution = $matches[2];
        $class_nbr = $matches[3];

        #error_log("ppsftclassid: $ppsftclassid");
        $manage_handler->add_ppsft_class_by_triplet($term, $institution, $class_nbr);

    } else if ($found = preg_grep('/^remove_\d+$/', array_keys(get_object_vars($data)))) {
        preg_match('/^remove_(\d+)$/', array_shift($found), $matches);
        $ppsftclassid = $matches[1];
        $manage_handler->remove_ppsft_class($ppsftclassid);
    } else {
        error_log("No valid action requested in umnauto/manage.php");
        $manage_handler->no_valid_action($data);
    }

    // Save any errors from the handler to SESSION.
    $enrol_umnauto_data = array();
    if (!empty($manage_handler->errors)) {
        $enrol_umnauto_data['errors'] = $manage_handler->errors;
    }

    $SESSION->enrol_umnauto = $enrol_umnauto_data;

    redirect(new moodle_url('/enrol/umnauto/manage.php', array('enrolid' => $enrolid)));

    # TODO: Still need to associate Moodle course with ppsft class.
    # TODO: Still need to enroll students in Moodle course.
}

// Not handling a data submission, so display page.

require_once("$CFG->dirroot/enrol/umnauto/manage_view.class.php");
$ppsft_data_adapter = ppsft_get_adapter();
$manage_page = new enrol_umnauto_manage_view($instance, $course, $ppsft_data_adapter);
$manage_page->render();

