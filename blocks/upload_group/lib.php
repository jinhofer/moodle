<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version  of the License, or
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
 *
 * @package   block
 * @subpackage upload_group
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v or later
 * @copyright University of Minnesota 2012
 */

require_once($CFG->dirroot.'/enrol/locallib.php');
require_once($CFG->dirroot.'/group/lib.php');

class block_upload_group_lib {

    /**
     * validate the uploaded CSV has the correct headers
     * @param csv_import_reader $reader
     * @return bool
     * @throws Exception
     */
    public function validate_headers($reader) {
        $columns = array();

        foreach ($reader->get_columns() as $col) {
            $col = strtoupper(trim($col));
            $columns[$col] = true;
        }

        // column "GROUP" is required
        if (!isset($columns['GROUP'])) {
            throw new Exception('Column GROUP not found');
        }

        // column "USERNAME" is required
        if (!isset($columns['USERNAME'])) {
            throw new Exception('Column USERNAME not found');
        }

        return true;
    }


    /**
     * enrol and add user to groups in course
     * @param object $course
     * @param csv_import_reader $reader
     * @param int $role_id
     */
     public function process_uploaded_groups($course, $reader, $role_id) {
         global $DB, $PAGE;

         $user_col  = null;    // index of username column
         $group_col = null;    // index of group column

         // find the index of the needed columns
         $i = 0;
         foreach ($reader->get_columns() as $col) {
             $col = strtoupper(trim($col));

             switch ($col) {
                case 'USERNAME':
                    $user_col = $i;
                        break;

                case 'GROUP':
                        $group_col = $i;
                        break;
            }

            $i++;
        }

        // get the manual enrolment plugin
        $enrol_instances = enrol_get_instances($course->id, true);

        $manual_instance = null;
        foreach ($enrol_instances as $instance) {
            if ($instance->enrol == 'manual') {
                $manual_instance = $instance;
                break;
            }
        }
        $manual_enroler = enrol_get_plugin('manual');

        // get the list of enrolled users for the course
        $manager = new course_enrolment_manager($PAGE, $course);
        $users  = $manager->get_users('firstname');
        $groups = $manager->get_all_groups();

        $group_ids = array();
        foreach ($groups as $group) {
            $group_ids[$group->name] = $group->id;
        }

        // prep the returned array
        $output = array('group_created'     => array(),
                        'user_enrolled'     => array(),
                        'member_added'      => array(),
                        'error'             => array(
                               'user_not_found'    => array(),
                               'group_failed'      => array(),
                               'enrol_failed'      => array(),
                               'member_failed'     => array()));

        // loop through the records
        $reader->init();

        while ($line = $reader->next()) {
            $username  = trim($line[$user_col]);
            $groupname = trim($line[$group_col]);

            // check if the user exists
            $user = $DB->get_record('user', array('username' => $username));

            if ($user === false) {
                $output['error']['user_not_found'][] = $username;
                continue;
            }

            // enroll the user as needed
            if (!isset($users[$user->id])) {
                try {
                    $manual_enroler->enrol_user($manual_instance, $user->id, $role_id);
                    $output['user_enrolled'][] = $username;
                }
                catch(Exception $e) {
                    $output['error']['enroll_failed'][] = $username;
                }
            }

            // create the group as needed
            if (!isset($group_ids[$groupname])) {
                $data = new stdClass();
                $data->courseid = $course->id;
                $data->name     = $groupname;

                $new_group_id = groups_create_group($data);

                if ($new_group_id === false) {
                    $output['error']['group_failed'][] = $groupname;
                }
                else {
                    $group_ids[$groupname]     = $new_group_id;
                    $output['group_created'][] = $groupname;
                }
            }

            // add the user to the group
            if (groups_add_member($group_ids[$groupname], $user->id)) {
                if (!isset($output['member_added'][$groupname])) {
                    $output['member_added'][$groupname] = array();
                }

                $output['member_added'][$groupname][] = $username;
            }
            else {
                if (!isset($output['error']['member_failed'][$groupname])) {
                    $output['error']['member_failed'][$groupname] = array();
                }

                $output['error']['member_failed'][$groupname][] = $username;
            }
        }

        return $output;
    }



    /**
     * Format the result from process_uploaded_group into HTML
     *
     * @param array $result
     * @return string
     */
    public function format_result($result) {
        $str = '<h>' . get_string('result_group_created', 'block_upload_group') . ':</h>';
        $str .= '<p>' . implode(', ', $result['group_created']) . '</p><br/>';

        $str .= '<h>Users enrolled:</h>';
        $str .= '<p>' . implode(', ', $result['user_enrolled']) . '</p><br/>';

        $str .= '<h>' . get_string('result_member_added', 'block_upload_group') . ':</h>';
        foreach ($result['member_added'] as $group => $members) {
            $str .= '<h>' . $group . ': </h>';
            $str .= '<p>' . implode(', ', $members) . '</p><br/>';
        }

        $str .= '<h style="color:red;"><br/>Errors:</h>';

        if (count($result['error']['user_not_found']) > 0) {
            $str .= '<h>' . get_string('result_user_not_found', 'block_upload_group') . ': </h>';
            $str .= '<p>' . implode(', ', $result['error']['user_not_found']) . '</p><br/>';
        }

        if (count($result['error']['group_failed']) > 0) {
            $str .= '<h>' . get_string('result_group_failed', 'block_upload_group') . ': </h>';
            $str .= '<p>' . implode(', ', $result['error']['group_failed']) . '</p><br/>';
        }

        if (count($result['error']['enrol_failed']) > 0) {
            $str .= '<h>' . get_string('result_enroll_failed', 'block_upload_group') . ': </h>';
            $str .= '<p>' . implode(', ', $result['error']['enrol_failed']) . '</p><br/>';
        }

        if (count($result['error']['member_failed']) > 0) {
            $str .= '<h>' . get_string('result_member_failed', 'block_upload_group') . ': </h>';

            foreach ($result['error']['member_failed'] as $group => $members) {
                $str .= '<h>' . $group . ': </h>';
                $str .= '<p>' . implode(', ', $members) . '</p><br/>';
            }
        }

        return $str;
    }
}
