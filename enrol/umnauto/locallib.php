<?php

require_once("{$CFG->dirroot}/lib/gradelib.php");

// See also enrol_cohort_sync in enrol/cohort/locallib.php, which is where
// much of this logic comes from.

function enrol_umnauto_sync($courseid = NULL) {
    global $DB;

    if (enrol_is_enabled('umnauto')) {

        $syncer = new enrol_umnauto_syncer($courseid);
        $syncer->sync();
    }

}

//================================================================
// IMPLEMENTATION
//================================================================

class enrol_umnauto_syncer {

    private $umnauto_plugin;

    /**
     * If $courseid is null, enrollments will be synchronized for all
     * courses.
     */
    private $courseid;

    /**
     *
     */
    public function __construct($courseid = NULL) {
        $this->umnauto_plugin = enrol_get_plugin('umnauto');
        $this->courseid = $courseid;
    }

    /**
     *
     */
    public function sync() {

        $this->do_enrollments();

        $this->do_role_assignments();

        // Should we be doing unenrollments whether or not
        // the enrollment plugin is enabled?  enrol/cohort does.
        $this->do_unenrollments();

        // enrol/cohort does role unassignments, but enrol_plugin->unenrol_user
        // seems to do a pretty good job of cleaning up anyway, so may not
        // need to here.
    }

    /**
     * Finds PeopleSoft enrollments that do not have a corresponding
     * Moodle course enrollment and enrols those users in the Moodle
     * course.
     */
    public function do_enrollments() {
        global $DB;

        $onecourse = $this->courseid ? "AND e.courseid = :courseid" : "";

        $sql = "SELECT ce.userid,
                       e.id AS enrolid,
                       e.courseid,
                       COUNT(gh.id) AS gh_count
                FROM {enrol} e
                     INNER JOIN {enrol_umnauto_classes} uc ON uc.enrolid = e.id
                     INNER JOIN {ppsft_class_enrol} ce ON ce.ppsftclassid = uc.ppsftclassid
                                AND ce.status = 'E'
                     LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
                               AND ue.userid = ce.userid
                     LEFT JOIN {grade_items} gi ON gi.courseid = e.courseid
                     LEFT JOIN {grade_grades_history} gh ON gh.itemid = gi.id
                               AND gh.userid = ce.userid
                WHERE e.status = :statusenabled
                      AND e.enrol = 'umnauto'
                      AND ue.id IS NULL
                      {$onecourse}
                GROUP BY ce.userid, e.id, e.courseid";

        $params = array('statusenabled' => ENROL_INSTANCE_ENABLED,
                        'courseid'      => $this->courseid);

        $rs = $DB->get_recordset_sql($sql, $params);
        $instances = array(); //cache
        foreach($rs as $userenrol) {
            $enrolid = $userenrol->enrolid;
            if (!isset($instances[$enrolid])) {
                $instances[$enrolid] = $DB->get_record('enrol',
                                                       array('id'=>$enrolid));
            }
            $this->umnauto_plugin->enrol_user($instances[$enrolid], $userenrol->userid);

            // recover grade if applicable
            if ($userenrol->gh_count > 0) {
                grade_recover_history_grades($userenrol->userid, $userenrol->courseid);
            }
        }
        $rs->close();
        unset($instances);
    }


    /**
     * Executes unenrollments.
     * Does not execute unenrollments for PeopleSoft classes that are no
     * longer associated with the Moodle course.
     */
    public function do_unenrollments() {
        global $DB;

        //$umnauto_course = $DB->get_record('enrol_umnauto_course',
        //                                  array('courseid' => $data->id));

        $onecourse = $this->courseid ? "AND e.courseid = :courseid" : "";

        # TODO: Make unique enrol_umnauto_course index on courseid.

        // For ppsft drops, we are defaulting to drop the enrollment if no enrol_umnauto_course record.
        // For ppsft withdrawns, we are defauting to keep the enrollment.

        // In the subselect in the below SQL, references to the outer select
        // must be in the where clause instead of the join clause to be
        // correct SQL and work.

        // The "not exists" subselect removes rows
        // for users that should not be removed because they are associated
        // through a PeopleSoft class other than the one that would otherwise
        // trigger the unenrol.

        $sql =
"select distinct ue.userid, e.id as enrolid
 from {enrol} e
   join {enrol_umnauto_classes} uc on uc.enrolid=e.id
   join {ppsft_class_enrol} ce on ce.ppsftclassid=uc.ppsftclassid
   join {user_enrolments} ue on ue.enrolid=e.id and ue.userid=ce.userid
   left join {enrol_umnauto_course} ucs on ucs.courseid=e.courseid
 where e.status=:statusenabled and e.enrol='umnauto' $onecourse
     and ((ce.status = 'D' and (ucs.auto_drop = 1 or ucs.auto_drop is null))
             or (ce.status = 'H' and ucs.auto_withdraw = 1))
     and not exists
 (
  select *
  from {enrol} e2
       join {enrol_umnauto_classes} uc2 on uc2.enrolid=e2.id
       join {ppsft_class_enrol} ce2 on ce2.ppsftclassid=uc2.ppsftclassid
       left join {enrol_umnauto_course} ucs2 on ucs2.courseid=e2.courseid
  where e2.id = e.id and
        ce2.userid=ue.userid and
        (ce2.status='E'
          or (ce2.status='D' and ucs2.auto_drop<>1)
          or (ce2.status='H' and (ucs2.auto_withdraw is null or ucs2.auto_withdraw<>1)))
 )";

        $params = array('statusenabled' => ENROL_INSTANCE_ENABLED,
                        'courseid'      => $this->courseid);

        $rs = $DB->get_recordset_sql($sql, $params);
        $instances = array(); //cache
        foreach($rs as $ue) {

            if (!isset($instances[$ue->enrolid])) {
                $instances[$ue->enrolid] = $DB->get_record('enrol', array('id'=>$ue->enrolid));
            }
            $this->umnauto_plugin->unenrol_user($instances[$ue->enrolid], $ue->userid);
        }
        $rs->close();
        unset($instances);
    }

    /**
     *
     */
    public function do_role_assignments() {
        global $DB;

        $onecourse = $this->courseid ? "AND e.courseid = :courseid" : "";

        $sql = "SELECT e.roleid, ue.userid, c.id AS contextid, e.id AS itemid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'umnauto' AND e.status = :statusenabled $onecourse)
                  JOIN {context} c ON (c.instanceid = e.courseid AND c.contextlevel = :coursecontext)
             LEFT JOIN {role_assignments} ra ON (ra.contextid = c.id AND ra.userid = ue.userid AND ra.itemid = e.id AND ra.component = 'enrol_umnauto' AND e.roleid = ra.roleid)
                 WHERE ra.id IS NULL and e.roleid <> 0 and e.roleid IS NOT NULL";
        $params = array();
        $params['statusenabled'] = ENROL_INSTANCE_ENABLED;
        $params['coursecontext'] = CONTEXT_COURSE;
        $params['courseid'] = $this->courseid;

        $rs = $DB->get_recordset_sql($sql, $params);
        foreach($rs as $ra) {
            role_assign($ra->roleid, $ra->userid, $ra->contextid, 'enrol_umnauto', $ra->itemid);
        }
        $rs->close();
    }


    // Unassign roles assigned through umnauto for which there is no
    // longer a corresponding umnauto enrollment.
/*  Not sure this is ever needed since enrol_plugin->unenrol_user seems to do a
    pretty good job of cleaning up.

    $onecourse = $courseid ? "AND c.instanceid = :courseid" : "";

    $sqlsave = "SELECT ra.roleid, ra.userid, ra.contextid, ra.itemid
              FROM {role_assignments} ra
              JOIN {context} c ON (c.id = ra.contextid AND c.contextlevel = :coursecontext $onecourse)
         LEFT JOIN (SELECT e.id AS enrolid, e.roleid, ue.userid
                      FROM {user_enrolments} ue
                      JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'umnauto')
                   ) x ON (x.enrolid = ra.itemid AND ra.component = 'enrol_umnauto' AND x.roleid = ra.roleid AND x.userid = ra.userid)
             WHERE x.userid IS NULL AND ra.component = 'enrol_umnauto'";

    $params = array('coursecontext' => CONTEXT_COURSE, 'courseid' => $courseid);

    $rs = $DB->get_recordset_sql($sql, $params);
    foreach($rs as $ra) {
        role_unassign($ra->roleid, $ra->userid, $ra->contextid, 'enrol_umnauto', $ra->itemid);
    }
    $rs->close();
*/

}

