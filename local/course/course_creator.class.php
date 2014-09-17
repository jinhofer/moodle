<?php

# TODO: Look at putting this in local/course/lib.php.

class course_creator {

    private $course_backup_wrapper;

    public function set_course_backup_wrapper($course_backup_wrapper) {
        $this->course_backup_wrapper = $course_backup_wrapper;
    }

    public function set_course_restore_wrapper($course_restore_wrapper) {
        $this->course_restore_wrapper = $course_restore_wrapper;
    }

    /**
     * Returns the source course id parsed from the parameter URL. If
     * parameter is not set, uses the default setting. If the parameter
     * is set, but does not point to this instance, throws
     * exception.
     */
    private function find_local_sourcecourseid($sourcecourseurl) {
        global $CFG;

        if ($sourcecourseurl) {
            $sourcecourseid = local_course_parse_courseurl_courseid(
                                        $sourcecourseurl,
                                        local_course_this_instancename());
        } else {
            // Use the configured defaultsourcecourseid if the requester
            // did not include a sourcecourseurl.
            $sourcecourseid = $CFG->defaultsourcecourseid;
        }
        return $sourcecourseid;
    }

    /**
     *
     */
    public function create_from_backup($request) {
        global $CFG, $DB;

        // Get backup file path.
        $backupfilepath = $request->backupfile;

        // Ensure that names do not match existing course names.
        list($request->fullname, $request->shortname) 
                        = restore_dbops::calculate_course_names(0,
                                                                $request->fullname,
                                                                $request->shortname);

        $courseid = restore_dbops::create_new_course($request->fullname,
                                                     $request->shortname,
                                                     $request->categoryid);

        // Execute the restore
        $this->course_restore_wrapper->restore_course_from_backup($courseid,
                                                                  $backupfilepath,
                                                                  $request->copyuserdata);

        // We must update the course again, especially the names, because restore will have 
        // overwritten them.
        $this->adjust_course_settings($courseid, $request);

        # TODO: should delete the tempdir here, or does restore already do that?

        return $courseid;
    }

    /**
     * Returns the id of the new course.
     * This generates a course_updated event but not a course_created event.
     * Not sure whether that is good or not given that these are created by
     * restoring from backup.
     */
    public function create_from_local_course($request) {
        if ($request->copyuserdata) {
            $courseid = $this->create_from_local_course_userdata($request);
        } else {
            $courseid = $this->create_from_local_course_no_userdata($request);
        }
        return $courseid;
    }

    /**
     * If we need user data, we cannot use a MODE_IMPORT backup, so we
     * must zip the backup and later unzip it.
     */
    private function create_from_local_course_userdata($request) {
        global $CFG, $USER;
        
        raise_memory_limit(MEMORY_EXTRA);
        set_time_limit(0);

        // Backup to file (in Moodle file storage).

        $sourcecourseid = local_course_parse_courseurl_courseid(
                            $request->sourcecourseurl,
                            local_course_this_instancename());

        // Although this method is intended for user data request, we don't
        // need to restrict it to that in the implementation.
        $include_user_data = $request->copyuserdata;

        $backupfile = $this->course_backup_wrapper->backup_course_to_file(
                                                    $sourcecourseid,
                                                    $include_user_data);

        // Create the new course.

        list($request->fullname, $request->shortname) 
                        = restore_dbops::calculate_course_names(0,
                                                                $request->fullname,
                                                                $request->shortname);

        $newcourseid = restore_dbops::create_new_course($request->fullname,
                                                        $request->shortname,
                                                        $request->categoryid);

        // Unzip to directory and delete the file.

        $tempdirname = restore_controller::get_tempdir_name($newcourseid, $USER->id);
        $tempdirpath = "$CFG->dataroot/temp/backup/$tempdirname";

        $file_packer = get_file_packer('application/vnd.moodle.backup');
        $unpacked = $backupfile->extract_to_pathname($file_packer, $tempdirpath);
        if (!$unpacked) {
            throw new Exception("extract_to_pathname of "
                                .$backupfile->get_contenthash()
                                ." to $tempdirpath failed");
        }

        // Restore from the directory.
        $this->course_restore_wrapper->restore_course_from_temp_dir(
                                         $newcourseid,
                                         $tempdirname,
                                         $include_user_data);

        return $newcourseid;
    }

    /**
     * If we do not need user data, we can backup the course to a temp directory
     * and leave it there for the restore operation instead of zipping and
     * later unzipping. This requires a MODE_IMPORT backup, which does not
     * allow user data.
     */
    private function create_from_local_course_no_userdata($request) {
        global $CFG;

        if ($request->sourcecourseurl) {
            $sourcecourseid = $this->find_local_sourcecourseid($request->sourcecourseurl);
        } else {
            $sourcecourseid = get_category_course_template_id($request->categoryid);
        }

        $backupid = $this->course_backup_wrapper->backup_course_to_temp_dir($sourcecourseid,
                                                                            $request->copyuserdata);

        $tempdirname = "$CFG->dataroot/temp/backup/$backupid";
        #echo "\n$tempdirname\n";

        // Ensure that names do not match existing course names.
        list($request->fullname, $request->shortname)
                        = restore_dbops::calculate_course_names(0,
                                                                $request->fullname,
                                                                $request->shortname);

        $courseid = restore_dbops::create_new_course($request->fullname,
                                                     $request->shortname,
                                                     $request->categoryid);

        $this->course_restore_wrapper->restore_course_from_temp_dir($courseid,
                                                                    $backupid,
                                                                    $request->copyuserdata);

        $this->adjust_course_settings($courseid, $request);

        # TODO: should delete the tempdir here, or does restore already do that?

        return $courseid;
    }

    private function adjust_course_settings($courseid, $request) {
        global $DB;

        $course = $DB->get_record('course', array('id' => $courseid));

        // Some settings we take from the request.
        $course->fullname      = $request->fullname;
        $course->shortname     = $request->shortname;
        $course->categoryid    = $request->categoryid;

        // And some settings we take from the server course default settings.
        // legacyfiles might not be configured depending on whether
        // legacyfilesinnewcourses has been set.
        $courseconfig = get_config('moodlecourse');
        $course->maxbytes    = $courseconfig->maxbytes;
        $course->legacyfiles = isset($courseconfig->legacyfiles) ? $courseconfig->legacyfiles : 0;
        $course->lang        = $courseconfig->lang;
        $course->visible     = $courseconfig->visible;

        if ($theme = get_category_course_theme($request->categoryid)) {
            $course->theme = $theme;
        }

        // And some settings we simply force.
        $course->idnumber = '';

        // update_course is in course/lib.php.
        update_course($course);
    }

}

