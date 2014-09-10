<?php

/**
 * Updates all class instructors.  Requires no input.
 * Can take a while to run.
 */

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');

if (CLI_MAINTENANCE) {
    echo "CLI maintenance mode active; this script is disabled.\n";
    exit(1);
}

$ppsft_updater = ppsft_get_updater();
$ppsft_updater->update_all_class_instructors();

echo "Updated all class instructors.\n";

