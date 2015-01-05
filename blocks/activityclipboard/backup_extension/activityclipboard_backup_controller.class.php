<?php

class activityclipboard_backup_controller extends backup_controller {

    protected function load_plan() {
        $this->log('loading plan for activityclipboard_backup_controller', backup::LOG_DEBUG);

        // Use custom backup_plan class.
        $this->plan = new activityclipboard_backup_plan($this);

        $this->plan->build(); // Build plan for this controller
        $this->set_status(backup::STATUS_PLANNED);
    }

}

