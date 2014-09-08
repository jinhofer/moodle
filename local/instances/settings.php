<?php

if ($hassiteconfig) {
    $ADMIN->add('localplugins',
                new admin_externalpage('manage_moodle_instances',
                get_string('configinstanceslist', 'local_instances'),
                $CFG->wwwroot.'/local/instances/list.php'));
}

