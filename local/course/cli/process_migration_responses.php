<?php

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');

if (CLI_MAINTENANCE) {
    echo "CLI maintenance mode active; this script is disabled.\n";
    exit(1);
}

#$USER = get_admin();

$course_request_manager = get_course_request_manager();

$course_request_manager->check_request_file_statuses();


$course_request_manager->process_migration_responses();

echo "done\n";

