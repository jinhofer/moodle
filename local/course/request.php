<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/user/lib.php');
require_once($CFG->dirroot . '/local/course/lib.php');
require_once($CFG->dirroot . '/local/course/request_form.php');
require_once($CFG->dirroot . '/local/course/request_form_handler.php');
require_once($CFG->dirroot . '/local/course/ppsftsearch_lib.php');


$crtype = required_param('crtype', PARAM_ALPHA);
$PAGE->set_url('/local/course/request.php', array('crtype' => $crtype));

/// Used in a number of redirects.
$returnurl = $CFG->wwwroot . '/my/';

/// Check permissions.
require_login();
if (isguestuser()) {
    print_error('guestsarenotallowed', '', $returnurl);
}
if (empty($CFG->enablecourserequests)) {
    print_error('courserequestdisabled', '', $returnurl);
}
$context = context_system::instance();
$PAGE->set_context($context);
require_capability('moodle/course:request', $context);

/// Set up the form.

// The original calls $data = course_request::prepare(); which
// essentially boils down to this call.
# TODO: Need to investigate the need for this further.
#       Might not need it at all if we want the summary fields to be plain text.
#$data = new stdClass;
#$data = file_prepare_standard_editor($data, 'summary', array('maxfiles'=>0, 'maxbytes'=>0));

$form_action_url = $CFG->wwwroot . "/local/course/request.php?crtype=$crtype";

$crmanager = get_course_request_manager();

$customdata = array('usercreator' => new local_user_creator(),
                    'courserequestmanager' => $crmanager,
                    'previous' => $CFG->wwwroot . "/local/course/requestgateway.php" );

$strtitle = get_string('nonacademiccourserequest', 'local_course');

switch ($crtype) {
    case 'acad':
        if (isset($SESSION->courserequest_ppsftsearch)) {
            $customdata['previous'] = $CFG->wwwroot.
                "/local/course/ppsftsearchresult?$SESSION->courserequest_ppsftsearch";
        }

        $strtitle = get_string('academiccourserequest', 'local_course');

        $classes = required_param_array('classes', PARAM_ALPHA);
        $triplets = ppsft_search::get_triplet_array_map(array_keys($classes));

        // Save $triplets in case we need to go back to previous.
        $SESSION->courserequest_selectedppsft = $triplets;

        $customdata['triplets'] = $triplets;

        // If these we determine that these get called often due to users adding additional role divs,
        // we might need to cache the emplids and ppsftclasses for the session as long as triplets does
        // not change.
        $ppsftadapter = ppsft_get_adapter();
        $customdata['primaryinstructoremplids'] = $ppsftadapter->get_primary_instructors_for_classes($triplets);
        $customdata['ppsftclasses'] = $ppsftadapter->get_classes_by_triplets_or_catalog($triplets);

        $requestform = new local_course_request_form_acad($form_action_url, $customdata);
        break;
    case 'nonacad':
        $requestform = new local_course_request_form_nonacad($form_action_url, $customdata);
        break;
    default:
        throw new Exception("Invalid crtype: $crtype");
}

#$requestform->set_data($data);

$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);

/// Standard form processing if statement.
if ($requestform->is_cancelled()){
    redirect($returnurl);

} else if ($postdata = $requestform->get_data()) {

    switch ($crtype) {
        case 'nonacad':
        case 'ta':
            $formhandler = new local_course_request_form_handler($crmanager);
            break;
        case 'ppsft':
        case 'acad':
            // This assumes we have $ppsftadapter from above.
            $ppsftupdater = ppsft_get_updater($ppsftadapter);
            $formhandler = new local_course_request_form_handler_ppsft($crmanager,
                                                                       $ppsftupdater);
            break;
        default:
            throw new Exception("Invalid crtype: $crtype");
    }

    $formhandler->handle($postdata, $customdata);

    redirect(new moodle_url('/local/course/requestconfirmation.php'));
    // and redirect to returnurl.
    #####notice(get_string('courserequestsuccess', 'local_course'), $returnurl);
}

# TODO ???$requestform->focus();

$PAGE->navbar->add(get_string('requestgatewayheading', 'local_course'),
                   new moodle_url('/local/course/requestgateway.php'));

if ($crtype == 'acad') {
    if (isset($SESSION->courserequest_ppsftsearch)) {

        // Just using a moodle_url as the navbar action does not work because
        // the query string is too complex for moodle_url rendering.
        # TODO: Consider refactoring and sharing code with previous button
        #       in the request form.
        $jscode = "function gotootherpage(e, args) { "
                     ." window.location.href=args.target; "
                     ." e.preventDefault(); }";
        $PAGE->requires->js_init_code($jscode);

        # TODO: Use helper function to reduce duplication.

        $searchformsaction = new action_link(
              new moodle_url('/local/course/requestgateway.php'),
              '',
              new component_action('click',
                                   'gotootherpage',
                                   array('target' => $CFG->wwwroot."/local/course/ppsftsearch.php?$SESSION->courserequest_ppsftsearch")));

        $PAGE->navbar->add(get_string('ppsftsearch', 'local_course'),
                           $searchformsaction);

        $searchresultaction = new action_link(
              new moodle_url('/local/course/requestgateway.php'),
              '',
              new component_action('click',
                                   'gotootherpage',
                                   array('target' => $CFG->wwwroot."/local/course/ppsftsearchresult.php?$SESSION->courserequest_ppsftsearch")));

        $PAGE->navbar->add(get_string('ppsftsearchresults', 'local_course'),
                           $searchresultaction);
    }

}

$PAGE->navbar->add($strtitle);

# TODO: How can we get this to focus on first error?
#$PAGE->set_focuscontrol('id_instructors');
echo $OUTPUT->header();
###echo $OUTPUT->heading($strtitle);

$categorytree = get_course_request_category_tree(0);

$PAGE->requires->yui_module('moodle-local_course-courserequest',
                            'M.local_course.init_courseRequest',
                            array(array('categorytree' => $categorytree)));
$PAGE->requires->strings_for_js(array('depth1select', 'depth2select', 'depth3select'), 'local_course');

// Show the request form.
$requestform->display();
echo $OUTPUT->footer();
