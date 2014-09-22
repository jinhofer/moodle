<?php

/**
 * provide functionality to create groups automatically based on
 * PeopleSoft sections
 *
 */

require_once($CFG->dirroot.'/group/lib.php');


class peoplesoft_autogroup {
    /**
     * how many courses to process at once; adjust accordingly
     * to balance between memory usage and speed
     *
     * @var int
     */
    protected $course_chunk_size = 100;


    /**
     * mapping from class IDs to group names
     * @var array
     */
    protected $classid_to_groupname = array();


    /**
     * mapping from existing group names to group IDs
     * Note that different courses could have the same group name for each,
     * thus, the key for this array is <course_id>_<groupname>
     * (instead of just group name to avoid collision
     *
     * @var array
     */
    protected $groupname_to_id = array();


    /**
     * log the result for each group in one run
     * @var array
     */
    protected $result = array();


    /**
     * log the error of each course in one run
     * @var array
     */
    protected $errors = array();


    /**
     * log the short summary stats
     * @var array
     */
    protected $stats = array();

    /**
     * @var array $options
     *         'course_chunk_size'    => int, how many courses get processed at once
     */
    public function __construct($options = array()) {
        if (isset($options['course_chunk_size'])) {
            $this->course_chunk_size = $options['course_chunk_size'];
        }
    }



    /**
     * main entrance for invoking the auto-group creation
     * and assignment of group members
     *
     * @param mixed $courses, string course ID, or list of course IDs
     */
    public function run($course_ids) {
        $this->result = array();    // reset the log
        $this->errors = array();    // reset the error log

        $this->stats = array('considered_group_count'   => 0,
                             'member_added_count'       => 0,
                             'member_removed_count'     => 0);

        // cast a single course ID into a list (of one)
        if (!is_array($course_ids))
            $course_ids = array($course_ids);

        $course_count = count($course_ids);

        // process in chunks
        $course_processed_count = 0;

        while ($course_processed_count < $course_count) {
            $course_ids_chunk = array_slice(
                $course_ids,
                $course_processed_count,
                min($this->course_chunk_size, $course_count - $course_processed_count));

            $course_processed_count += count($course_ids_chunk);

            // retrieve the groups, both existing and expected
            $groups = $this->get_existing_and_expected_groups($course_ids_chunk);

            // perform comparison
            $group_actions = $this->calculate_group_actions($groups['existing'], $groups['expected']);

            // update DB
            $this->update_db($group_actions);
        }

        return array('result' => $this->result,
                     'errors' => $this->errors);
    }




    /**
     * for the provided course IDs, query the DB to get the existing groups (and their members),
     * as well as the expected groups (based on PeopleSoft data)
     *
     * @param array $course_ids
     * @return array
     *    'existing' => array(<course_id> => array(<group_name> => array(<user_ids>)))
     *    'expected' => array(<course_id> => array(<group_name> => array(<user_ids>)))
     */
    protected function get_existing_and_expected_groups($course_ids) {
        global $DB;

        //====== get the list of existing auto-generated groups and their members
        $query = "SELECT groups.id AS groups__id,
                         groups.courseid AS groups__courseid,
                         groups.name AS groups__name,
                         groups_members.userid AS groups_members__userid
                  FROM   {groups} groups
                         LEFT JOIN {groups_members} groups_members
                             ON groups.id = groups_members.groupid
                  WHERE  groups.courseid IN (".implode(',', array_fill(0, count($course_ids), '?')).")";

        $rs = $DB->get_recordset_sql($query, $course_ids);

        // map them for easy comparison later
        $existing_groups = array();
        $this->groupname_to_id = array(); // map from existing group names into group IDs

        foreach ($rs as $record) {
            $course_id  = $record->groups__courseid;
            $group_name = $record->groups__name;

            if ( !isset($existing_groups[$course_id]) ) {
                $existing_groups[$course_id] = array();
            }

            if ( !isset($existing_groups[$course_id][$group_name]) ) {
                $existing_groups[$course_id][$group_name] = array();
            }

            $this->groupname_to_id[$course_id . '_' . $group_name] = $record->groups__id;

            if ( !is_null($record->groups_members__userid)) {
                $existing_groups[$course_id][$group_name][] = $record->groups_members__userid;
            }
        }

        $rs->close();     // important, release resource on DBMS


        //===== get the list of user enrolled in these courses for verification
        $course_id_placeholder = '('.implode(',', array_fill(0, count($course_ids), '?')).')';

        $query = "SELECT DISTINCT user_enrolments.userid AS user_enrolments__userid
                  FROM   {user_enrolments} user_enrolments
                         INNER JOIN {enrol} enrol
                             ON user_enrolments.enrolid = enrol.id
                  WHERE  enrol.courseid IN {$course_id_placeholder}";

        $rs = $DB->get_recordset_sql($query, $course_ids);

        $course_enrolled_userid = array();

        foreach ($rs as $record) {
            $course_enrolled_userid[$record->user_enrolments__userid] = true;
        }

        $rs->close();    // important, release resource on DBMS


        //===== get the list of what should be in the DB in the end
        $query = "SELECT enrol.courseid AS enrol__courseid,
                         ppsft_classes.id AS ppsft_classes__id,
                         ppsft_terms.term_name AS ppsft_terms__term_name,
                         ppsft_classes.institution AS ppsft_classes__institution,
                         ppsft_classes.subject AS ppsft_classes__subject,
                         ppsft_classes.catalog_nbr AS ppsft_classes__catalog_nbr,
                         ppsft_classes.section AS ppsft_classes__section,
                         ppsft_class_enrol.userid AS ppsft_class_enrol__userid,
                         enrol_umnauto_course.autogroup_option AS enrol_umnauto_course__autogroup_option
                  FROM   {enrol} enrol
                         INNER JOIN {enrol_umnauto_classes} enrol_umnauto_classes
                             ON enrol.id = enrol_umnauto_classes.enrolid
                         INNER JOIN {ppsft_classes} ppsft_classes
                             ON ppsft_classes.id = enrol_umnauto_classes.ppsftclassid
                         LEFT JOIN {ppsft_class_enrol} ppsft_class_enrol
                             ON ppsft_classes.id = ppsft_class_enrol.ppsftclassid AND
                                ppsft_class_enrol.status = 'E'
                         LEFT JOIN {enrol_umnauto_course} enrol_umnauto_course
                             ON enrol.courseid = enrol_umnauto_course.courseid
                         LEFT JOIN {ppsft_terms} ppsft_terms
                             ON ppsft_classes.term = ppsft_terms.term
                  WHERE  enrol.courseid IN {$course_id_placeholder}";

        $rs = $DB->get_recordset_sql($query, $course_ids);

        // map them for easy comparison later
        $expected_groups = array();
        $this->classid_to_groupname = array(); // map from class IDs into group names

        foreach ($rs as $record) {
            $course_id  = $record->enrol__courseid;
            $class_id   = $record->ppsft_classes__id;

            // map the name
            if ( !isset($this->classid_to_groupname[$class_id]) ) {
                $group_name = $this->compose_group_name(array(
                    'term'             => $record->ppsft_terms__term_name,
                    'institution'      => $record->ppsft_classes__institution,
                    'subject'          => $record->ppsft_classes__subject,
                    'catalog_nbr'      => $record->ppsft_classes__catalog_nbr,
                    'section'          => $record->ppsft_classes__section,
                    'autogroup_option' => $record->enrol_umnauto_course__autogroup_option
                ));

                $this->classid_to_groupname[$class_id] = $group_name;
            }
            else {
                $group_name = $this->classid_to_groupname[$class_id];
            }

            if ( !isset($expected_groups[$course_id][$group_name]) ) {
                $expected_groups[$course_id][$group_name] = array();
            }

            if ( !is_null($record->ppsft_class_enrol__userid)) {
                $user_id = $record->ppsft_class_enrol__userid;

                // only add users enrolled in Moodle course
                if (isset($course_enrolled_userid[$user_id])) {
                    $expected_groups[$course_id][$group_name][] = $user_id;
                }
            }
        }

        $rs->close();     // important, release resource on DBMS

        return array('existing' => $existing_groups,
                     'expected' => $expected_groups);
    }




    /**
     * compare the existing groups and expected groups, using the expected group
     * as the master list to determine what DB actions need to be done for each
     * group and its members
     *
     * existing groups with no match in expected groups are ignored, no need to delete them.
     *
     * @param array $existing_groups
     * @param array $expected_groups
     * @return array
     *    <course_id> => array(
     *         <group_name> => array(
     *              'action'    => 'insert'|'update',
     *              'members'   => array(
     *                   'insert'    => array(<user_ids>),
     *                   'delete'    => array(<user_ids>)
     *    )    )    )
     *
     */
    protected function calculate_group_actions($existing_groups, $expected_groups) {
        $actions = array();

        // compare at course level, using expected groups as master list
        foreach ($expected_groups as $course_id => $groups) {
            $tmp = array();  // store the list of group actions for the course

            if ( !isset($existing_groups[$course_id]) ) {
                // this course doesn't have any group yet. Add the groups and their members

                foreach ($groups as $group_name => $members) {
                    $tmp[$group_name] = array('action'  => 'insert',
                                              'members' => array('insert' => $members));
                }
            }
            else {
                // compare the groups
                foreach ($groups as $group_name => $members) {
                    if ( !isset($existing_groups[$course_id][$group_name]) ) {
                        // group didn't exist, add new
                        $tmp[$group_name] = array('action'  => 'insert',
                                                  'members' => array('insert' => $members));
                    }
                    else {
                        // group existed, update
                        $existing_members   = $existing_groups[$course_id][$group_name];
                        $members_to_add     = array_diff($members, $existing_members);
                        $members_to_remove  = array_diff($existing_members, $members);
                        $tmp[$group_name] = array('action'  => 'update',
                                                  'members' => array('insert' => $members_to_add,
                                                                     'delete' => $members_to_remove));
                    }
                }
            }

            $actions[$course_id] = $tmp;

            // update the stats
            $this->stats['considered_group_count'] += count($tmp);
        }

        return $actions;
    }




    /**
     * perform update to DB depending on the actions for each group
     *
     * @param array $group_actions, see format returned by $this->calculate_group_actions()
     * @return void
     */
    protected function update_db($group_actions) {
        global $DB;

        $members_to_delete = array(); // cache the list of members to delete at once

        foreach ($group_actions as $course_id => $groups) {

            foreach ($groups as $group_name => $group_info) {
                // take care of the group
                switch ($group_info['action']) {
                    case 'insert':
                        $group_record = new stdClass();
                        $group_record->courseid     = $course_id;
                        $group_record->name         = $group_name;
                        $group_record->description  = "Auto-generated from PeopleSoft section as {$group_name}";

                        $group_id = groups_create_group($group_record);

                        if ($group_id === false) {
                            $this->log_error($course_id, "cannot insert group {$group_name}");
                            continue; // proceed to next group
                        }
                        break;

                    case 'update':
                        $group_id = $this->groupname_to_id[$course_id . '_' . $group_name];
                        break;
                }

                // handle the members
                foreach ($group_info['members']['insert'] as $user_id) {
                    if (!groups_add_member($group_id, $user_id)) {
                        $this->log_error($course_id, "cannot insert member {$user_id} into " .
                                                     "group {$group_id}-{$group_name}");
                        continue; // proceed to next member
                    }
                }

                // update the stats
                $this->stats['member_added_count'] += count($group_info['members']['insert']);

                if (isset($group_info['members']['delete'])) {
                    foreach ($group_info['members']['delete'] as $user_id) {
                        if (!isset($members_to_delete[$group_id])) {
                            $members_to_delete[$group_id] = array();
                        }

                        $members_to_delete[$group_id][$user_id] = true;
                    }
                }
            }
        }

        // delete the removed member records
        if (count($members_to_delete) > 0) {
            $this->stats['member_removed_count'] = 0;

            foreach ($members_to_delete as $group_id => $users) {
                foreach ($users as $user_id => $nada) {
                    if (groups_remove_member($group_id, $user_id)) {
                        $this->stats['member_removed_count']++;
                    }
                    else {
                        $this->log_error('ALL_COURSES', "cannot delete member {$user_id} in group {$group_id}");
                    }
                }
            }

             // update the stats
        }
    }



    /**
     * return the accumulated stats of the last run
     */
    public function get_stats() {
        return $this->stats;
    }



    /**
     * compose an automatic group name from a PeopleSoft class
     *
     * if autogroup_option == 1: include term in the name
     *
     * @param array $class_props
     *         'term'               => string
     *         'institution'        => string
     *         'subject'            => string
     *         'catalog_nbr'        => string
     *         'section'            => string
     *         'autogroup_option'   => int
     * @return string
     */
    protected function compose_group_name($props) {
        $name = "{$props['subject']} {$props['catalog_nbr']}_{$props['section']} (Auto {$props['institution']})";

        if (!is_null($props['autogroup_option']) && $props['autogroup_option'] == 1) {
            $name = substr($props['term'], 0, 2).substr($props['term'],-2).'_'.$name;
        }

        return $name;
    }


    /**
     * log an error message into the central error log
     * @param string $course_id
     * @param string $msg
     * @return void
     */
    protected function log_error($course_id, $msg) {
        if ( !isset($this->errors[$course_id]) ) {
            $this->errors[$course_id] = array();
        }

        $this->errors[$course_id][] = $msg;
    }


    /**
     * log an action's result into the central result log
     * @param string $course_id
     * @param string $msg
     * @return void
     */
    protected function log_result($course_id, $msg) {
        if ( !isset($this->result[$course_id]) ) {
            $this->result[$course_id] = array();
        }

        $this->result[$course_id][] = $msg;
    }

}
