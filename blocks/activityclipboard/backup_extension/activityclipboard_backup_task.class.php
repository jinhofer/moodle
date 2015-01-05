<?php

class activityclipboard_backup_task extends backup_task {

    /**
     * Create all the steps that will be part of this task
     */
    public function build() {

        $this->add_step(new activityclipboard_backup_coursefiles_step('activityclipboard_backup_coursefiles_step'));

        $this->built = true;
    }

    protected function define_settings() {
    }
}

