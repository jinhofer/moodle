<?php

/**
 * This script recursively deletes categories and their
 * courses. It requires that the categories be prepared
 * beforehand by prefixing their names with "DELETE RECURSIVELY"
 * and hiding them. Set up course_deleted event handler in
 * local/course/lib.php and local/course/db/events.php
 * to log course deletions to the error log for monitoring.
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->dirroot.'/course/lib.php');

// now get cli options
list($options, $unrecognized) = cli_get_params(array('help'=>false, 'execute'=>false),
                                               array('h'=>'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
"Bulk course deletions. Deletes categories recursively
whose names start with 'DELETE RECURSIVELY'
Options:
--execute

Example:
$ php local/course/bulk_delete.php

";

    echo $help;
    die;
}

cron_setup_user();

$categories_to_delete = $DB->get_records_select('course_categories',
                                                "name like 'DELETE RECURSIVELY%' and visible=0");

echo "The following categories are set up to be recursively deleted:\n";
foreach ($categories_to_delete as $category) {
    echo "\t$category->name\n";
}

if (!$options['execute']) {
    echo "Not actually deleting because --execute not set.\n";
    die;
}


$prompt = "Do you want to proceed? (NO/yes)";
$proceed = cli_input($prompt);
if ($proceed !== 'yes') {
    echo "Bulk delete canceled\n";
    die;
}

foreach ($categories_to_delete as $category) {
    echo "Deleting $category->name...\n";
    category_delete_full($category, false);
}
