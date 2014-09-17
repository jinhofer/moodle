<?php

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');

$id = required_param('id', PARAM_INT);
$PAGE->set_url('/local/course/migration_server_delete.php', $id ? array('id'=>$id) : null);
admin_externalpage_setup('local_course_manage_migration_servers');

$returnurl = "$CFG->wwwroot/local/course/migration_server_list.php";

class delete_migrationserver_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('header',
                           'migrationserverinfoeditdelete',
                           get_string('migrationservertoremove', 'local_course'));

        $mform->addElement('static',
                           'migrationserverwwwroot',
                           get_string('migrationserverwwwroot', 'local_course'));

        $mform->addElement('static',
                           'enabled_txt',
                           get_string('enabled', 'local_course'));

        $mform->addElement('static',
                           'upgradeserverwwwroot',
                           get_string('upgradeserverwwwroot', 'local_course'));

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, 'Remove migration server');
    }
}

$form = new delete_migrationserver_form();

$sql =<<< SQL
select s.*, m1.wwwroot as migrationserverwwwroot, m2.wwwroot as upgradeserverwwwroot
from {course_request_servers} s
  join {moodle_instances} m1 on m1.id=s.sourceinstanceid
  left join {moodle_instances} m2 on m2.id=s.upgradeinstanceid
where s.id=:id
SQL;

$rec = $DB->get_record_sql($sql, array('id' => $id), MUST_EXIST);

$rec->enabled_txt = $rec->enabled ? 'yes' : 'no';
$form->set_data($rec);

if ($form->is_cancelled()) {

    redirect($returnurl);

} else if ($data = $form->get_data()) {

    $DB->delete_records('course_request_servers', array('id' => $id));

    redirect($returnurl);
}

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();

