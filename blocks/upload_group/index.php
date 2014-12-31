<?php

require(realpath(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))).'/config.php');
require_once($CFG->dirroot.'/blocks/upload_group/forms.php');
require_once($CFG->dirroot.'/blocks/upload_group/lib.php');
require_once($CFG->libdir.'/csvlib.class.php');


$course_id  = required_param('id', PARAM_INT);
$action     = optional_param('action', null, PARAM_TEXT);

// get the course record
if (! ($course = $DB->get_record('course', array('id' => $course_id)))) {
    print_error('invalidcourseid', 'error');
}

require_login($course);

$context = context_course::instance($course->id);
require_capability('moodle/course:managegroups', $context);

$return_url = new moodle_url('/blocks/upload_group/index.php', array('id' => $course->id));

$PAGE->set_url($return_url);
$PAGE->set_title("Upload group data");
$PAGE->set_pagelayout('course');
$PAGE->set_heading($course->fullname);

if ($action != null) {
    // process form submission
    switch ($action) {
        case 'upload_group_data':
            $form = new block_upload_group_upload_form();

            if ($form->is_cancelled()) {
                redirect($CFG->wwwroot . '/course/view.php?id=' . $course->id);
            }

            $form_data = $form->get_data();

            $iid    = csv_import_reader::get_new_iid('upload_group');
            $reader = new csv_import_reader($iid, 'upload_group');

            $read_count = $reader->load_csv_content($form->get_file_content('group_data'),
                                                    $form_data->encoding,
                                                    $form_data->delimiter);

            if ($read_count === false) {
                print_error('csvloaderror', '', $return_url);
            } else if ($read_count == 0) {
                print_error('csvemptyfile', 'error', $return_url);
            }

            $self_lib = new block_upload_group_lib();

            // test if columns ok
            try {
                $self_lib->validate_headers($reader);
            }
            catch(Exception $e) {
                print_error('invalid_header', 'block_upload_group', $return_url, array('msg' => $e->getMessage()));
            }

            // print out sample lines and the confirm button
            $reader->init();

            echo $OUTPUT->header();
            echo '<h>Upload groups preview</h>';

            $table = new html_table();
            $table->head = $reader->get_columns();

            $table->data = array();
            while ($line = $reader->next()) {
                $table->data[] = $line;
            }

            echo get_string('confirm_upload_help', 'block_upload_group');
            echo html_writer::table($table);

            // the confirm form
            $data = array('id'    => $course->id,
                          'iid'   => $iid);
            $confirm_form = new block_upload_group_confirm_form(null, $data);

            $confirm_form->display();

            echo $OUTPUT->footer();
            break;

        case 'process_group_data':
            $iid  = required_param('iid', PARAM_INT);
            $reader = new csv_import_reader($iid, 'upload_group');

            $form = new block_upload_group_confirm_form();

            if ($form->is_cancelled()) {
                $reader->cleanup();
                redirect($CFG->wwwroot . '/course/view.php?id=' . $course->id);
            }

            $form_data = $form->get_data();

            $self_lib = new block_upload_group_lib();

            echo $OUTPUT->header();

            try {
                $result = $self_lib->process_uploaded_groups($course, $reader, $form_data->role);
            }
            catch(Exception $e) {
                print_error('e_process_group', 'block_upload_group', $return_url, array('msg' => $e->getMessage()));
            }

            // output the result
            echo $self_lib->format_result($result);

            echo $OUTPUT->footer();
            break;


            default:
                // display error
                print_error('unknown_action', 'block_upload_group', $return_url);
                break;
    }
} else {
    // display the upload form
    echo $OUTPUT->header();
    echo "<h2 id=\"upload_group_title\">Upload Groups</h2>";

    $form = new block_upload_group_upload_form(null, array('id' => $course->id));
    $form->display();

    echo $OUTPUT->footer();
}
