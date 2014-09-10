<?php

/**
 * Updates all class enrollments.  Requires no input.
 * Can take a while to run.
 */

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');
require_once(__DIR__.'/../ppsft_data_updater.class.php');
require_once(__DIR__.'/../ppsft_data_adapter.class.php');

if (CLI_MAINTENANCE) {
    echo "CLI maintenance mode active; this script is disabled.\n";
    exit(1);
}

$USER = get_admin();

$ppsft_updater = ppsft_get_updater();
$ppsft_updater->update_all_class_enrollments();

echo "Updated all class enrollments.\n";

$ppsft_updater->sync_vanished_enrollments();

echo "Synchronized vanished enrollments.\n";

