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
 * @package    mod
 * @subpackage libraryreserves
 * @copyright  University of Minnesota
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_libraryreserves_activity_task
 */

/**
 * Define the complete libraryreserves structure for backup, with file and id annotations
 */
class backup_libraryreserves_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $libraryreserves = new backup_nested_element('libraryreserves', array('id'), array(
                'name', 'timemodified'));

        // Build the tree
        // (love this)

        // Define sources
        $libraryreserves->set_source_table('libraryreserves', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations
        // (none)

        // Return the root element (libraryreserves), wrapped into standard activity structure
        return $this->prepare_activity_structure($libraryreserves);
    }
}
