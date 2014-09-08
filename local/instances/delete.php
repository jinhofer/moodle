<?php

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');

$id = required_param('id', PARAM_INT);
$PAGE->set_url('/local/instances/delete.php', $id ? array('id'=>$id) : null);
admin_externalpage_setup('manage_moodle_instances');

$returnurl = "$CFG->wwwroot/local/instances/list.php"; 

class delete_moodleinstance_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('header', 'moodleinstancefieldset', get_string('moodleinstancetoremove', 'local_instances'));

        $mform->addElement('static', 'wwwroot', get_string('wwwroot', 'local_instances'));
        $mform->addElement('static', 
                           'isupgradeserver_txt', 
                           get_string('isupgradeserver', 'local_instances'));

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, 'Remove instance');
    }
}

$form = new delete_moodleinstance_form();

$instance_rec = $DB->get_record('moodle_instances', array('id'=>$id));
$instance_rec->isupgradeserver_txt = $instance_rec->isupgradeserver ? 'yes' : 'no';
$form->set_data($instance_rec);

if ($form->is_cancelled()) {

    redirect($returnurl);

} else if ($data = $form->get_data()) {

    $instance = $DB->get_record('moodle_instances', array('id' => $id));

    $DB->delete_records('moodle_instances', array('id' => $id));

    events_trigger('local_instances_instance_deleted', $instance);

    redirect($returnurl);
}

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();

