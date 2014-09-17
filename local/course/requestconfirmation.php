<?php

require_once(dirname(__FILE__) . '/../../config.php');

$PAGE->set_url('/local/course/requestconfirmation.php');

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

$strtitle = get_string('courserequest', 'local_course');
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);

$PAGE->navbar->add($strtitle);
echo $OUTPUT->header();

#echo $OUTPUT->box(get_string('courserequestconfirmation', 'local_course'));

###echo $OUTPUT->heading($strtitle);

echo $OUTPUT->box_start('requestconfirmationcontainer');

$messagediv = html_writer::tag('div',
                               get_string('courserequestconfirmation', 'local_course'),
                               array('class'=>'message'));

$buttons  = html_writer::tag('button',
                             get_string('requestanothersite', 'local_course'),
                             array('onclick'=>"window.location.href='"
                                                .new moodle_url('requestgateway.php')
                                                ."'"));
$buttons .= html_writer::tag('button',
                             get_string('closewindow', 'local_course'),
                             array('onclick'=>"window.close()"));
###$buttons .= html_writer::link('requestconfirmation.php', 'test', array('target'=>'_blank'));

// Intentionally nesting one box inside the other to allow for centering the div as inline-block.
echo $OUTPUT->box($messagediv.$buttons, 'requestconfirmation generalbox');

echo $OUTPUT->box_end();  // requestconfirmationcontainer

echo $OUTPUT->footer();
