<?php

class course_restore_wrapper {

    private $restore_controller_factory;

    /**
     *
     */
    public function __construct($restore_controller_factory) {
        $this->restore_controller_factory = $restore_controller_factory;
    }

    /**
     *
     */
    public function restore_course_from_backup($courseid, $backupfilepath, $include_user_data) {
        global $CFG, $USER;

        if (! is_file($backupfilepath)) {
            throw new Exception("Not a file: $backupfilepath");
        }

        raise_memory_limit(MEMORY_EXTRA);
        set_time_limit(0);

        $tempdirname = restore_controller::get_tempdir_name($courseid, $USER->id);
        $tempdirpath = "$CFG->dataroot/temp/backup/$tempdirname";
        $file_packer = get_file_packer('application/vnd.moodle.backup');
        $unpacked = $file_packer->extract_to_pathname($backupfilepath, $tempdirpath);
        if (!$unpacked) {
            throw new Exception("extract_to_pathname($backupfilepath, $tempdirpath) failed");
        }

        error_log("restore_course_from_backup unzipping archive to $tempdirpath");

        $this->restore_course_from_temp_dir($courseid, $tempdirname, $include_user_data);
    }

    /**
     *
     */
    public function restore_course_from_temp_dir($courseid, $tempdirname, $include_user_data) {

        $restore_controller = $this->restore_controller_factory
                                   ->create_restore_controller($courseid,
                                                               $tempdirname,
                                                               $include_user_data);

        $restore_controller->execute_precheck();
        $precheck_results = $restore_controller->get_precheck_results();

        if (!empty($precheck_results)) {
            // Formatting for standard Moodle exception display.
            if (array_key_exists('errors', $precheck_results)) {
                $message =  'Precheck errors: <br />'
                           .implode('<br />', $precheck_results['errors'])
                           .'<br />';
            }
            if (array_key_exists('warnings', $precheck_results)) {
                $message =  'Precheck warnings: <br />'
                           .implode('<br />', $precheck_results['warnings']);
            }
            if (isset($message)) {
                throw new Exception($message);
            } else {
                // Might not be possible to get here.
                error_log(print_r($precheck_results, true));
            }
        }

        #$users = $restore_controller->get_plan()->get_setting('users')->get_value();

        $restore_controller->execute_plan();

        $restore_controller->destroy();
    }
}

