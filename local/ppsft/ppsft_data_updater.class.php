<?php

require_once($CFG->dirroot.'/local/genericset/lib.php');

// Nomenclature note: "source" in names refers to the source of
// record for PeopleSoft data.

// TODO: Create events to trigger action resulting from changes.

// TODO: Try eliminating circular dependency on ppsft/lib.php.
//       Might be just on ppsft_triplet_concat_sql and ppsft_term_string_from_number.

class ppsft_data_updater_exception extends Exception {}
class ppsft_no_such_class_exception extends ppsft_data_updater_exception {}


#/**
# * Helper class for getting recent updates from PeopleSoft.
# */
# class recent_enrollment_updater {}

/**
 *
 */
class ppsft_data_updater {

    private $ppsft_adapter;
    private $user_creator;
    private $current_term;

    private static $setname_student_emplid_not_in_ldap = 'ppsft/studentenrol:emplidnotinldap';

    /**
     * Requires an instance of a ppsft_data_adapter.
     */
    public function __construct($ppsft_adapter, $user_creator) {
        $this->ppsft_adapter = $ppsft_adapter;
        $this->user_creator  = $user_creator;
    }

    /**
     * To ensure that we don't miss any updates, we count the number of entries
     * with the syseffdt matching the max syseffdt.  If that number agrees
     * with the count from previous run, we can assume that we processed
     * them all last time.  Otherwise, we will process all with that syseffdt
     * in addition to newer ones.
     */
    public function update_recent_ppsft_enrollment_changes() {

        $pre_time = microtime(true);

        // On previous runs, we might have saved student emplids not yet in ldap
        // for later retry. This is where we retry.
        $this->retry_saved_emplids_not_in_ldap();

        // The last run's $maxtime is this run's $since_time.  Likewise, count.

        $since_time  = get_config('local_ppsft', 'last_max_enrol_time') ?: '';
        $check_count = get_config('local_ppsft', 'last_max_enrol_count') ?: 0;

        $ppsftchanges = $this->ppsft_adapter->get_enrollment_updates_since($since_time);

        // To be the number of rows with the $since_time syseffdt.
        $since_time_count = 0;

        // To be the emplids from rows with the $since_time syseffdt.
        $since_time_emplids = array();

        $maxtime = '';
        $maxtime_count = 0;
        $emplids = array();

        // Gather up the affected emplids. Also, get time and count for
        // updating last_max_enrol* values.
        foreach ($ppsftchanges as $ppsftchange) {
            if ($ppsftchange->syseffdt == $since_time) {
                ++$since_time_count;
                $since_time_emplids[] = $ppsftchange->um_trig_emplid;
            } else {
                $emplids[] = $ppsftchange->um_trig_emplid;
            }

            if ($ppsftchange->syseffdt > $maxtime) {
                $maxtime = $ppsftchange->syseffdt;
                $maxtime_count = 1;
            } else if ($ppsftchange->syseffdt == $maxtime) {
                ++$maxtime_count;
            }
        }

        // If the row count for the $since_time has gone up, we need
        // to process emplids for $since_time, also.
        if ($since_time_count > $check_count) {
            mtrace('$since_time_count > $check_count');
            $emplids = array_merge($emplids, $since_time_emplids);
        }
        if ($since_time_count < $check_count) {
            mtrace("ERROR??? since_time_count: $since_time_count; check_count: $check_count");
        }
        # TODO: Throw exception for less than???

        $unique_emplids = array_unique($emplids);

        mtrace(date('r') . "  Updating enrollments for " . count($unique_emplids) . " emplids", '... ');

        // Update enrollments for each emplid.
        $this->update_student_enrollments($unique_emplids);

        set_config('last_max_enrol_count', $maxtime_count, 'local_ppsft');
        set_config('last_max_enrol_time' , $maxtime      , 'local_ppsft');

        mtrace("Used " . (microtime(true) - $pre_time) . " seconds");
    }

    /**
     *
     */
    public function retry_saved_emplids_not_in_ldap() {
        $emplids = genericset_get(self::$setname_student_emplid_not_in_ldap);

        foreach ($emplids as $emplid) {
            try {
                $this->update_student_enrollment($emplid);
                genericset_remove(self::$setname_student_emplid_not_in_ldap, $emplid);
            } catch (local_user_notinldap_exception $ex) {
                // Apparently, still not in LDAP.
            } catch (local_user_exception $ex) {
                error_log("WARNING: retry_saved_emplids_not_in_ldap caught exception on emplid $emplid: ".$ex->getMessage());
            }
        }
    }

    /**
     *
     */
    private function get_current_term() {
        if (!isset($this->current_term)) {
            $this->current_term = $this->ppsft_adapter->get_current_term();
        }
        return $this->current_term;
    }

    /**
     *
     */
    public function update_terms() {
        global $DB;

        $current_term = $this->get_current_term();
        $source_terms = $this->ppsft_adapter->get_terms_since($current_term);

        $terms = $DB->get_fieldset_select('ppsft_terms', 'term', '1=1');

        $terms_to_add = array_diff($source_terms, $terms);

        foreach ($terms_to_add as $term_to_add) {
            $term_name = ppsft_term_string_from_number($term_to_add);
            $DB->insert_record('ppsft_terms',
                               array('term'      => $term_to_add,
                                     'term_name' => $term_name));
        }
    }

    /**
     * Calls update_student_enrollment for each emplid in the input array.
     * If the emplid has relevant PeopleSoft enrollments but does not exist
     * in LDAP yet, this will save the emplid for later retry.
     * The error_log call for other exceptions seems mild but will generate
     * a ticket when run in the context of a cron, which is when this is
     * intended to be run.  If used elsewhere, should consider modifying
     * error handling, accordingly.
     */
    public function update_student_enrollments($emplids) {

        foreach ($emplids as $emplid) {
            try {
                $this->update_student_enrollment($emplid);
            } catch (local_user_notinldap_exception $ex) {
                // We save the emplid so that we can retry later.

                genericset_add(self::$setname_student_emplid_not_in_ldap, $emplid);

            } catch (local_user_exception $ex) {
                error_log("WARNING: update_student_enrollment for emplid $emplid failed: ".$ex->getMessage());
            }
        }
    }

    /**
     * Updates ppsft_class_enrol for person identified by emplid to match
     * student enrollments in PeopleSoft source of record.
     * Considers only current and future terms.
     */
    public function update_student_enrollment($emplid) {
        global $DB;

        if ($user = $DB->get_record('user', array('idnumber' => $emplid), '*', IGNORE_MISSING)) {
            return $this->update_enrollment_for_user($user);
        }

        # If here, user with $emplid does not exist yet.

        $source_enrollments = $this->ppsft_adapter->get_student_enrollments($emplid,
                                                                     $this->get_current_term());

        // If user does not have any enrollments, no need to create the user.
        if (empty($source_enrollments)) {
            return;
        }

        $sql_triplets_string = static::objects_as_sql_triplet_string($source_enrollments);

        $class_matches = $this->get_triplet_matches_in_moodle($sql_triplets_string);

        if (empty($class_matches)) {
            // Nothing to do because we don't have any of these classes in Moodle, yet.
            return;
        }

        $userid = $this->user_creator->create_from_emplid($emplid);

        foreach ($class_matches as $class_match) {

            $source = $source_enrollments[$class_match->triplet];

            $this->insert_ppsft_class_enrol($userid,
                                            $class_match->ppsftclassid,
                                            $source);

        }
    }

    /**
     *
     */
    private function insert_ppsft_class_enrol($userid, $ppsftclassid, $data) {
        global $DB;

        $DB->insert_record('ppsft_class_enrol',
                           array('userid'       => $userid,
                                 'ppsftclassid' => $ppsftclassid,
                                 'status'       => $data->status,
                                 'add_date'     => $data->add_date,
                                 'drop_date'    => $data->drop_date,
                                 'grading_basis' => $data->grading_basis));
    }

    /**
     *
     */
    private function get_triplet_matches_in_moodle($sql_triplets_string) {
        global $DB;

        // This must be matched with the triplet in ppsft_data_adapter.
        $sql_triplet_concat = ppsft_triplet_concat_sql($DB);

        $sql =<<<SQL
select $sql_triplet_concat as triplet,
       pc.id as ppsftclassid
from {ppsft_classes} pc
where (pc.term, pc.institution, pc.class_nbr) in ($sql_triplets_string)
SQL;

        return $DB->get_records_sql($sql);
    }

    /**
     *
     */
    private function get_student_class_matches_in_moodle($userid, $sql_triplets_string) {
        global $DB;

        // This must be matched with the triplet in ppsft_data_adapter.
        $sql_triplet_concat = ppsft_triplet_concat_sql($DB);

        $sql =<<<SQL
select $sql_triplet_concat as triplet,
       pc.id as ppsftclassid,
       e.id  as ppsftclassenrolid,
       e.status,
       e.grading_basis
from {ppsft_classes} pc
  left join {ppsft_class_enrol} e
       on  e.ppsftclassid = pc.id
       and e.userid = :userid
where (pc.term, pc.institution, pc.class_nbr) in ($sql_triplets_string)
SQL;

        return $DB->get_records_sql($sql,
                                    array('userid' => $userid,
                                          'term'   => $this->get_current_term()));

    }

    /**
     * Updates ppsft_class_enrol for $user to match
     * student enrollments in PeopleSoft source of record.
     * Considers only current and future terms.
     * Current logic assumes that dates will only change if the status
     * changes; we check only the status to identify changed records.
     * This should be okay because adding a drop date will change the
     * status to either 'D' or 'H'.
     */
    public function update_enrollment_for_user($user) {
        global $DB;

        if (is_numeric($user)) {
            $user = $DB->get_record('user', array('id'=>$user), '*', MUST_EXIST);
        }

        $emplid = $user->idnumber;
        $userid = $user->id;

        $source_enrollments = $this->ppsft_adapter->get_student_enrollments($emplid,
                                                                     $this->get_current_term());

        if (empty($source_enrollments)) {
            $class_enrollments = array();
        } else {
            $sql_triplets_string = static::objects_as_sql_triplet_string($source_enrollments);
            $class_enrollments = $this->get_student_class_matches_in_moodle($userid, $sql_triplets_string);
        }

        foreach ($class_enrollments as $class_enrollment) {
            $source = $source_enrollments[$class_enrollment->triplet];
            if (! isset($class_enrollment->ppsftclassenrolid)) {
                // We have a ppsftclass, but no ppsft_class_enrol for this user.

                $this->insert_ppsft_class_enrol($userid,
                                                $class_enrollment->ppsftclassid,
                                                $source);

            } elseif ($class_enrollment->status != $source->status) {
                // We have local enrollment record, but the status is wrong. Update.

                $DB->update_record('ppsft_class_enrol',
                                   array('id'        => $class_enrollment->ppsftclassenrolid,
                                         'status'    => $source->status,
                                         'add_date'  => $source->add_date,
                                         'drop_date' => $source->drop_date,
                                         'vanished'  => null,
                                         'grading_basis' => $source->grading_basis));
            }
        }

        // TODO: We don't expect any enrollment records in PeopleSoft to just disappear,
        // but should we check?  Then delete from ppsft_class_enrol?

    }

    static private function objects_as_sql_triplet_string($objects_w_term_inst_class) {
        if (!empty($objects_w_term_inst_class)) {

            $sql_triplets_string = implode(',',
                array_map(
                    function ($o) {
                        return "\n($o->term, '$o->institution', $o->class_nbr)";
                    },
                    $objects_w_term_inst_class));

        } else {
            $sql_triplets_string = '';
        }

        return $sql_triplets_string;
    }

    /**
     * Updates ppsft_class_instr for $user to match instructor assignments in PeopleSoft
     * source of record.  Has disabled logic to add classes to ppsft_classes if not
     * already there.  Considers only current and future terms.
     */
    public function update_instructor_classes($user) {
        global $DB;

        $emplid = $user->idnumber;
        $userid = $user->id;

        $source_classes = $this->ppsft_adapter->get_instructor_classes($emplid,
                                                                         $this->get_current_term());

        // Get the classes that we have listed in Moodle for this user.  Get all
        // those that we have listed and outer join on instructor so that we can
        // determine both which classes we have in Moodle and which instructor associations.
        // Delete records only if the term >= current_term.  We can do this by retrieving
        // only for current term and forward so that the local classes and the source classes are
        // for the same terms.

        if (empty($source_classes)) {
            $sql_triplets_string = '';
            $classes = array();
        } else {
            $sql_triplets_string = static::objects_as_sql_triplet_string($source_classes);
            $classes = $this->get_class_matches_in_moodle($userid, $sql_triplets_string);
        }

        // Add classes and instructor associations for classes not already
        // in ppsft_classes.  Probably, we will not want to do this.  This
        // code is helpful at times in development anyway.
        // DO NOT ENABLE THIS WITHOUT MAKING ADJUSTMENTS TO INCLUDE THE COURSE
        // LONG NAME AS ONE GETS FROM get_class_by_triplet
        // WARNING: Would also need to add current enrollments.
        $add_classes = false;  // Change to true to add classes not in ppsft_classes.
        if ($add_classes) {
            $classes_to_add = array_diff_key($source_classes, $classes);
            foreach ($classes_to_add as $class_to_add) {
                $ppsftclassid = $DB->insert_record('ppsft_classes', $class_to_add);
                $DB->insert_record('ppsft_class_instr',
                                   array('userid'       => $userid,
                                         'ppsftclassid' => $ppsftclassid));
            }
        }

        // For classes that already exist in ppsft_classes, add instructor
        // associations if not already in ppsft_class_instr.
        $classes_to_check = array_intersect_key($classes, $source_classes);
        foreach ($classes_to_check as $class_to_check) {
            if (! isset($class_to_check->userid)) {
                $DB->insert_record('ppsft_class_instr',
                                   array('userid'       => $userid,
                                         'ppsftclassid' => $class_to_check->id));
            }
        }

        // Delete any instructor associations that are no longer in PeopleSoft.
        $class_instrs_to_delete = $this->get_class_instr_mismatches_in_moodle($userid, $sql_triplets_string);
        foreach ($class_instrs_to_delete as $class_instr_to_delete) {
            $DB->delete_records('ppsft_class_instr',
                                array('id' => $class_instr_to_delete->id));
        }
    }

    /**
     * Returns a ppsftclassid.  Adds from ppsft, if necessary.
     */
    public function find_ppsft_class($term, $institution, $classnbr) {
        global $DB;

        $ppsftclassid = $DB->get_field('ppsft_classes',
                                       'id',
                                       array('term'        => $term,
                                             'institution' => $institution,
                                             'class_nbr'   => $classnbr));

        if (! $ppsftclassid) {
            $ppsftclassid = $this->add_class_by_triplet($term,
                                                        $institution,
                                                        $classnbr);
        }
        return $ppsftclassid;
    }

    /**
     * Adds PeopleSoft class to ppsft_classes. Unique index on triplet
     * will cause exception if attempting to add the same class twice.
     */
    public function add_class_by_triplet($term, $institution, $class_nbr) {
        global $DB;

        $class = $this->ppsft_adapter->get_class_by_triplet($term, $institution, $class_nbr);

        if (!$class) {
            throw new ppsft_no_such_class_exception(
                    "No PeopleSoft class found for $term $institution $class_nbr");
        }

        $ppsftclassid = $DB->insert_record('ppsft_classes', $class);

        // 20110915 Colin. Add student and instructor associations.  We especially
        //                 need student enrollments since incremental update will
        //                 not pick up past enrollments.
        $ppsftclass = $this->get_ppsft_class($ppsftclassid);
        $this->update_class_enrollment($ppsftclass);
        $this->update_class_instructors($ppsftclass);

        return $ppsftclassid;
    }

    /**
     * Simple helper to avoid duplication.
     */
    private function get_ppsft_class($ppsftclassid) {
        global $DB;
        return $DB->get_record('ppsft_classes',
                               array('id'=>$ppsftclassid),
                               '*',
                               MUST_EXIST);
    }

    /**
     * Updates ppsft_class_instr for a class to match the
     * PeopleSoft data source of record.
     * Adds to ppsft_class_instr only if instructor is already in Moodle,
     * unless addusers is set to true, in which case it will them to Moodle.
     */
    public function update_class_instructors($ppsftclass, $addusers=false) {
        global $DB;

        // If parameter is just the id, then get the object.
        if (is_numeric($ppsftclass)) {
            $ppsftclass = $this->get_ppsft_class($ppsftclass);
        }

        $source_instr_emplids = $this->ppsft_adapter->get_class_instructors(
                                                    $ppsftclass->term,
                                                    $ppsftclass->institution,
                                                    $ppsftclass->class_nbr);

        if (! empty($source_instr_emplids)) {
            // Make keys match values for convenience later.
            $source_instr_emplids = array_combine($source_instr_emplids,
                                                  $source_instr_emplids);
        }

        $sql =<<<SQL
select u.idnumber, u.id
from {ppsft_class_instr} ci
  join {user} u on u.id = ci.userid
where ci.ppsftclassid = :ppsftclassid
SQL;

        $mdl_ppsft_instrs = $DB->get_records_sql(
                                         $sql,
                                         array('ppsftclassid' => $ppsftclass->id));

        $instrs_to_delete = array_diff_key($mdl_ppsft_instrs, $source_instr_emplids);
        $emplids_to_add   = array_diff_key($source_instr_emplids, $mdl_ppsft_instrs);

        foreach ($instrs_to_delete as $instr) {
            $DB->delete_records('ppsft_class_instr', array('id' => $instr->id));
        }

        foreach ($emplids_to_add as $emplid) {
            if (!$userid = $DB->get_field('user', 'id', array('idnumber' => $emplid))
                and $addusers)
            {
                // User is not in Moodle yet and the input parameter says to add, if necessary.
                $userid = $this->user_creator->create_from_emplid($emplid);
            }
            if ($userid) {
                $DB->insert_record('ppsft_class_instr',
                                   array('ppsftclassid' => $ppsftclass->id,
                                         'userid'       => $userid));
            }
        }
    }

    /**
     * Updates ppsft_class_enrol for a class to match the
     * PeopleSoft data source of record.
     */
    // TODO: Update enrollment data.
    //       1. For source enrollments not in ppsft_class_enrol,
    //           a. create user, if necessary,
    //           b. if user exists but needs emplid, update,
    //           c. insert enrollment record.
    //           (What do we do if username already exists with different emplid?)
    //           (What do we do if same emplid exists for multiple users?)
    //       2. For source enrollments in ppsft_class_enrol with changes, update.
    //       3. For ppsft_class_enrol not in source, mark as vanished.
    public function update_class_enrollment($ppsftclass) {
        global $DB;

        // If parameter is just the id, then get the object.
        if (is_numeric($ppsftclass)) {
            $ppsftclass = $this->get_ppsft_class($ppsftclass);
        }

        // Both SQL result arrays must be keyed on emplid (aka idnumber).

        //  USING 'H' FOR WITHDRAWN STATUS
        //  (CAN'T USE 'W' BECAUSE PPSFT USES THAT FOR WAIT-LISTED).
        //  WE DERIVE IT FROM enrl_drop_dt AND stdnt_enrl_status
        //  IN PEOPLESOFT.

        $source_enrolled = $this->ppsft_adapter->get_class_enrollments(
                                                    $ppsftclass->term,
                                                    $ppsftclass->institution,
                                                    $ppsftclass->class_nbr);

        #error_log('source_enrolled: ' . print_r($source_enrolled, true));

        // Might need to reconsider this.  Not sure how we could have records
        // drop to zero after having had some previously, which is the only time
        // this would matter.
        if (empty($source_enrolled)) {
            return;
        }

        $sql_idnumbers_string = implode(',',
            array_map(
                function ($ee) {return "'$ee->emplid'";},
                $source_enrolled));

        // Get users for emplids (idnumbers) already in Moodle.  Left join on
        // allows us to get even those without an existing mdl_ppsft_class_enrol
        // for the course.
        // We also get users that have a mdl_ppsft_class_enrol record but
        // no corresponding source ppsft enrollment record.
        $sql =<<<SQL
select u.idnumber, u.id userid, e.status, e.id as ppsftclassenrolid, e.vanished, e.grading_basis
from {user} u
  left join {ppsft_class_enrol} e
         on  e.userid = u.id
         and e.ppsftclassid = :ppsftclassid
where e.userid is not null or u.idnumber in ($sql_idnumbers_string)
SQL;

        $existing_moodle_users = $DB->get_records_sql(
                            $sql,
                            array('ppsftclassid' => $ppsftclass->id));

        #print_r($existing_moodle_users);

        // First, handle users that already exist in Moodle and insert or update
        // a ppsft_class_enrol record for them as necessary.

        foreach ($existing_moodle_users as $user) {

            if (! array_key_exists($user->idnumber, $source_enrolled)) {
                if (empty($user->vanished)) {
                    $DB->update_record('ppsft_class_enrol',
                                       array('id'        => $user->ppsftclassenrolid,
                                             'vanished'  => time()));
                }
                continue;
            }

            $source = $source_enrolled[$user->idnumber];

            if (! isset($user->status) ) {

                // User exists but ppsft_class_enrol record does not.
                $this->insert_ppsft_class_enrol($user->userid,
                                                $ppsftclass->id,
                                                $source);

            } elseif ($user->status != $source->status or $user->grading_basis != $source->grading_basis) {

                /*
                 * Current logic assumes that dates will only change if the status
                 * changes; we check only the status to identify changed records.
                 * This should be okay because adding a drop date will change the
                 * status to either 'D' or 'H'.
                 */

                // User and ppsft_class_enrol records exist, but status or
                // grading_basis is incorrect.
                // If change is to 'D' or 'H', execute full refresh for student
                // to prevent mistakenly unenrolling a user as the result of
                // section change.

                if ($source->status == 'E') {

                    $DB->update_record('ppsft_class_enrol',
                                       array('id'        => $user->ppsftclassenrolid,
                                             'status'    => $source->status,
                                             'add_date'  => $source->add_date,
                                             'drop_date' => $source->drop_date,
                                             'vanished'  => null,
                                             'grading_basis' => $source->grading_basis));

                } else {

                    $this->update_enrollment_for_user($user->userid);

                }
            } elseif (! empty($user->vanished)) {
                $DB->update_record('ppsft_class_enrol',
                                   array('id'       => $user->ppsftclassenrolid,
                                         'vanished' => null));
            }
        }

        // Next, handle any users that are not yet in Moodle.

        $users_to_add = array_diff_key($source_enrolled, $existing_moodle_users);

        if (! empty($users_to_add)) {

            $rv = $this->user_creator->create_from_emplids(array_keys($users_to_add));
            $new_user_ids = $rv['moodle_ids'];
            if (!empty($rv['errors'])) {
                foreach ($rv['errors'] as $emplid => $errormsg) {
                    if ($errormsg === NO_MATCH_IN_LDAP_ERR_MSG) {
                        // If the user is not in LDAP, then they probably dropped completely from
                        // U enrollment.  Silently ignore this error.
                    } else {
                        // One possible error cause is that, due to a user's previous status wrt the U,
                        // the user's Moodle record could be missing an idnumber (emplid). We can fix that
                        // problem.
                        if ($userid = $this->user_creator->attempt_user_fix($emplid)) {
                            // Now that we have a good user, add the user to the list for
                            // mdl_ppsft_class_enrol inserts.
                            $new_user_ids[$emplid] = $userid;
                        } else {
                            error_log("In update_class_enrollment: Error creating user with emplid $emplid. $errormsg");
                        }
                    }
                }
            }

            foreach ($new_user_ids as $emplid => $new_user_id) {
                // First, confirm that the user isn't already in the table,
                // since the UI and the cron sometimes have a race condition.
                if (!$DB->record_exists('ppsft_class_enrol',
                                        array('userid'=>$new_user_id,
                                              'ppsftclassid'=>$ppsftclass->id)))
                {
                    $source = $source_enrolled[$emplid];

                    $this->insert_ppsft_class_enrol($new_user_id,
                                                    $ppsftclass->id,
                                                    $source);
                }
            }
        }

        $DB->set_field('ppsft_classes', 'enrol_updated', time(), array('id'=>$ppsftclass->id));
    }

    /**
     * In environments with a lot of data (like production), this could run
     * for a long time.
     */
    public function update_all_class_enrollments() {
        global $DB;

        $sql = 'select pc.* from {ppsft_classes} pc where term >= :term';
        $ppsftclasses = $DB->get_records_sql($sql, array('term' => $this->get_current_term()));

        foreach ($ppsftclasses as $ppsftclass) {
            $this->update_class_enrollment($ppsftclass);
        }
    }

    /**
     * In environments with a lot of data (like production), this could run
     * for a long time.
     */
    public function update_all_class_instructors() {
        global $DB;

        $sql = 'select pc.* from {ppsft_classes} pc where term >= :term';
        $ppsftclasses = $DB->get_records_sql($sql, array('term' => $this->get_current_term()));

        foreach ($ppsftclasses as $ppsftclass) {
            $this->update_class_instructors($ppsftclass);
        }
    }

    /**
     * Retrieves ppsft class records that match the
     * given user as instructor and any of the passed triplets.
     */
    private function get_class_matches_in_moodle($userid, $sql_triplets_string) {
        global $DB;

        // This must be matched with the triplet in ppsft_data_adapter.
        $sql_triplet_concat = ppsft_triplet_concat_sql($DB);

        $sql =<<<SQL
select $sql_triplet_concat as triplet,
       pc.*, ci.userid
from {ppsft_classes} pc
  left join {ppsft_class_instr} ci
       on  ci.ppsftclassid = pc.id
       and ci.userid = :userid
where (pc.term, pc.institution, pc.class_nbr) in ($sql_triplets_string)
  and pc.term >= :term
SQL;

        return $DB->get_records_sql($sql,
                                    array('userid' => $userid,
                                          'term'   => $this->get_current_term()));
    }

    /**
     * Returns the instructor class assignments in ppsft_class_instr
     * that are not in the $sql_triplets_string parameter.  If the
     * $sql_triplets_string parameter contains instructor assignments
     * from the PeopleSoft source of record, the records return by
     * this function are instructor assignments in ppsft_class_instr
     * that are no longer valid.
     */
    private function get_class_instr_mismatches_in_moodle($userid, $sql_triplets_string) {
        global $DB;

        // This must be matched with the triplet in ppsft_data_adapter.
        $sql_triplet_concat = ppsft_triplet_concat_sql($DB);

        if (!empty($sql_triplets_string)) {
            $triplets_condition =
                "and (pc.term, pc.institution, pc.class_nbr) NOT in ($sql_triplets_string)";
        } else {
            $triplets_condition = '';
        }

        $sql =<<<SQL
select $sql_triplet_concat as triplet,
       ci.*
from {ppsft_classes} pc
  join {ppsft_class_instr} ci
    on  ci.ppsftclassid = pc.id
    and ci.userid = :userid
where pc.term >= :term
    $triplets_condition
SQL;

        return $DB->get_records_sql($sql,
                                    array('userid' => $userid,
                                          'term'   => $this->get_current_term()));
    }

    /**
     * Intended for use by the ppsft user_deleted event handler.
     */
    public function purge_user($user) {
        global $DB;

        $userid = is_numeric($user) ? $user : $user->id;

        $DB->delete_records('ppsft_class_instr', array('userid' => $userid));
        $DB->delete_records('ppsft_class_enrol', array('userid' => $userid));
    }

    /**
     * For mdl_ppsft_class_enrol rows for which the corresponding PeopleSoft
     * enrollment has simply vanished (instead of marked as "D" in the PeopleSoft
     * student enrollment table), looks for an indication in PeopleSoft that the
     * enrollment record was deleted.  If such an indication is found, marks
     * the mdl_ppsft_class_enrol row as dropped.
     */
    public function sync_vanished_enrollments() {
        global $DB;

        $vanished = $this->get_nondropped_vanished_enrollments();

        foreach ($vanished as $venrol) {

            $deleted_timestamp = $this->ppsft_adapter->get_enrollment_row_delete_action(
                                                $venrol->idnumber,
                                                $venrol->term,
                                                $venrol->institution,
                                                $venrol->class_nbr);

            if (! empty($deleted_timestamp)) {
                $msg = "Marking vanished as dropped: $venrol->idnumber ".
                       "$venrol->term $venrol->institution $venrol->class_nbr";
                if (CLI_SCRIPT) {
                    echo $msg . "\n";
                } else {
                    error_log($msg);
                }

                $enrolupdate['id']     = $venrol->id;
                $enrolupdate['status'] = 'D';
                $DB->update_record('ppsft_class_enrol', $enrolupdate);
            }
        }

    }

    private function get_nondropped_vanished_enrollments() {
        global $DB;

        $sql =<<<SQL
select pce.*, u.idnumber, pc.term, pc.institution, pc.class_nbr
from {ppsft_class_enrol} pce
  join {ppsft_classes} pc on pc.id = pce.ppsftclassid
  join {user} u on u.id = pce.userid
where pce.status='E'
  and pce.vanished > 0
  and u.idnumber is not null
  and trim(u.idnumber) <> '';
SQL;
        return $DB->get_records_sql($sql);
    }

}


