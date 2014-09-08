<?php

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('manage_moodle_instances');

// return back to self
$returnurl = "$CFG->wwwroot/local/instances/list.php";

$instances = $DB->get_records('moodle_instances', null, 'wwwroot');

$table = new html_table();
$table->head = array(get_string('wwwroot'        , 'local_instances'),
                     get_string('instancename'   , 'local_instances'),
                     get_string('isupgradeserver', 'local_instances'),
                     get_string('action'         , 'local_instances'));

foreach ($instances as $instance) {
    $row = array();
    $row[] = $instance->wwwroot;
    $row[] = $instance->name;
    $row[] = $instance->isupgradeserver ? 'yes' : 'no';

    $type = 'edit';
    $url = new moodle_url('edit.php', array('id' => $instance->id));
    $buttons  = $OUTPUT->action_icon($url, new pix_icon('t/'.$type, get_string($type)));

    if ( $CFG->wwwroot != $instance->wwwroot ) {
        $type = 'delete';
        $buttons .= ' ';
        $buttons .= $OUTPUT->action_icon(new moodle_url('delete.php', 
                                                        array('id' => $instance->id)),
                                         new pix_icon('t/'.$type, get_string($type)));
    }
    $row[] = $buttons;
    $table->data[] = $row;
}


echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('moodleinstances', 'local_instances'));

echo html_writer::table($table);

echo $OUTPUT->single_button($CFG->wwwroot.'/local/instances/edit.php',
                            get_string('addinstance', 'local_instances'),
                            'get');

echo $OUTPUT->footer();

