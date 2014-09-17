<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

/**
 * The request_form_handler classes use the course request manager.  The intended
 * difference in the intent between the form_handler and the manager is that the
 * handler handles the data as received by the forms and translates them as necessary.
 * The manager, on the other hand, is entirely independent of the forms.
 */

class local_course_request_form_handler {

    protected $course_request_manager;

    /**
     *
     */
    public function __construct($course_request_manager) {
        $this->course_request_manager = $course_request_manager;
    }

    /**
     *
     */
    public function handle($postdata, $customdata) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $requestid = $this->add_course_request($postdata);
        $this->add_requested_instructors_to_request($requestid, $postdata);

        $transaction->allow_commit();

        $this->course_request_manager->notify_course_requested($requestid);
    }

    /**
     *
     */
    protected function add_course_request($data) {
        global $USER;

        $data->requesterid = $USER->id;

        $requestid = $this->course_request_manager->insert_course_request($data);
        return $requestid;
    }

    protected function get_role_id($shortname) {
        global $DB;

        if (!$roleid = $DB->get_field('role', 'id', array('shortname'=>$shortname))) {
            throw new Exception("No $shortname role id found");
        }

        return $roleid;
    }

    /**
     *
     */
    protected function add_requested_instructors_to_request($requestid, $data) {
        global $DB, $USER;

        $assignable = get_course_request_assignable_roles();

        $manager = $this->course_request_manager;

        foreach ($data->additionalroleselect as $index => $roleid) {
            if (empty($roleid) or ! array_key_exists($roleid, $assignable)) {
                continue;
            }
            $x500s = split_x500_text_list($data->additionalroleuserlist[$index]);
            foreach ($x500s as $x500) {
                $sendemail = $data->additionalroleemail[$index];
                $manager->add_course_user_by_x500($requestid, $x500, $roleid, $sendemail);
            }
        }

        // Add the current user with the role selected for own role in the course.
        if (array_key_exists($data->yourrole, $assignable)) {
            $manager->add_course_user($requestid, $USER->id, $data->yourrole);
        }
    }
}

class local_course_request_form_handler_ppsft extends local_course_request_form_handler {

    protected $ppsft_updater;

    public function __construct($course_request_manager, $ppsft_updater) {
        parent::__construct($course_request_manager);
        $this->ppsft_updater = $ppsft_updater;
    }

    /**
     *
     */
    public function handle($postdata, $customdata) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        // Add the requested ppsft classes to the local tables.  Also associates ppsft instructors.
        $ppsftclasses = $this->get_requested_ppsftclasses($postdata);

        $this->course_request_manager->build_course_names_from_ppsft_classes($postdata, $ppsftclasses);

        $requestid = $this->add_course_request($postdata);
        $this->add_requested_instructors_to_request($requestid, $postdata);

        $this->add_ppsftclasses_to_request($requestid, $ppsftclasses);

        $this->add_primary_ppsft_instructors_to_request($requestid, $customdata);

        $transaction->allow_commit();

        $this->course_request_manager->notify_course_requested($requestid);
    }

    /**
     *
     */
    private function add_primary_ppsft_instructors_to_request($requestid, $customdata) {
        global $DB;

        // The business requirement (from Mark) is that primary ppsft instructors
        // ALWAYS start with the Moodle instructor role.

        $emplids = $customdata['primaryinstructoremplids'];
        if (empty($emplids)) return;

        $emplids = preg_grep('/^\d+$/', $emplids);
        if (empty($emplids)) return;

        $emplidsqlstring = "'".implode("','", $emplids)."'";

        $sql =<<<SQL
select distinct ci.userid
from {ppsft_class_instr} ci
  join {user} u on u.id=ci.userid
  join {course_request_classes} rc on rc.ppsftclassid=ci.ppsftclassid
where rc.courserequestid=:requestid and
      u.idnumber in ($emplidsqlstring);
SQL;

        $primaries = $DB->get_fieldset_sql($sql, array('requestid'=>$requestid));
        $instructor_roleid = $this->get_role_id('editingteacher');
        foreach ($primaries as $userid) {
            $this->course_request_manager->add_course_user($requestid, $userid, $instructor_roleid);
        }
    }

    /**
     * Adds classes, as necessary, to the ppsft_classes table. We also
     * update the class instructors to avoid stale data on these
     * classes which are actively being worked.
     */
    private function get_requested_ppsftclasses($data) {
        global $USER, $DB;

        # TODO: Would meet design intent better if used manager.

        if (empty($data->classes)) {
            return array();
        }

        $ppsftclassids = array();

        $triplets = ppsft_search::get_triplet_array_map(array_keys($data->classes));

        foreach ($triplets as $tripletstring=>$triplet) {

            // Find the class in ppsft_classes and add it if necessary.
            $ppsftclassid = $this->ppsft_updater->find_ppsft_class(
                                                        $triplet['term'],
                                                        $triplet['institution'],
                                                        $triplet['clsnbr']);

            $this->ppsft_updater->update_class_instructors($ppsftclassid);

            $ppsftclassids[] = $ppsftclassid;
        }
        return $DB->get_records_list('ppsft_classes', 'id', $ppsftclassids);
    }

    /**
     * Adds ppsftclass associations to course request.
     */
    private function add_ppsftclasses_to_request($requestid, $ppsftclasses) {
        foreach ($ppsftclasses as $ppsftclass) {
            $this->course_request_manager->add_course_ppsft_class($requestid, $ppsftclass->id);
        }
    }
}
