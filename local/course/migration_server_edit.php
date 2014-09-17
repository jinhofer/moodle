<?php

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/instances/lib.php');
require_once 'migration_server_edit_form.php';

$id = optional_param('id', 0, PARAM_INT);
$PAGE->set_url('/local/course/migration_server_edit.php', $id ? array('id'=>$id) : null);
admin_externalpage_setup('local_course_manage_migration_servers');

function update_migration_server($formdata) {
    global $DB;
    $DB->update_record('course_request_servers', $formdata);
}

function add_migration_server($formdata) {
    global $DB;

    $formdata->requestinginstanceid = get_this_moodle_instanceid();

    $DB->insert_record('course_request_servers', $formdata);
}

$returnurl = "$CFG->wwwroot/local/course/migration_server_list.php";

if ($id) {
    $rec = $DB->get_record('course_request_servers', array('id'=>$id));
    $form = new edit_migrationserver_form(null,
                                          array('currentrequester'=>$rec->requestinginstanceid,
                                                'currentsource'   =>$rec->sourceinstanceid));
    $form->set_data($rec);
} else {
    $form = new edit_migrationserver_form(null,
                                          array('currentrequester'=>get_this_moodle_instanceid(),
                                                'currentsource'   =>0));
}

if ($form->is_cancelled()) {

    redirect($returnurl);

} else if ($data = $form->get_data()) {

    if ($id) {
        update_migration_server($data);
    } else {
        add_migration_server($data);
    }

    redirect($returnurl);
}

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();

