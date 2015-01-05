<?php

class activityclipboard_restore_task extends restore_task {

    /**
     * Create all the steps that will be part of this task
     */
    public function build() {

        $this->add_step(new activityclipboard_restore_coursefiles_step('activityclipboard_restore_coursefiles_step'));

        $this->built = true;
    }

    protected function define_settings() {
    }
}

