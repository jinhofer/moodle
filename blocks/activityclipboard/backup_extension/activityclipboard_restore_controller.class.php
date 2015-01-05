<?php

class activityclipboard_restore_controller extends restore_controller {

    private $newsectionid;

    public function set_newsectionid($newsectionid) {
        $this->newsectionid = $newsectionid;
    }

    public function get_newsectionid() {
        return $this->newsectionid;
    }

    protected function load_plan() {

        // The reason for overriding this method is to be able to
        // use our own custom restore_plan.  Otherwise, this method is copied
        // entirely from backup/controller/restore_controller.class.php.
        // See original for comments.

        $this->log('loading backup info', backup::LOG_DEBUG);
        $this->info = backup_general_helper::get_backup_information($this->tempdir);

        $this->type = $this->info->type;

        $this->samesite = backup_general_helper::backup_is_samesite($this->info);

        $this->log('loading plan for activityclipboard_restore_controller', backup::LOG_DEBUG);

        // Use custom restore_plan class.
        $this->plan = new activityclipboard_restore_plan($this);
        $this->plan->build();

        $this->set_status(backup::STATUS_PLANNED);
    }

}

