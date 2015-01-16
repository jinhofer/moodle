<?php

function xmldb_local_course_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012072400) {

        // Define table course_request_servers to be created
        $table = new xmldb_table('course_request_servers');

        // Adding fields to table course_request_servers
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('requestinginstanceid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('sourceinstanceid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('upgradeinstanceid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);

        // Adding keys to table course_request_servers
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for course_request_servers
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // course savepoint reached
        upgrade_plugin_savepoint(true, 2012072400, 'local', 'course');
    }

    if ($oldversion < 2012090100) {

        // Changing size of sections and callnumbers fields on table course_request_u to 255.
        $table = new xmldb_table('course_request_u');

        $field = new xmldb_field('sections', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'courseid');

        // Conditionally launch add field "sections"
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $dbman->change_field_precision($table, $field);

        $field = new xmldb_field('callnumbers', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'sections');

        // Conditionally launch add field "callnumbers"
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $dbman->change_field_precision($table, $field);

        upgrade_plugin_savepoint(true, 2012090100, 'local', 'course');
    }

    if ($oldversion < 2013041701) {
        // This block adds two fields to course_request_category_map: display and allowrequest

        $table = new xmldb_table('course_request_category_map');

        // Define field display to be added to course_request_category_map
        $field = new xmldb_field('display', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'categoryid');

        // Conditionally launch add field display
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field allowrequest to be added to course_request_category_map
        $field = new xmldb_field('allowrequest', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'display');

        // Conditionally launch add field allowrequest
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // course savepoint reached
        upgrade_plugin_savepoint(true, 2013041701, 'local', 'course');
    }

    if ($oldversion < 2013100800) {

        // Define field sendemail to be added to course_request_users
        $table = new xmldb_table('course_request_users');
        $field = new xmldb_field('sendemail', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'roleid');

        // Conditionally launch add field sendemail
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // course savepoint reached
        upgrade_plugin_savepoint(true, 2013100800, 'local', 'course');
    }


    if ($oldversion < 2013111800) {

        // Drop unused fields from mdl_course_request_u.
        $table = new xmldb_table('course_request_u');
        $todrop = array('instructors', 'designers', 'campus', 'term', 'coursetype');
        foreach ($todrop as $fieldname) {
            $field = new xmldb_field($fieldname);
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        // Drop unused fields from mdl_course_request_category_map.
        $table = new xmldb_table('course_request_category_map');
        $todrop = array('campus', 'term', 'coursetype');
        foreach ($todrop as $fieldname) {
            $field = new xmldb_field($fieldname);
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        // Course savepoint reached.
        upgrade_plugin_savepoint(true, 2013111800, 'local', 'course');
    }

    if ($oldversion < 2014022400) {

        // Drop summary and summaryformat fields from mdl_course_request_u.
        $table = new xmldb_table('course_request_u');
        $todrop = array('summary', 'summaryformat');
        foreach ($todrop as $fieldname) {
            $field = new xmldb_field($fieldname);
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        // Course savepoint reached.
        upgrade_plugin_savepoint(true, 2014022400, 'local', 'course');
    }

    if ($oldversion < 2014080800) {

        // Define table course_request_category_conf to be created.
        $table = new xmldb_table('course_request_category_conf');

        // Adding fields to table course_request_category_conf.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table course_request_category_conf.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for course_request_category_conf.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Course savepoint reached.
        upgrade_plugin_savepoint(true, 2014080800, 'local', 'course');
    }

    if ($oldversion < 2015011500) {

        // Define field timecreated to be added to course_request_u.
        $table = new xmldb_table('course_request_u');

        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'requestform');

        // Conditionally launch add field timecreated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timecreated');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('modifierid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field modifierid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Course savepoint reached.
        upgrade_plugin_savepoint(true, 2015011500, 'local', 'course');
    }

    return true;
}

