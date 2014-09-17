<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once('ppsftsearch_lib.php');

$PAGE->set_url('/local/course/ppsftsearchresult.php');

$searchtype = required_param('type', PARAM_ALPHA);
$previous = 'ppsftsearch.php?'.$_SERVER['QUERY_STRING'].'#'.$searchtype;

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

$classes = ppsft_search::get_classes_from_search_query_string();

if (empty($classes)) {
    $instructions = get_string('ppsftsearchclassesnotfound', 'local_course');
    $resultformoutput = '';
} else {
    $instructions = get_string('ppsftsearchresultinstructions', 'local_course');
    $resultformoutput = ppsft_search::get_result_form($classes, $previous);
}

$PAGE->requires->yui_module('moodle-local_course-ppsftclassselect',
                            'M.ppsft_class_select.init_ppsftClassSelect');
$PAGE->requires->string_for_js('ppsftsearchresultnotselected', 'local_course');

$SESSION->courserequest_ppsftsearch = $_SERVER['QUERY_STRING'];

$strtitle = get_string('ppsftsearchresults', 'local_course');
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);
$PAGE->set_docs_path('');

$PAGE->navbar->add(get_string('requestgatewayheading', 'local_course'),
                   new moodle_url('/local/course/requestgateway.php'));

/************* Start of navbar link to previous ***************/
// This navbar entry is an action link because the query string is
// too complex for moodle_url rendering.
# TODO: Reduce duplication between this and request.php.

$jscode = "function gotootherpage(e, args) { "
                   ." window.location.href=args.target; "
                   ." e.preventDefault(); }";
$PAGE->requires->js_init_code($jscode);
$searchformsaction = new action_link(
      new moodle_url('/local/course/requestgateway.php'),
      '',
      new component_action('click',
                           'gotootherpage',
                            array('target' => $CFG->wwwroot."/local/course/ppsftsearch.php?$SESSION->courserequest_ppsftsearch#$searchtype")));

$PAGE->navbar->add(get_string('ppsftsearch', 'local_course'),
                   $searchformsaction);


/************* End of navbar link to previous ***************/

$PAGE->navbar->add($strtitle);
echo $OUTPUT->header();

echo html_writer::tag('p', $instructions, array('id'=>'ppsftsearchresultinstructions'));

echo $resultformoutput;

echo $OUTPUT->footer();

