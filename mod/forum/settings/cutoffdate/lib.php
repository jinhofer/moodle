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
 * @package    mod-forum
 * @subpackage cutoffdate
 * @copyright  2014 Jon Marthaler at the University of Minnesota  {@link http://umn.edu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//STRY0010467 mart0969 20140807 - Add subplugin library for forum cutoff date

/**
 * Adds cutoff date control to form for editing forum settings
 * @param moodle_form $mform The form to add the element to.
 */
function forumsettings_cutoffdate_add_element($mform) {
    $mform->addElement('date_time_selector', 'cutoffdate',
                get_string('cutoffdate', 'forumsettings_cutoffdate'),
                array('optional' => true, 'defaulttime' => time()));
    $mform->addHelpButton('cutoffdate', 'cutoffdate', 'forumsettings_cutoffdate');
}

/**
 * Displays notification message if user cannot post
 * @param moodle_context $modcontext The context for the module
 * @forum stdClass $forum The forum data
 */
function forumsettings_cutoffdate_display_notification($modcontext, $forum) {
    global $OUTPUT;
    if (!has_capability('mod/forumsettings_cutoffdate:postaftercutoff', $modcontext) && $forum->cutoffdateenabled == 1 &&
                $forum->cutoffdate > 0 && time() > $forum->cutoffdate) {
        echo $OUTPUT->notification(get_string('cutoffdateon', 'forumsettings_cutoffdate'). ' ' . userdate($forum->cutoffdate, get_string('cutoffdateformat', 'forumsettings_cutoffdate')));
    }
}

/**
 * Lists settings that need to be taken out of the data while updating
 * @return array Array holding list of settings to be removed
 */
function forumsettings_cutoffdate_settings_to_remove() {
    return array('cutoffdate');
}

/**
 * Gets data from forum_cutoff table
 * @param int $forum_id ID from forum table
 * @return stdClass Object with forum_cutoff data
 */
function forumsettings_cutoffdate_get_data($forum_id) {
    global $DB;
    $forumcutoff = $DB->get_record('forum_cutoff', array('forum_id' => $forum_id));
    $data = new stdClass();
    if ($forumcutoff) {
        $data->cutoffdate = $forumcutoff->cutoffdate;
        $data->cutoffdateenabled = $forumcutoff->cutoffdateenabled;
    }
    return $data;
}

/**
 * Adds or updates data in forum_cutoff table
 * @param stdClass $forum The data for the forum
 */
function forumsettings_cutoffdate_update_data($forum) {
    global $DB;

    $forumcutoff = new stdClass();
    $old = $DB->get_record('forum_cutoff', array('forum_id' => $forum->id));

    $forumcutoff->forum_id = $forum->id;
    $forumcutoff->cutoffdate = $forum->cutoffdate;
    if ($forum->cutoffdate == 0) {
        $forumcutoff->cutoffdateenabled = 0;
    } elseif ($forum->cutoffdate > 0) {
        $forumcutoff->cutoffdateenabled = 1;
    }

    if ($old) {
        $forumcutoff->id = $old->id;
        $DB->update_record('forum_cutoff', $forumcutoff);
    } else {
        $DB->insert_record('forum_cutoff', $forumcutoff);
    }
}

/**
 * Deletes forum cutoff data
 * @param int $forum_id The ID of the forum for which data should be deleted
 */
function forumsettings_cutoffdate_delete_data($forum_id) {
    global $DB;
    $DB->delete_records('forum_cutoff', array('forum_id' => $forum_id));
}

/**
 * Function to see if forum check if we are past the cutoff date
 * @param int $forum_id The ID for the forum
 * @param mod_context $modcontext The context for the forum
 * return bool True if we are past the cutoff and the user can't post, false otherwise
 */
function forumsettings_cutoffdate_check_date($forum_id, $modcontext) {
    global $DB;
    $forumcutoff = $DB->get_record('forum_cutoff', array('forum_id' => $forum_id));
    if ($forumcutoff && $forumcutoff->cutoffdateenabled == 1 && time() > $forumcutoff->cutoffdate && !has_capability('mod/forumsettings_cutoffdate:postaftercutoff', $modcontext)) {
        return true;
    } else {
        return false;
    }
}

