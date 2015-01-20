<?php

/**
 * This file keeps track of upgrades to the ltisource_chooser subplugin
 *
 * @package    ltisource_chooser
 * @author     Dominic Hanzely <dhanzely@umn.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * xmldb_ltisource_chooser_upgrade is the function that upgrades
 * the ltisource_chooser plugin database when is needed
 *
 * This function is automaticly called when version number in
 * version.php changes.
 *
 * @param int $oldversion New old version number.
 *
 * @return boolean
 */
function xmldb_ltisource_chooser_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();


    // Moodle v2.2.0 release upgrade line
    // Put any upgrade step following this

    // Moodle v2.3.0 release upgrade line
    // Put any upgrade step following this


    // Moodle v2.4.0 release upgrade line
    // Put any upgrade step following this


    // Moodle v2.5.0 release upgrade line.
    // Put any upgrade step following this.


    // Moodle v2.6.0 release upgrade line.
    // Put any upgrade step following this.
    if ($oldversion < 2014061300) {

        // Define table lti_source_chooser to be created.
        $table = new xmldb_table('lti_source_chooser');

        // Adding fields to table lti_source_chooser.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('typeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table lti_source_chooser.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table lti_source_chooser.
        $table->add_index('typeid', XMLDB_INDEX_NOTUNIQUE, array('typeid'));

        // Conditionally launch create table for lti_source_chooser.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Chooser savepoint reached.
        upgrade_plugin_savepoint(true, 2014061300, 'ltisource', 'chooser');
    }

    return true;
}
