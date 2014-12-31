<?php

require(realpath(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))).'/config.php');
require_once($CFG->dirroot.'/local/myu/forms.php');

$course_id = required_param('courseid', PARAM_INT);
$saved     = optional_param('saved', 0, PARAM_INT);
$action    = optional_param('action', null, PARAM_TEXT);

$course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);


require_login($course);

$coursecontext = context_course::instance($course->id);
require_capability('moodle/course:update', $coursecontext);

$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/myu/course_prefs.php', array('courseid' => $course_id));
$PAGE->set_title(get_string('page_title', 'local_myu'));
$PAGE->set_heading($SITE->fullname);

$form = new local_myu_course_prefs_form(null, array('course_id' => $course_id));

if (!is_null($action)) {
    if ($form->is_cancelled()) {
        redirect(new moodle_url('/course/view.php', array('id' => $course_id)));
    }

    switch ($action) {
        case 'update_course_prefs':
            $form_data = $form->get_data();

            // check for existing record
            $mode = 'update';
            $course_prefs = $DB->get_record('myu_course', array('courseid' => $course_id));

            if ($course_prefs === false) {
                $mode = 'insert';
                $course_prefs = new stdClass();
                $course_prefs->courseid = $course_id;
            }

            $course_prefs->skip_portal = isset($form_data->skip_portal) ? 1 : 0;

            if ($mode == 'insert') {
                $DB->insert_record('myu_course', $course_prefs);
            }
            else {
                $DB->update_record('myu_course', $course_prefs);
            }

            // redirect to avoid resubmitting the form
            redirect(new moodle_url('/local/myu/course_prefs.php', array('courseid' => $course_id, 'saved' => 1)));
            break;
    }
}

$course_prefs = $DB->get_record('myu_course', array('courseid' => $course_id));

if ($course_prefs !== false) {
    $form->set_data($course_prefs);
}


echo $OUTPUT->header();

if ($saved == 1) {
    echo '<span style="color:red;">Settings saved</span>';
}

$form->display();
echo $OUTPUT->footer();
