<?php

require_once(dirname(__FILE__) . '/../../config.php');

$PAGE->set_url('/local/course/requestgateway.php');

require_login();
if (isguestuser()) {
    print_error('guestsarenotallowed', '');
}
if (empty($CFG->enablecourserequests)) {
    print_error('courserequestdisabled', '');
}
$context = context_system::instance();
$PAGE->set_context($context);
require_capability('moodle/course:request', $context);

$strtitle = get_string('requestgatewayheading', 'local_course');
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);
$PAGE->set_docs_path('');

$PAGE->navbar->add($strtitle);
echo $OUTPUT->header();

echo $OUTPUT->box(get_string('courserequestgatewayintro', 'local_course'), 'requestformblockcontainer block');


echo $OUTPUT->box_start('requestformlinkscontainer');

echo $OUTPUT->heading($strtitle);

$links = html_writer::tag('div',
            html_writer::link(new moodle_url('/local/course/ppsftsearch.php'),
            get_string('requestformlink_acad', 'local_course')),
array('class' => 'requestformlink'));

$links .= html_writer::tag('div',
            html_writer::link(new moodle_url('/local/course/request.php', array('crtype' => 'nonacad')),
            get_string('requestformlink_nonacad', 'local_course')),
array('class' => 'requestformlink'));

// Intentionally nesting one box inside the other to allow for centering the div as inline-block.
echo $OUTPUT->box($links, 'requestformlinks');

echo $OUTPUT->box_end();

echo $OUTPUT->footer();
