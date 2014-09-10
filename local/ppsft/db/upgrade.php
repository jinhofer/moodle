<?php

function xmldb_local_ppsft_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Adding the vanished field to mdl_ppsft_class_enrol in order to keep
    // track of enrollment records that vanish from ppsft.
    if ($oldversion < 2013020800) {
        // Define field vanished to be added to ppsft_class_enrol
        $table = new xmldb_table('ppsft_class_enrol');
        $field = new xmldb_field('vanished', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'drop_date');

        // Conditionally launch add field vanished
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // ppsft savepoint reached
        upgrade_plugin_savepoint(true, 2013020800, 'local', 'ppsft');
    }

    // Adding the grading basis field to mdl_ppsft_class_enrol so that we can
    // export that with the rest of a grade export intended for uploading to PeopleSoft.
    if ($oldversion < 2014011800) {
        // Define field grading_basis to be added to ppsft_class_enrol
        $table = new xmldb_table('ppsft_class_enrol');
        $field = new xmldb_field('grading_basis', XMLDB_TYPE_CHAR, '3', null, XMLDB_NOTNULL, null, 'A-F', 'vanished');

        // Conditionally launch add field grading_basis
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // ppsft savepoint reached
        upgrade_plugin_savepoint(true, 2014011800, 'local', 'ppsft');
    }

    return true;
}

