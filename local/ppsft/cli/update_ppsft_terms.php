<?php

/**
 * Updates the ppsft_terms.  Requires no input.
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
$ppsft_updater->update_terms();

echo "Updated ppsft terms.\n";

echo "done\n";

