<?php
class activityclipboard_restore_root_task extends restore_root_task {

    public function get_newsectionid() {
        return $this->plan->get_newsectionid();
    }

    public function get_oldsectionid() {
        return $this->plan->get_oldsectionid();
    }

    public function build() {

        parent::build();

        // We need to replace the base's restore_load_included_files
        // with our own.

        $found = false;

        $stepcount = count($this->steps);
        for ($i = 0; $i < $stepcount; $i++) {
            if ($this->steps[$i] instanceof restore_load_included_files) {

                // Maintenance consideration: If add_step changes to do more,
                // we might need to adjust this, also.

                $this->steps[$i]
                            = new activityclipboard_restore_load_included_files(
                                    'load_file_records', 'files.xml');
                $this->steps[$i]->set_task($this);

                $found = true;
                break;
            }
        }

        if (! $found) {
            throw new Exception('Did not find restore_load_included_files step');
        }

        $this->add_step(new activityclipboard_set_sectionid_step('activityclipboard_set_sectionid'));

        $this->built = true;
    }
}

// Overriding restore_load_included_files because it does not restore any
// legacy course files that are included in the backup for an activity restore.
class activityclipboard_restore_load_included_files extends restore_load_included_files {

    public function process_file($data) {
        // Same implementation as base except that we don't filter the files.

        $data = (object)$data;

        restore_dbops::set_backup_files_record($this->get_restoreid(), $data);
    }
}

class activityclipboard_set_sectionid_step extends restore_execution_step {

    // Need to pass in new sectionid and old section and then call set_mapping.

    protected function define_execution() {
        $newsectionid = $this->task->get_newsectionid();
        $oldsectionid = $this->task->get_oldsectionid();
        restore_dbops::set_backup_ids_record($this->get_restoreid(),
                                             'course_section',
                                             $oldsectionid,
                                             $newsectionid);
    }
}

