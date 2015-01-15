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
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in Page module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function libraryreserves_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_MOD_INTRO:               return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return false;
        case FEATURE_IDNUMBER:                return false;

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function libraryreserves_get_extra_capabilities() {
    return array();
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function libraryreserves_reset_userdata($data) {
    return array();
}

/**
 * List of view style log actions
 * @return array
 */
function libraryreserves_get_view_actions() {
    return array('view','view all');
}

/**
 * List of update style log actions
 * @return array
 */
function libraryreserves_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add page instance.
 * @param stdClass $data
 * @param mod_page_mod_form $mform
 * @return int new page instance id
 */
function libraryreserves_add_instance($data, $mform = null) {
    global $CFG, $DB;

    $cmid = $data->coursemodule;
    $data->name = get_string('linktext', 'libraryreserves');
    $data->id = $DB->insert_record('libraryreserves', $data);

    return $data->id;
}

/**
 * Update page instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function libraryreserves_update_instance($data, $mform) {
    return true;
}

/**
 * Delete page instance.
 * @param int $id
 * @return bool true
 */
function libraryreserves_delete_instance($id) {
    global $DB;

    if (!$lr = $DB->get_record('libraryreserves', array('id' => $id))) {
        return false;
    }

    $DB->delete_records('libraryreserves', array('id' => $lr->id));

    return true;
}


/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info Info to customise main page display
 */
function libraryreserves_get_coursemodule_info($coursemodule) {
    return null;
}

