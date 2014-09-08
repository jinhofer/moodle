<?php

require_once($CFG->dirroot.'/local/instances/lib.php');

function xmldb_local_instances_install() {
    add_this_moodle_instance();
}
