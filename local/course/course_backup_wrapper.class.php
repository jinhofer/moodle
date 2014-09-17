<?php

class course_backup_wrapper {

    private $backup_controller_factory;

    public function __construct($backup_controller_factory) {
        $this->backup_controller_factory = $backup_controller_factory;
    }

    /**
     * Using backup::MODE_IMPORT because intending to immediately restore.
     * When using MODE_IMPORT caller should delete the temp directory as
     * in the call to fulldelete($tempdestination) in backup/import.php.
     *
     * Returns the backupid, which is also the name of the temp directory
     * in moodledata under temp/backup/
     */
    public function backup_course_to_temp_dir($courseid, $include_user_data=false) {

        $backup_controller = $this->backup_controller_factory
                                  ->create_backup_controller($courseid,
                                                             $include_user_data,
                                                             backup::MODE_IMPORT);

        # TODO: See other settings in backup/moodle2/backup_settingslib.php.

        $backup_controller->execute_plan();

        return $backup_controller->get_backupid();
    }

    /**
     * Returns a Moodle file object.
     */
    public function backup_course_to_file($courseid, $include_user_data=false) {

        $backup_controller = $this->backup_controller_factory
                                  ->create_backup_controller($courseid,
                                                             $include_user_data,
                                                             backup::MODE_GENERAL);
        // Set filename
        $format = $backup_controller->get_format();
        $type = $backup_controller->get_type();
        $id = $backup_controller->get_id();
        $users = $backup_controller->get_plan()->get_setting('users')->get_value();
        $anonymized = $backup_controller->get_plan()->get_setting('anonymize')->get_value();
        $filename = backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymized);
        $backup_controller->get_plan()->get_setting('filename')->set_value($filename);

        // If the status is not set as follows, the filename gets
        // reset to backup.mbz.
        $backup_controller->set_status(backup::STATUS_AWAITING);

        $backup_controller->execute_plan();

        $backup = $backup_controller->get_results();

        $backupfile = $backup['backup_destination'];

        return $backupfile;

        #$success = $backupfile->copy_content_to("$destinationdir/$filename");
        #if ($success) {
        #    $backupfile->delete();
        #} else {
        #    # TODO: THROW EXCEPTION?
        #}
    }
}

