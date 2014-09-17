<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once('ppsftsearch_lib.php');

$PAGE->set_url('/local/course/ppsftsearch.php');

// Check permissions.
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

$myterm = null;
$subjectparams = array();
$classnumberparams = array();

// If we get to this page by clicking "Previous" on the ppsftsearchresult
// page, the previous settings should be available in the query string.

$type = optional_param('type', null, PARAM_ALPHA);
if ('subj' == $type) {
    $subjectparams = ppsft_search::get_query_string_param_array();
} else if ('clsnbr' == $type) {
    $classnumberparams = ppsft_search::get_query_string_param_array();
} else if ('my' == $type) {
    # TODO: Consider handling this term in javascript for consistency.
    $myterm = $_GET['s'][0]['term'];
}

$PAGE->requires->yui_module('moodle-local_course-ppsftsearch',
                            'M.ppsft_search.init_ppsftSearch',
                            array(array('subjectparams'=>$subjectparams,
                                        'classnumberparams'=>$classnumberparams)));
$PAGE->requires->string_for_js('ppsftsearchformempty', 'local_course');
$PAGE->requires->string_for_js('ppsftsearchformmissingparams', 'local_course');

unset($SESSION->courserequest_selectedppsft);

$strtitle = get_string('ppsftsearch', 'local_course');
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);
$PAGE->set_docs_path('');

$previous = new moodle_url('/local/course/requestgateway.php');

$PAGE->navbar->add(get_string('requestgatewayheading', 'local_course'), $previous);
$PAGE->navbar->add($strtitle);
echo $OUTPUT->header();

$instructions = get_string('ppsftsearchinstructions', 'local_course');
echo html_writer::tag('p', $instructions, array('id'=>'ppsftsearchinstructions'));

/****** Search for current user ppsft classes as instructor ******/

echo '<a name="my"></a>';

echo $OUTPUT->box_start('block searchbox myclasses');
echo $OUTPUT->heading(get_string('searchformyclasses', 'local_course'),
                      2,  // level
                      'searchtypeheading');
echo html_writer::tag('p', get_string('myclasssearchdescription', 'local_course'), array('class'=>'myclasssearchdescription'));
echo ppsft_search::get_myclass_search_form($USER->id, $myterm);
echo $OUTPUT->box_end();

/****** Search by catalog ******/

echo '<a name="subj"></a>';

echo $OUTPUT->box_start('block searchbox');
echo $OUTPUT->heading(get_string('searchbysubject', 'local_course'),
                      2,  // level
                      'searchtypeheading');
echo html_writer::tag('p', get_string('catalogsearchdescription', 'local_course'), array('class'=>'catalogsearchdescription'));
echo ppsft_search::get_by_subject_form();
echo $OUTPUT->box_end();

/****** Search by class number ******/

echo '<a name="clsnbr"></a>';

echo $OUTPUT->box_start('block searchbox');
echo $OUTPUT->heading(get_string('searchbyclassnumber', 'local_course'),
                      2,  // level
                      'searchtypeheading');
echo html_writer::tag('p', get_string('classnumbersearchdescription', 'local_course'), array('class'=>'classnumbersearchdescription'));
echo ppsft_search::get_by_number_form();
echo $OUTPUT->box_end();

$previousbutton = html_writer::tag('button',
                                   get_string('previous'),
                                   array('onclick' => "window.location.href='$previous'; return false;",
                                         'name' => 'previous'));

echo html_writer::tag('div', $previousbutton, array('class'=>'previousbuttondiv'));

echo $OUTPUT->footer();

