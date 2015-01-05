<?php
$plugin->version      = 2015010200;   // The (date) version of this plugin
$plugin->requires     = 2014111001;   // Requires this Moodle version
$plugin->component    = 'local_moodlekiosk';
$plugin->dependencies = array(
    'local_myu' => ANY_VERSION   // Uses the skip_portal flag in the mdl_myu_course table.
);
