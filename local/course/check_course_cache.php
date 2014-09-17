<?php

require_once('../../config.php');

require_login();  // Intentionally not passing course.

$id = required_param('id', PARAM_INT); // course id

$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/course/check_course_cache.php', array('id'=>$id));

$PAGE->set_context(context_system::instance());

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

require_capability('moodle/course:update', $coursecontext);

// If user clicked the rebuild button...
if (array_key_exists('rebuild', $_POST)) {
    require_sesskey();
    rebuild_course_cache($id);
    redirect($PAGE->url);
}

// User did not click the rebuild button, so display course modules.
$PAGE->set_title('check_course_cache');
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading('check_course_cache');

$modinfo = unserialize($course->modinfo);
echo '<div class="coursecache">';
foreach ($modinfo as $cmid => $cm) {
    echo '<div class="coursemodule">'."$cmid: ";
    echo $cm->mod;
    echo ', ';
    echo $cm->name;

    if ($DB->record_exists('course_modules', array('id'=>$cmid))) {
        echo ' <span class="cm_ok">'.get_string('cachecmok', 'local_course').'</span><br />';
    } else {
        echo '<div class="cm_error">'.get_string('cachecmerror', 'local_course')."<br />\n";
        echo '<div class="cachedcm">';
        print_r($cm);
        echo '</div>';
        echo "</div>\n";
    }
    echo '</div>';
}
echo '</div>';

echo $OUTPUT->single_button(new moodle_url($PAGE->url, array('rebuild' => 1)), get_string('rebuild', 'local_course'));

echo $OUTPUT->footer();
