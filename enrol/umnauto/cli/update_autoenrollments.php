<?php

/**
 * Updates autoenrollments.  Requires no input.
 * Can take a while to run.
 */

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');

if (CLI_MAINTENANCE) {
    echo "CLI maintenance mode active; this script is disabled.\n";
    exit(1);
}

enrol_umnauto_sync();

echo "Updated autoenrollments.\n";

