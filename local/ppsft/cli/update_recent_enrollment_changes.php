<?php

/**
 * Example: /swadm/usr/local/php/bin/php update_recent_enrollment_changes.php
 */

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');

if (CLI_MAINTENANCE) {
    echo "CLI maintenance mode active; this script is disabled.\n";
    exit(1);
}

$ppsft_updater = ppsft_get_updater();

$ppsft_updater->update_recent_ppsft_enrollment_changes();

#$emplids_to_update = $ppsft_updater->get_recent_ppsft_enrollment_change_emplids();

#foreach ($emplids_to_update as $emplid) {
#    $ppsft_updater->update_student_enrollment($emplid);
#    echo "Updated ppsft enrollment for $emplid\n";
#}


