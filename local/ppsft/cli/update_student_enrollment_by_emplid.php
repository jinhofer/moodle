<?php

/**
 * Takes emplids on stdin delimited by new lines.
 *
 * Example: echo 4386022 | /swadm/usr/local/php/bin/php update_student_enrollment_by_emplid.php
 */

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');
require_once(__DIR__.'/../ppsft_data_updater.class.php');
require_once(__DIR__.'/../ppsft_data_adapter.class.php');
require_once($CFG->dirroot.'/local/user/lib.php');

$USER = get_admin();

$ppsft_updater = ppsft_get_updater();

while ($emplid = rtrim(fgets(STDIN, 32))) {
    $ppsft_updater->update_student_enrollment($emplid);
    echo "Updated ppsft enrollment for $emplid\n";
}


echo "done\n";

