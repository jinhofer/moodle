<?php

class activityclipboard_restore_coursefiles_step extends restore_execution_step {

    protected function define_execution() {
        $this->add_course_files();
    }


    // Copied from backup/util/plan/restore_structure_step.class.php add_related_files and modified.
    public function add_course_files() {
        $component = 'course';
        $filearea  = 'legacy';
        $filesctxid = $this->task->get_info()->original_course_contextid;

        #error_log("old course context id = $filesctxid");
        #$ids = $DB->get_records('backup_ids_temp');
        #error_log(print_r($ids, true));
        #$files = $DB->get_records('backup_files_temp');
        #error_log('FILES: ' . var_export($files, true));

        restore_dbops::send_files_to_pool($this->get_basepath(),
                                          $this->get_restoreid(),
                                          $component,
                                          $filearea,
                                          $filesctxid,
                                          $this->task->get_userid());
    }

}

