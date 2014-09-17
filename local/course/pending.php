<?php

/**
 * This custom UMN page is based on the core course/pending.php page
 * and attempts to follow the same approach for more easily applying
 * changes to the core version to this version.
 */

/**
 * Allow the administrator to look through a list of course requests and approve or reject them.
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/course/lib.php');
require_once($CFG->dirroot . '/local/course/approve_request_form.php');
require_once($CFG->dirroot . '/local/course/reject_request_form.php');

require_login();
require_capability('moodle/site:approvecourse', context_system::instance());

$approve = optional_param('approve', 0, PARAM_INT);
$reject  = optional_param('reject' , 0, PARAM_INT);
$import  = optional_param('import' , 0, PARAM_INT);
$download  = optional_param('download' , 0, PARAM_INT);

$pendingpageurl = $CFG->wwwroot . '/local/course/pending.php';
admin_externalpage_setup('coursespending');

$request_manager = get_course_request_manager();


/////////////////////////////////////////////////////////////
/// Process request to download the backup file.

if (!empty($download) and confirm_sesskey()) {
    $request = $DB->get_record('course_request_u', array('id'=>$download));
    send_file($request->backupfile, basename($request->backupfile));
}

////////////////////////////////////////////////////////////////////////
/// Process import request. User must have clicked "Start import"
if (!empty($import) and confirm_sesskey()) {
    if (data_submitted()){

        // Setting this causes continue on the default exception handler
        // page to go to $pendingpageurl.
        $SESSION->fromurl = $pendingpageurl;

        $request = $DB->get_record('course_request_u', array('id'=>$import));

        $request_manager->request_migration($request);

        redirect($pendingpageurl);
    }
}

//////////////////////////////////////////////////////////////////////////////////
/// Process approval of a course. Either going to or returning from approval form.
if (!empty($approve) and confirm_sesskey()) {

    $request = $DB->get_record('course_request_u', array('id'=>$approve));
    $request->category_string = local_course_build_category_string($request->categoryid);
    $silent = optional_param('silent', 0, PARAM_BOOL);
    $emailtestonly = optional_param('emailtestonly', 0, PARAM_BOOL);

    $customdata = array('silent'  => $silent,
                        'request' => $request,
                        'emailtestonly' => $emailtestonly);

    $approveform = new local_approve_request_form($pendingpageurl, $customdata);

    if ($approveform->is_cancelled()) {
        redirect($pendingpageurl);

    } else if ($data = $approveform->get_data()) {

        $message = $data->approvenotice;

        // emailtestonly condition should not create course
        if ($emailtestonly) {
            $request_manager->notify_course_approved($request->id, $message, $silent, $emailtestonly);
            redirect($pendingpageurl);
        }

        // Setting this causes continue on the default exception handler
        // page to go to $pendingpageurl.
        $SESSION->fromurl = $pendingpageurl;

        $transaction = $DB->start_delegated_transaction();
        $courseid = $request_manager->create_course($request->id);
        $transaction->allow_commit();

        if ($courseid !== false) {
            $request_manager->notify_course_approved($request->id, $message, $silent);
            redirect($CFG->wwwroot.'/course/view.php?id=' . $courseid);
        } else {
            print_error('courseapprovedfailed');
        }
    }

/// Display the form for giving a reason for rejecting the request.
    echo $OUTPUT->header($approveform->focus());
    $approveform->display();
    echo $OUTPUT->footer();
    exit;
}

////////////////////////////////////////////////////////////////////////////////////
/// Process rejection of a course. Either going to or returning from reject form.

# TODO: Check the state of the request and perform necessary cleanup.
#       Should probably be done in the the cancel method of the manager.

if (!empty($reject)) {
    // Load the request.
    $request = $DB->get_record('course_request_u', array('id'=>$reject));
    $silent = optional_param('silent', 0, PARAM_BOOL);
    $emailtestonly = optional_param('emailtestonly', 0, PARAM_BOOL);

    $customdata = array('silent'  => $silent,
                        'request' => $request,
                        'emailtestonly' => $emailtestonly);

    // Prepare the form.
    $rejectform = new local_reject_request_form($pendingpageurl, $customdata);

    if ($rejectform->is_cancelled()){
        redirect($pendingpageurl);

    } else if ($data = $rejectform->get_data()) {

        $message = $data->rejectnotice;

        // emailtestonly condition should not cancel the request
        if ($emailtestonly) {
            $request_manager->notify_course_rejected($request->id, $message, $silent, $emailtestonly);
            redirect($pendingpageurl);
        }

        /// Reject the request
        $request_manager->cancel($request);

        $request_manager->notify_course_rejected($request->id, $message, $silent);

        /// Redirect back to the course listing.
        $redirection_message = get_string($silent ? 'courserejected_silent'
                                                  : 'courserejected',
                                          'local_course');
        redirect($pendingpageurl, $redirection_message);
    }

/// Display the form for giving a reason for rejecting the request.
    echo $OUTPUT->header($rejectform->focus());
    $rejectform->display();
    echo $OUTPUT->footer();
    exit;
}

////////////////////////////////////////////////////////////////////
/// Display the list of pending course requests.

// The report layout uses a horizontal scrollbar as necessary.
# TODO: What exactly is the difference? Also, see http://docs.moodle.org/dev/Themes_2.0_overflow_problems.
$PAGE->set_pagelayout('report');

/// Print a list of all the pending requests.
echo $OUTPUT->header();

$pending = $request_manager->get_pending_requests();

if (empty($pending)) {
    echo $OUTPUT->heading(get_string('nopendingcourses'));
} else {
    echo $OUTPUT->heading(get_string('coursespending'));

/// Build a table of all the requests.
    $table = new html_table();
    $table->attributes['class'] = 'pendingcourserequests generaltable';

    # TODO: Not sure about cell alignment. Mixing it up like this looks bad, but all centered looks worse.
    #       Maybe set colclasses and align th and td separately.
    #$table->align = array('left', 'left', 'left', 'left', 'left', 'center', 'left', 'left',
    #                      'center', 'center', 'left', 'left', 'center', 'left');
    $table->head = array(get_string('shortnamependinghdr'       , 'local_course'),
                         get_string('fullnamependinghdr'        , 'local_course'),
                         get_string('requesterpendinghdr'       , 'local_course'),
                         get_string('reasonpendinghdr'          , 'local_course'),
                         get_string('enrolleespendinghdr'       , 'local_course'),
                         get_string('sectionspendinghdr'        , 'local_course'),
                         get_string('callnumberspendinghdr'     , 'local_course'),
                         get_string('sourcecourseurlpendinghdr' , 'local_course'),
                         get_string('migrateuserseditpendinghdr', 'local_course'),
                         get_string('actionpendinghdr'          , 'local_course'));

    $table->colclasses[12] = 'url';

    $rowindex = 0;
    foreach ($pending as $request) {

        $sesskey = sesskey();
        $requestid = $request->id;

        $actions = '';

        $showapprovelinks = false;

        $filesuffix = null;
        $downloadlink = null;

        // If backupfile is set, build the download link.
        if (!empty($request->backupfile) and file_exists($request->backupfile)) {
            $filesuffix = pathinfo($request->backupfile, PATHINFO_EXTENSION);
            $downloadlink = html_writer::link(new moodle_url($pendingpageurl,
                                                         array('download' => $requestid,
                                                               'sesskey'  => $sesskey)),
                                          ".$filesuffix");
        }

        switch ($request->status) {
            case CRS_REQ_STATUS_NEW:
                if (is_valid_remote_instance($request->sourcecourseurl)) {
                    $actions .= $OUTPUT->single_button(new moodle_url($pendingpageurl,
                                                                      array('import' => $requestid,
                                                                            'sesskey' => $sesskey)),
                                                       get_string('startimport', 'local_course'));

                } else {
                    $showapprovelinks = true;
                }
                break;
            case CRS_REQ_STATUS_MIGRATING:
                $actions .= get_string('pendinginprogress', 'local_course') . '<br />';
                break;
            case CRS_REQ_STATUS_ERROR:
                // Show "Error" or whatever else is in the pendingerror string. If the backupfile property
                // points to a file with an err extension, show as tooltip.  Provide link to file.

                if (isset($filesuffix) and $filesuffix == 'err') {
                    $tooltip = file_get_contents($request->backupfile);
                } else {
                    $tooltip = get_string('pendingnoerrorinfoavail', 'local_course');
                }
                $downloadtxt = isset($downloadlink) ? " ($downloadlink)" : '';
                $actions .= '<span title="'.$tooltip.'">'.get_string('pendingerror', 'local_course')."</span>$downloadtxt<br />";
                break;
            case CRS_REQ_STATUS_READY:
                $suffix = pathinfo($request->backupfile, PATHINFO_EXTENSION);
                $downloadtxt = isset($downloadlink) ? " ($downloadlink)" : '';
                $actions .= get_string('pendingready', 'local_course') . "$downloadtxt<br />";
                $showapprovelinks = true;
                break;

        }

        $showapprovelinks && $actions .=
            html_writer::link(new moodle_url($pendingpageurl,
                                             array('approve' => $requestid,
                                                   'sesskey' => $sesskey)),
                              get_string('approve', 'local_course'),
                              array('target'=>'_blank')) . '<br />' .
            html_writer::link(new moodle_url($pendingpageurl,
                                             array('approve' => $requestid,
                                                   'sesskey' => $sesskey,
                                                   'silent' => 1)),
                              get_string('approvesilently', 'local_course'),
                              array('target'=>'_blank')) . '<br />'; 

        $actions .=
            html_writer::link(new moodle_url($pendingpageurl,
                                             array('reject' => $requestid,
                                                   'sesskey' => $sesskey)),
                              get_string('reject', 'local_course'),
                              array('target'=>'_blank')) . '<br />' .
            html_writer::link(new moodle_url($pendingpageurl,
                                             array('reject' => $requestid,
                                                   'sesskey' => $sesskey,
                                                   'silent' => 1)),
                              get_string('rejectsilently', 'local_course'),
                              array('target'=>'_blank'));

        // The userdata_edit column contains two vaguely-related elements. One
        // is a checkbox indicating whether to include userdata in the course copied
        // from sourcecourseurl.  The other is a link that allows editing the
        // sourcecourseurl and the userdata flag.  The edit link displays only
        // if the request is NEW.
        $userdata_checkbox = html_writer::checkbox('userdata',
                                                   1,
                                                   $request->copyuserdata ? true : false,
                                                   '',
                                                   array('disabled' => 'disabled'));

        $userdata_edit = "<p>$userdata_checkbox</p>";

        if ($request->status == CRS_REQ_STATUS_NEW) {

            $editlink = html_writer::link(
                          new moodle_url($CFG->wwwroot.'/local/course/edit_request.php',
                                         array('id' => $requestid,
                                               'sesskey' => $sesskey)),
                          'Edit');

            $userdata_edit .= "<p>$editlink</p>";
        }

        $additionalusers = array_map(function($u) {
                                        $nameparts = explode('@', $u->username);
                                        return array_shift($nameparts);
                                     },
                                     $request_manager->get_requested_users($requestid));
        sort($additionalusers);
        $additionalusers = implode(' ', $additionalusers);

        // Check here for shortname collisions and warn about them.
        ###$course->check_shortname_collision();
        # TODO: Make equivalent call to check collisions.

        $row = array();
        $row[] = format_string($request->shortname);
        $row[] = format_string($request->fullname);
        $row[] = fullname($DB->get_record('user', array('id'=>$request->requesterid)));
        $row[] = format_string($request->reason);
        $row[] = $additionalusers;
        $row[] = $request->sections;
        $row[] = $request->callnumbers;
        $row[] = html_writer::link($request->sourcecourseurl,
                                   $request->sourcecourseurl,
                                   array('target'=>'_blank'));
        $row[] = $userdata_edit;
        $row[] = $actions;

    /// Add the row to the table.
        $table->data[] = $row;

        ++$rowindex;
    }

/// Display the table.
    echo html_writer::table($table);

/// Message about name collisions, if necessary.
    if (!empty($collision)) {
        print_string('shortnamecollisionwarning');
    }
}

/// Finish off the page.
echo $OUTPUT->single_button($CFG->wwwroot . '/course/index.php', get_string('backtocourselisting'));
echo $OUTPUT->footer();
