<?php

class activityclipboard_restore_plan extends restore_plan {

    public function get_newsectionid() {
        return $this->controller->get_newsectionid();
    }

    private function get_activityinfo() {
        $activityid   = key($this->controller->get_info()->activities);
        $info         = $this->controller->get_info();
        return $info->activities[$activityid];
    }

    public function get_oldsectionid() {
        $activityinfo = $this->get_activityinfo();
        return $activityinfo->sectionid;
    }

    public function build() {
        // See backup/moodle2/restore_plan_builder.class.php for where we get
        // the guts of this.

        $activityinfo = $this->get_activityinfo();

        // Colin. We need a customized root_task in order to get all files
        //        into the file pool.
        $this->add_task(new activityclipboard_restore_root_task('root_task'));

        // restore_plan_builder dispatches to activity, section, or course
        // function, but we know we want activity.
        if ($task = restore_factory::get_restore_activity_task($activityinfo)) {
            $this->add_task($task);

            $blocks = backup_general_helper::get_blocks_from_path($task->get_taskbasepath());
            foreach ($blocks as $basepath => $name) {
                if ($task = restore_factory::get_restore_block_task($name, $basepath)) {
                    $this->add_task($task);
                } else {
                    // Colin: Below TODO from original in restore_plan_builder.
                    // TODO: Debug information about block not supported
                }
            }
        } else { // Activity is missing in target site, inform plan about that
            $this->set_missing_modules();
        }

        // Colin.  This is where we invoke our custom functionality.
        $this->add_task(new activityclipboard_restore_task('activityclipboard_restore_task'));

        $this->add_task(new restore_final_task('final_task'));

        $this->built = true;
    }
}
