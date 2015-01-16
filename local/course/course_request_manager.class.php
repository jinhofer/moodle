<?php

# TODO: Need to review permission checking to get here.

class course_request_manager {

    private $user_creator;
    private $course_creator;
    private $course_xfer_client;

    /**
     *
     */
    public function __construct($user_creator, $course_creator, $course_xfer_client) {
        $this->user_creator       = $user_creator;
        $this->course_creator     = $course_creator;
        $this->course_xfer_client = $course_xfer_client;
    }

    /**
     *
     */
    public function process_migration_responses() {
        $this->course_xfer_client->reset_cache();

        $this->check_migration_repository_dir();

        $responses = $this->course_xfer_client->get_current_response_list();
        foreach ($responses as $requestid => $filepath) {
            try {
                $this->process_migration_response($requestid, $filepath);
            } catch (Exception $ex) {
                error_log('Exception in process_migration_response. ' . $ex);
            }
        }
    }

    private function check_migration_repository_dir() {
        $dirpath = $this->get_migration_repository_dir();
        if (! is_dir($dirpath)) {
            throw new Exception("Migration repository directory $dirpath does not exist.");
        }
    }

    /**
     * Processing typically involves copying the backup file to the
     * migration repository and updating the request record.
     */
    public function process_migration_response($requestid, $filepath) {
        global $DB, $USER;

        // Validate requestid and filepath.
        $file_requestid = local_course_requestid_from_filename($filepath);
        if ($requestid <> $file_requestid) {
            throw new Exception("$filepath not named for requestid $requestid");
        }

        // Check the course_request record for status.
        if (CRS_REQ_STATUS_MIGRATING != $DB->get_field('course_request_u',
                                                       'status',
                                                       array('id'=>$requestid)))
        {
            throw new Exception("Request $requestid not in migrating status ($filepath)");
        }

        // Copy file to moodledata filesystem repository.
        $filename = basename($filepath);
        $destination = $this->get_migration_repository_dir() . "/$filename";
        copy($filepath, $destination);

        $suffix = pathinfo($filepath, PATHINFO_EXTENSION);
        # TODO: Document this special suffix value.
        $newstatus = $suffix == 'err' ? CRS_REQ_STATUS_ERROR : CRS_REQ_STATUS_READY;

        // Update request status.
        $DB->update_record('course_request_u',
                           array('id'=>$requestid,
                                 'timemodified'=>time(),
                                 'modifierid'=>$USER->id,
                                 'status'=>$newstatus,
                                 'backupfile'=>$destination));

        // Delete request file. This should cause the server on the other end to
        // delete the response file.
        $this->course_xfer_client->delete_request($requestid);
    }


    /**
     * Convenience method for setting the request status.
     */
     private function set_request_status($request, $status) {
        global $DB, $USER;
        $requestid = is_numeric($request) ? $request : $request->id;
        $DB->update_record('course_request_u', array('id'           => $requestid,
                                                     'timemodified' => time(),
                                                     'modifierid'   => $USER->id,
                                                     'status'       => $status));
    }

    /**
     *
     */
    # TODO: UNIT TEST
    public function check_request_file_statuses($fix=true) {
        global $DB;

        $this->course_xfer_client->reset_cache();

        $migratingrequestids = $DB->get_fieldset_select('course_request_u',
                                                        'id',
                                                        'status = ?',
                                                        array(CRS_REQ_STATUS_MIGRATING));
        $sentrequestids = array_keys($this->course_xfer_client->get_sent_request_list());

        $missingsent = array_diff($migratingrequestids, $sentrequestids);
        $extrasent   = array_diff($sentrequestids, $migratingrequestids);

        #var_export($missingsent);
        #var_export($extrasent);

        // Handle the files that should exist, but do not.
        foreach ($missingsent as $requestid) {
            error_log("Request file for $requestid is missing and request is MIGRATING.");

            if ($fix) {
                // Race condition not possible as long as request_migration writes
                // the file before setting the status to MIGRATING and this method
                // first gets status before getting file list.
                $this->set_request_status($requestid, CRS_REQ_STATUS_NEW);
                error_log("Set status for request $requestid back to NEW.");
            }
        }

        foreach ($extrasent as $requestid) {
            $request = $DB->get_record('course_request_u', array('id'=>$requestid));
            if (!$request) {
                error_log("Request file for $requestid, but no such request in database.");
                if ($fix) {
                    $this->course_xfer_client->delete_request($requestid);
                    error_log("Deleted request file for $requestid");
                }
                continue;
            }
            switch ($request->status) {
                case CRS_REQ_STATUS_NEW:
                    error_log("Request file for $requestid, but request is NEW.");
                    if ($fix) {
                        // Race condition can result if other process has just
                        // created the file and is set status to MIGRATING after
                        // the last read. To be sure, we will change status only
                        // if the file is more than 30 seconds old.
                        $request_age = $this->course_xfer_client->get_request_age($requestid);
                        if ($request_age > 30) {
                            $this->set_request_status($requestid, CRS_REQ_STATUS_ERROR);
                        }
                        error_log("Set status for request $requestid to ERROR.");
                    }
                    break;
                case CRS_REQ_STATUS_READY:
                    error_log("Request file for $requestid, but request is READY.");
                    if ($fix) {
                        // Race condition not a problem here because delete_request
                        // handles delete by another process in stride.
                        $this->course_xfer_client->delete_request($requestid);
                        error_log("Deleted request file for $requestid");
                    }
                    break;
                case CRS_REQ_STATUS_CANCELED:
                case CRS_REQ_STATUS_COMPLETE:
                    error_log("Request file for $requestid, but request is READY.");
                    if ($fix) {
                        // Race condition not a problem here because both
                        // file delete operations are will handle a delete
                        // by another process in stride.
                        $this->course_xfer_client->delete_request($requestid);
                        error_log("Deleted request file for $requestid");
                        $this->delete_file_from_migration_dir($request->backupfile);
                    }
                    break;
            }
        }
    }

    /**
     * Returns pending requests. If requesterid is passed, includes
     * pending requests only for that user.
     */
    public function get_pending_requests($requesterid = null) {
        global $DB;

        $requestercondition = '';
        if (! empty($requesterid)) {
            $requestercondition = ' and requesterid = :requesterid ';
        }

        // Assumes that all status value below CRS_REQ_STATUS_CANCELED indicate
        // pending. See CRS_REQ_STATUS_* values.
        $pending = $DB->get_records_select('course_request_u',
                                           "status < :status $requestercondition",
                                           array('status' => CRS_REQ_STATUS_CANCELED,
                                                 'requesterid' => $requesterid));
        return $pending;
    }

    public function insert_course_request($request) {
        global $DB;

        if (isset($request->courseid)) {
            error_log('Attempt to set courseid in insert_course_request');
            throw new Exception('Invalid request state.');
        }

        $request->categoryid = $this->lookup_categoryid($request);

        $request->status = CRS_REQ_STATUS_NEW;
        $request->timecreated = time();

        $requestid = $DB->insert_record('course_request_u', $request);

        return $requestid;
    }

    /**
     *
     */
    public function request_migration($course_request) {

        $instance = local_course_parse_courseurl_instancename($course_request->sourcecourseurl);

        if (! is_valid_remote_instancename($instance)) {
            throw new Exception('Source course URL does not point to a valid Moodle source instance.');
        }

        $migration_request = array('requestid'       => $course_request->id,
                                   'sourcecourseurl' => $course_request->sourcecourseurl,
                                   'userdata'        => $course_request->copyuserdata);

        $this->course_xfer_client->write_request($migration_request);

        $this->set_request_status($course_request->id, CRS_REQ_STATUS_MIGRATING);
    }

    /**
     * Adds the user with the given x500 as a user to be associated with
     * the new course with the given role.  Creates the user in Moodle
     * if the user does not already exist in Moodle.
     */
    public function add_course_user_by_x500($requestid, $x500, $roleid, $sendemail) {
        global $DB;

        $username = x500_to_moodle_username($x500);
        $userid = $DB->get_field('user', 'id', array('username' => $username));
        if (! $userid) {
            $userid = $this->user_creator->create_from_x500($x500);
        }
        $this->add_course_user($requestid, $userid, $roleid, $sendemail);
        return $userid;
    }

    /**
     * $sendemail indicates whether user should receive emails related to this course request.
     */
    public function add_course_user($requestid, $userid, $roleid, $sendemail=true) {
        global $DB;

        // Add only if user does not already have that role.
        // If user already has that role but is not set to receive email and
        // new $sendemail is true, then update to send email.

        $cruser = $DB->get_record('course_request_users',
                                  array('courserequestid' => $requestid,
                                        'userid'          => $userid,
                                        'roleid'          => $roleid));
        if (false == $cruser) {
            $DB->insert_record('course_request_users',
                               array('courserequestid' => $requestid,
                                     'userid'          => $userid,
                                     'roleid'          => $roleid,
                                     'sendemail'       => $sendemail));
        } else if ($sendemail and ! $cruser->sendemail) {
            $cruser->sendemail = true;
            $DB->update_record('course_request_users', $cruser);
        }
    }

    /**
     * Returns user objects. Returns a single object
     * for each user even if user has multiple roles
     * Set $sendemailonly true to include only those that should receive emails
     * related to the course request.
     */
    public function get_requested_users($requestid, $sendemailonly=false) {
        global $DB;

        $whereclause = '';
        if ($sendemailonly) {
            $whereclause = 'where sendemail <> 0';
        }

        $sql =<<<SQL
select u.*
from {user} u
join
(
    select userid, courserequestid
    from {course_request_users}
    $whereclause
    group by userid, courserequestid
) ru on ru.userid=u.id
where ru.courserequestid=:courserequestid
SQL;
        $users = $DB->get_records_sql($sql, array('courserequestid'=>$requestid));
        return $users;
    }

    /**
     * According to the FD, we set passwords on all self and guest
     * enrollment instances, even if the plugin is not configured to
     * require them.
     */
    private function initialize_enrollment_instances($requestid, $courseid) {
        global $DB;

        $enrols = enrol_get_instances($courseid, false);

        $enrollment_methods_found = array();

        foreach ($enrols as $enrol) {
            $enrollment_methods_found[$enrol->enrol] = $enrol->enrol;
            $this->initialize_enrollment_instance($requestid, $enrol);
        }

        // add enrol instances as necessary
        $required_methods = array('manual', 'self', 'guest', 'umnauto');

        $missing = array_diff($required_methods, $enrollment_methods_found);

        if (!empty($missing)) {
            $course = $DB->get_record('course', array('id'=>$courseid));

            foreach ($missing as $required) {
                if ($plugin = enrol_get_plugin($required)) {
                    $enrolid = $plugin->add_default_instance($course);
                    $enrol = $DB->get_record('enrol', array('id'=>$enrolid));
                    $this->initialize_enrollment_instance($requestid, $enrol);
                }
            }
        }
    }

    /**
     * Used to initialize enrollment instance just after creating the course.
     */
    protected function initialize_enrollment_instance($requestid, $instance) {
        global $DB;

        $id = $instance->id;

        switch ($instance->enrol) {
            case 'umnauto':
                $this->setup_enrol_umnauto($requestid, $instance);
                break;
            case 'manual':
                // Enable.
                if ($instance->status !== ENROL_INSTANCE_ENABLED) {
                    $DB->update_record('enrol',
                                       array('id'     => $id,
                                             'status' => ENROL_INSTANCE_ENABLED));
                }
                break;
            case 'guest':
            case 'self':
                // Set password and disable.
                $DB->update_record('enrol',
                                   array('id'       => $id,
                                         'status'   => ENROL_INSTANCE_DISABLED,
                                         'password' => generate_password(20)));
                break;
        }
    }

    /**
     * Sets up umnauto for a new course.
     */
    private function setup_enrol_umnauto($requestid, $instance) {
        global $DB;

        $ppsftclassids = $DB->get_fieldset_select('course_request_classes',
                                                  'ppsftclassid',
                                                  'courserequestid = :requestid',
                                                  array('requestid' => $requestid));

        if (empty($ppsftclassids)) {
            return;
        }

        $umnauto = enrol_get_plugin('umnauto');

        // Add ppsftclassids that are not already there.

        $existing = $umnauto->get_ppsft_classes($instance->id);
        $ppsftclassids = array_diff($ppsftclassids, $existing);

        foreach ($ppsftclassids as $ppsftclassid) {
            $umnauto->add_ppsft_class($instance->id, $ppsftclassid);
        }
    }

    /**
     *
     */
    protected function lookup_categoryid($request) {
        global $DB;

        if (property_exists($request, 'depth3category') and $request->depth3category) {
            $catid = $request->depth3category;
        } else if ($request->depth2category) {
            $catid = $request->depth2category;
        } else if ($request->depth1category) {
            $catid = $request->depth1category;
        } else {
            error_log('No valid category found in insert_course_request');
            throw new Exception('Invalid request state.');
        }

        // Ensure that course requests are allowed for this category.
        if (! $DB->record_exists('course_request_category_map',
                                 array('categoryid' => $catid,
                                       'allowrequest' => 1)))
        {
            throw new Exception("Course requests are not allowed for category id $catid.");
        }

        return $catid;
    }

    /**
     *
     */
    public function create_course($requestid) {
        global $DB, $USER;

        $request = $DB->get_record('course_request_u', array('id'=>$requestid));
        if (!$request) {
            throw new Exception("Invalid course request id: $requestid");
        }

        if (CRS_REQ_STATUS_READY == $request->status
            and is_valid_remote_instance($request->sourcecourseurl))
        {
            $courseid = $this->course_creator->create_from_backup($request);
        } else {
            $courseid = $this->course_creator->create_from_local_course($request);
        }

        $DB->update_record('course_request_u', array('id'       => $requestid,
                                                     'courseid' => $courseid,
                                                     'timemodified' => time(),
                                                     'modifierid'   => $USER->id,
                                                     'status'   => CRS_REQ_STATUS_COMPLETE));

        $this->initialize_enrollment_instances($requestid, $courseid);

        // Set up the requested users (typically, instructors and designers).

        $course_users = $DB->get_records('course_request_users',
                                          array('courserequestid'=>$requestid));

        foreach ($course_users as $course_user) {
            enrol_try_internal_enrol($courseid, $course_user->userid, $course_user->roleid);
        }

        # TODO: Test this call.
        // Delete the backupfile, if any, if it is in the migration repository.
        $this->delete_file_from_migration_dir($request->backupfile);

        // If turnitin is installed and used on this course, reset turnitin.
        $this->reset_turnitin($courseid);

        return $courseid;
    }

    // If turnitin is installed and used on this course, reset turnitin.
    private function reset_turnitin($courseid) {
        global $CFG, $DB;

        $turnitinlibfile = $CFG->dirroot."/mod/turnitintool/lib.php";
        if (file_exists($turnitinlibfile)) {
            if ($DB->count_records('turnitintool', array('course'=>$courseid))) {
                include_once($turnitinlibfile);
                turnitintool_duplicate_recycle($courseid,'NEWCLASS');
            }
        }
    }

    /**
     * If a non-empty filepath is passed and the file is located in the
     * migration repository directory, then delete it. We allow empty
     * in order to avoid putting the condition in each caller.
     */
    private function delete_file_from_migration_dir($filepath) {
        if (!empty($filepath)
            and dirname($filepath) == $this->get_migration_repository_dir())
        {
            if (file_exists($filepath)) {
                unlink($filepath);
            } else {
                debugging("$filepath not found to delete");
            }
        }
    }

    /**
     *
     */
    private function get_migration_repository_dir() {
        global $CFG;
        return "$CFG->dataroot/$CFG->migration_repository_dir";
    }

    /**
     * Adds ppsftclasses to the request.
     */
    public function add_course_ppsft_class($requestid, $ppsftclassid) {
        global $DB;

        # TODO: Should we verify that the ppsftclassid is valid?

        $id = $DB->insert_record('course_request_classes',
                                 array('courserequestid' => $requestid,
                                       'ppsftclassid'    => $ppsftclassid));
        return $id;
    }


    /**
     * Sets fullname, shortname, sections, and callnumbers in $request object.
     */
    # TODO: Good candidate for unit test.
    public function build_course_names_from_ppsft_classes($request, $ppsftclasses) {
        // For some parts of the name, we arbitrarily use values from the first
        // ppsftclass in the array.

        $subj_catnbr_map = array();
        $sections = array();
        $callnumbers = array();

        foreach ($ppsftclasses as $ppsftclass) {
            $sections[]= $ppsftclass->section;
            $callnumbers[] = $ppsftclass->class_nbr;

            // We add the subject and catalog number to $subj_catnbr, unless
            // the subject is already there.  Then add the catalog number.
            // Seems odd, but that's how Elena asked for it.
            if(!isset($subj_catnbr_map[$ppsftclass->subject])){
                #$subj_catnbr_map[$ppsftclass->subject] = $ppsftclass->subject." ".$ppsftclass->catalog_nbr;
                $subj_catnbr_map[$ppsftclass->subject] = array($ppsftclass->catalog_nbr);
            } else {
                $subj_catnbr_map[$ppsftclass->subject][]= $ppsftclass->catalog_nbr;
            }
        }

        $subj_catnbr_string = $this->build_subj_catnbr_string($subj_catnbr_map);

        $first_ppsftclass = array_shift($ppsftclasses);

        $title = $first_ppsftclass->long_title;

        $sections_string = $this->build_sections_string($sections);

        $request->fullname = $subj_catnbr_string . ' ' . $title
                  . ' (sec ' . $sections_string . ') '
                  . ppsft_term_string_from_number($first_ppsftclass->term)
                  . $this->get_campus_fullname_string($first_ppsftclass->institution);

        $request->shortname = $this->build_shortname_from_one_class($first_ppsftclass);

        // Add suffix, if necessary, to keep names unique.
        $this->make_course_names_unique($request);

        // Set sections and callnumbers in matching order and
        // including all dupes (IAW FD).
        $request->sections    = implode(' ', $sections);
        $request->callnumbers = implode(' ', $callnumbers);
    }

    /*
     *
     */
    private function get_campus_fullname_string($campus) {
        global $CFG;

        if ($campus == 'UMNTC') return '';

        return ', '.attempt_mapping($campus, $CFG->campuses);
    }

    /*
     * We don't want to add the catalog number if we already have it, as
     * would be the case for multiple sections of the same class.
     */
    private function build_subj_catnbr_string($subj_catnbr_map) {
        $subj_catnbr_tmp = array();
        foreach ($subj_catnbr_map as $subject => $catnbrs) {
            $catnbrs = array_unique($catnbrs);
            sort($catnbrs);
            $subj_catnbr_tmp[]= $subject . ' ' . implode('/', $catnbrs);
        }
        sort($subj_catnbr_tmp);
        return implode('/', $subj_catnbr_tmp);
    }

    /*
     *
     */
    private function build_sections_string($sections) {
        $sections = array_unique($sections);
        sort($sections);
        return implode(', ', $sections);
    }

    /*
     * Use the ppsftclass for the term part of the string, but the request
     * for the campus part of the string.
     */
    private static function build_shortname_from_one_class($ppsftclass) {

        $short_campus = ($ppsftclass->institution == 'UMNTC')
                        ? ''
                        : substr($ppsftclass->institution, 3, 1);

        return   $ppsftclass->subject
               . $ppsftclass->catalog_nbr . '_'
               . $ppsftclass->section
               . ppsft_term_string_from_number($ppsftclass->term, true)
               . $short_campus;
    }

    /**
     * Adds a suffix as neccessary to ensure unique shortname and
     * long name.
     * Currently use only for names derived from associated ppsft
     * classes.  Others use form validation and throw error.
     */
    protected function make_course_names_unique($request) {

        $suffix_index  = $this->get_next_course_name_index($request->shortname,
                                                           $request->fullname);
        if ($suffix_index > 1) {
            $request->fullname .= "_$suffix_index";
            $request->shortname .= "_$suffix_index";
        }
    }

    /*
     * The parameters to this function are candidate course names without
     * an index.  The function looks for any matching existing in the
     * pending requests or the courses and returns the highest index
     * in use.
     */
    private function get_next_course_name_index($shortname, $fullname) {
        global $DB;

        # TODO: This whole function should have an automated unit test
        #       written for it.

        // Using '|' as the SQL LIKE escape character because the default
        // backslash gets escaped by mysql's real_escape_string later, which
        // prevents the backslash from getting into the final SQL as intended.

        // First escape any existing '|' characters.
        $shortpattern = str_replace('|', '||', $shortname);
        $fullpattern = str_replace('|', '||', $fullname);

        // Escape SQL LIKE wildcard characters ('_', '%') with '|'.
        $shortpattern = str_replace('_', '|_', str_replace('%', '|%', $shortpattern)) . '%';
        $fullpattern = str_replace('_', '|_', str_replace('%', '|%', $fullpattern)) . '%';

        $sql =<<<SQL
  (select shortname, fullname from {course_request_u}
   where shortname like :requestshortname escape '|' or
      fullname like :requestfullname escape '|')
union all
  (select shortname, fullname from {course}
   where shortname like :courseshortname escape '|' or
      fullname like :coursefullname escape '|')
SQL;

        $rs = $DB->get_recordset_sql($sql,
                                     array('requestshortname'=>$shortpattern,
                                           'requestfullname'=>$fullpattern,
                                           'courseshortname'=>$shortpattern,
                                           'coursefullname'=>$fullpattern));

        $max = 0;

        $short_re_quoted = preg_quote($shortname, '/');
        $full_re_quoted = preg_quote($fullname, '/');

        foreach ($rs as $record) {
            // For our purposes, no suffix is the same as "_1", so if we have any
            // matches, we must set the max to 1.
            if ($max == 0) {
                $max = 1;
            }

            if (preg_match("/^{$short_re_quoted}_([\d]+)$/", $record->shortname, $matches)) {
                $max = max($max, $matches[1]);
            }
            unset($matches);

            if (preg_match("/^{$full_re_quoted}_([\d]+)$/", $record->fullname, $matches)) {
                $max = max($max, $matches[1]);
            }
        }
        $rs->close();
        return $max + 1;
    }

    /**
     *
     */
    public function cancel($request) {
        global $DB;

        if (is_numeric($request)) {
            $request = $DB->get_record('course_request_u', array('id' => $request));
        }

        // Delete request if in migrating or ready status.
        if (   CRS_REQ_STATUS_MIGRATING == $request->status
            or CRS_REQ_STATUS_READY     == $request->status)
        {
            $this->course_xfer_client->delete_request($request->id);
        }

        # TODO: Test this call.
        // Delete the backupfile, if any, if it is in the migration repository.
        $this->delete_file_from_migration_dir($request->backupfile);

        $this->set_request_status($request, CRS_REQ_STATUS_CANCELED);
    }

    public function build_assigned_roles_email_string($request) {
        global $DB;

        $sql =<<<SQL
select ru.id as requestuserid, ru.userid, r.*, u.username
from mdl_course_request_users ru
  join mdl_user u on u.id=ru.userid
  join mdl_role r on r.id=ru.roleid
where ru.courserequestid=:courserequestid
order by sortorder
SQL;
        $userroles = $DB->get_records_sql($sql, array('courserequestid'=>$request->id));

        $byrole = array();

        foreach ($userroles as $userrole) {
            $byrole[$userrole->id][] = $userrole;
        }

        $roleuserlists = array();
        foreach ($byrole as $roleid => $rolegroup) {
            $rolename = role_get_name($rolegroup[0]);
            $usernames = array_map(function ($ru) {
                                      $nameparts = explode('@', $ru->username);
                                      return reset($nameparts);
                                   },
                                   $rolegroup);
            $usersstring = implode(', ', $usernames);
            $roleuserlists[] = get_string('roleuserlist',
                                          'local_course',
                                          array('rolename'=>$rolename, 'userlist'=>$usersstring));
        }
        return implode(get_string('roleuserlistdelim', 'local_course'), $roleuserlists);
    }

    /**
     * Sends notification from admin to some users notifying of request submission.
     */
    public function notify_course_requested($request) {
        global $DB, $CFG;

        if (is_numeric($request)) {
            $request = $DB->get_record('course_request_u', array('id' => $request));
        }
        $request->category_string = local_course_build_category_string($request->categoryid);

        $requester = $DB->get_record('user', array('id' => $request->requesterid));
        $request->requester_username = $requester->username;
        $request->requester_name = fullname($requester);

        $request->assignedroles = $this->build_assigned_roles_email_string($request);

        $message_type_name = 'courserequested';
        $subject = get_string('courserequestnotifyemailsubject', 'local_course');
        $message = get_string('courserequestnotifyemail', 'local_course', $request);

        $adminusers = get_users_from_config($CFG->courserequestnotify,
                                            'moodle/site:approvecourse');

        $courseuserstoemail = $this->get_requested_users($request->id, true);

        $users = $adminusers + $courseuserstoemail + array($requester->id => $requester);

        foreach ($users as $user) {
            $this->send_course_request_notification($user,
                                                    $message_type_name,
                                                    $subject,
                                                    $message);
        }
    }

    /**
     * The body of the message consists of a user-specific first part and
     * a common second part.  The second part is the commonmessage parameter.
     * If silent, this method prefixes the message with another part.
     * If called with a request object instead of the requestid, ensure that
     * the request contains the latest updates, especially the courseid.
     */
    public function notify_course_approved($request, $commonmessage, $silent, $emailtestonly=false) {
        global $DB, $CFG, $USER;

        if (is_numeric($request)) {
            $request = $DB->get_record('course_request_u', array('id' => $request));
        }
        $requester = $DB->get_record('user', array('id' => $request->requesterid));

        $message_type_name = 'courserequestapproved';
        $subject = get_string('courseapprovedemail_subject', 'local_course');

        // Get the users set up in admin/settings.php?section=courserequest
        $users = get_users_from_config($CFG->courserequestnotify, 'moodle/site:approvecourse');

        $message_init = '';

        if ( $silent) {
            $message_init .= get_string('courseapprovedemail_silent', 'local_course');
        } else {
            $courseuserstoemail = $this->get_requested_users($request->id, true);
            $users = $users + $courseuserstoemail + array($requester->id => $requester);
        }

        $msgdata = new stdClass();
        $msgdata->requester = $requester->username;
        $msgdata->url = "$CFG->wwwroot/course/view.php?id=$request->courseid";
        $msgdata->courseshortname = $request->shortname;

        // The URL for the new course would not have been available when the $commonmessage
        // was first build, so substitute it now if it appears.
        $commonmessage = str_replace('{$a->url}', $msgdata->url, $commonmessage);

        foreach ($users as $user) {
            $message = $message_init;

            $msgdata->userfullname = fullname($user);
            $msgdata->username = $user->username;
            $message .= get_string('courseapprovedemail_user', 'local_course', $msgdata);
            $message .= "\n\n".$commonmessage;

            // If we are only testing emails, then replace each user with the current user
            // so that the current user receives every email that would otherwise go to others.
            if ($emailtestonly) {
                $user = $USER;
            }

            $this->send_course_request_notification($user,
                                                    $message_type_name,
                                                    $subject,
                                                    $message);
        }
    }

    /**
     * The body of the message consists of a user-specific first part and
     * a common second part.  The second part is the commonmessage parameter.
     * If silent, this method prefixes the message with another part.
     */
    public function notify_course_rejected($request, $commonmessage, $silent, $emailtestonly=false) {
        global $DB, $CFG, $USER;

        if (is_numeric($request)) {
            $request = $DB->get_record('course_request_u', array('id' => $request));
        }

        $message_type_name = 'courserequestrejected';
        $subject = get_string('courserejectedemail_subject', 'local_course');

        // Get the users set up in admin/settings.php?section=courserequest
        $users = get_users_from_config($CFG->courserequestnotify, 'moodle/site:approvecourse');

        $message_init = '';

        if ( $silent) {
            $message_init .= get_string('courserejectedemail_silent', 'local_course');
        } else {
            $courseuserstoemail = $this->get_requested_users($request->id, true);
            $requester = $DB->get_record('user', array('id' => $request->requesterid));
            $users = $users + $courseuserstoemail + array($requester->id => $requester);
        }

        $msgdata = new stdClass();
        $msgdata->courseshortname = $request->shortname;
        $msgdata->coursefullname = $request->fullname;
        $msgdata->reason = $commonmessage;

        foreach ($users as $user) {
            $message = $message_init;

            $msgdata->userfullname = fullname($user);
            $message .= get_string('courserejectedemail_user', 'local_course', $msgdata);

            // If we are only testing emails, then replace each user with the current user
            // so that the current user receives every email that would otherwise go to others.
            if ($emailtestonly) {
                $user = $USER;
            }

            $this->send_course_request_notification($user,
                                                    $message_type_name,
                                                    $subject,
                                                    $message);
        }
    }

    /**
     *
     */
    private function send_course_request_notification($addressee,
                                                      $message_type_name,
                                                      $subject,
                                                      $message)
    {
        $eventdata = new stdClass();
        $eventdata->component         = 'moodle';
        $eventdata->name              = $message_type_name;
        $eventdata->userfrom          = $this->get_request_email_sender();
        $eventdata->subject           = $subject;
        $eventdata->fullmessage       = $message;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = '';
        $eventdata->notification      = 1;
        $eventdata->userto = $addressee;
        message_send($eventdata);
    }

    /**
     *
     */
    private function get_request_email_sender() {
        global $CFG, $DB;

        $username = $CFG->courserequestemailsender;

        if (empty($username)) {
            error_log("courserequestemailsender not configured");
        } else {
            if (! $sender = $DB->get_record('user', array('username'=>$username))) {
                error_log("courserequestemailsender is not set to a valid Moodle username: $username");
            }
        }
        return $sender ?: get_admin();
    }

}




