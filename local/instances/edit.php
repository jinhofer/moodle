<?php

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once 'edit_form.php';

$id = optional_param('id', 0, PARAM_INT);
$PAGE->set_url('/local/instances/edit.php', $id ? array('id'=>$id) : null);
admin_externalpage_setup('manage_moodle_instances');

$returnurl = "$CFG->wwwroot/local/instances/list.php";

if ($id) {
    $instance_rec = $DB->get_record('moodle_instances', array('id'=>$id));
    $form = new edit_moodleinstance_form(null, (array) $instance_rec);
    $form->set_data($instance_rec);
} else {
    $form = new edit_moodleinstance_form();
}

if ($form->is_cancelled()) {

    redirect($returnurl);

} else if ($data = $form->get_data()) {

    if ($id) {
        update_moodle_instance($data);
    } else {
        add_moodle_instance($data);
    }

    redirect($returnurl);
}

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();

