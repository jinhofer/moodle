<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file keeps track of upgrades to the forumsettings_cutoffdate subplugin
 *
 * @package    forumsettings_cutoffdate
 * @author     Jon Marthaler <mart0969@umn.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//STRY0010467 mart0969 20140807 - Add subplugin for forum cutoff date

defined('MOODLE_INTERNAL') || die();

function xmldb_forumsettings_cutoffdate_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2014071000) {

        // Define table forum_cutoff to be created.
        $table = new xmldb_table('forum_cutoff');

        // Adding fields to table forum_cutoff.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('forum_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cutoffdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('cutoffdateenabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table forum_cutoff.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for forum_cutoff.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Cutoffdate savepoint reached.
        upgrade_plugin_savepoint(true, 2014071000, 'forumsettings', 'cutoffdate');
    }

    return true;
}

