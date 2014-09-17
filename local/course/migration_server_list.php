<?php

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('local_course_manage_migration_servers');

// return back to self
$returnurl = "$CFG->wwwroot/local/course/migration_server_list.php";

/* Populate server table data */

$servers = local_course_get_migration_servers();

$servertable = new html_table();
$servertable->head = array(get_string('migrationserverwwwroot', 'local_course'),
                           get_string('migrationenabled'      , 'local_course'),
                           get_string('upgradeserverwwwroot'  , 'local_course'),
                           get_string('action'                , 'local_course'));

foreach ($servers as $server) {
    $row = array();
    $row[] = $server->migrationserverwwwroot;
    $row[] = $server->enabled ? 'yes' : 'no';
    $row[] = $server->upgradeserverwwwroot;

    $type = 'edit';
    $url = new moodle_url('migration_server_edit.php', array('id' => $server->id));
    $buttons  = $OUTPUT->action_icon($url, new pix_icon('t/'.$type, get_string($type)));
    $type = 'delete';
    $buttons .= ' ';
    $buttons .= $OUTPUT->action_icon(new moodle_url('migration_server_delete.php',
                                                    array('id' => $server->id)),
                                     new pix_icon('t/'.$type, get_string($type)));

    $row[] = $buttons;
    $servertable->data[] = $row;
}

/* Populate client table data */

$clients = local_course_get_migration_clients();

$clienttable = new html_table();
$clienttable->head = array(get_string('migrationclientwwwroot', 'local_course'),
                           get_string('migrationenabled'      , 'local_course'),
                           get_string('action'                , 'local_course'));

foreach ($clients as $client) {
    $row = array();
    $row[] = $client->migrationclientwwwroot;
    $row[] = $client->enabled ? 'yes' : 'no';

    $type = 'edit';
    $url = new moodle_url('migration_client_edit.php', array('id' => $client->id));
    $buttons  = $OUTPUT->action_icon($url, new pix_icon('t/'.$type, get_string($type)));
    $type = 'delete';
    $buttons .= ' ';
    $buttons .= $OUTPUT->action_icon(new moodle_url('migration_client_delete.php',
                                                    array('id' => $client->id)),
                                     new pix_icon('t/'.$type, get_string($type)));

    $row[] = $buttons;
    $clienttable->data[] = $row;
}

/* Display server and client tables */

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('migrationservers', 'local_course'));

echo html_writer::table($servertable);

echo $OUTPUT->single_button($CFG->wwwroot.'/local/course/migration_server_edit.php',
                            get_string('addmigrationserver', 'local_course'),
                            'get');


echo $OUTPUT->heading(get_string('migrationclients', 'local_course'));

echo html_writer::table($clienttable);

echo $OUTPUT->single_button($CFG->wwwroot.'/local/course/migration_client_edit.php',
                            get_string('addmigrationclient', 'local_course'),
                            'get');

echo $OUTPUT->footer();

