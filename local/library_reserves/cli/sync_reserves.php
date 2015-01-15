<?php

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once("{$CFG->dirroot}/local/library_reserves/lib.php");

if (CLI_MAINTENANCE) {
    echo "CLI maintenance mode active; this script is disabled.\n";
    exit(1);
}

$log_msgs = array();



//============== MAIN ROUTINE =============

// create an instance
$syncer = new library_reserves_syncer();
$syncer->sync();
