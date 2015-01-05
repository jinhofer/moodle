<?php

class activityclipboard_backup_plan extends backup_plan {

    public function build() {
        // See backup/moodle2/backup_plan_builder.class.php for where we get
        // the guts of this.

        $controller_format = $this->controller->get_format();
        $controller_id     = $this->controller->get_id();

        // Use same root task as orginal. (See backup_plan_builder.class.php.)
        $this->add_task(new backup_root_task('root_task'));

        // backup_plan_builder dispatches to activity, section, or course
        // function, but we know we want activity.
        $this->add_task(backup_factory::get_backup_activity_task($controller_format,
                                                                 $controller_id));

        $blockids = backup_plan_dbops::get_blockids_from_moduleid($controller_id);
        foreach ($blockids as $blockid) {
            $this->add_task(backup_factory::get_backup_block_task($controller_format,
                                                                  $blockid,
                                                                  $controller_id));
        }

        // Call custom task. This task must find course files that need to be included
        // in the backup.
        $this->add_task(new activityclipboard_backup_task('activityclipboard_backup_task'));

        $this->add_task(new backup_final_task('final_task'));

        $this->built = true;
    }

}
