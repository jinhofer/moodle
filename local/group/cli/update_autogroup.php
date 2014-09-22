<?php

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot .'/local/group/peoplesoft_autogroup.php');

if (CLI_MAINTENANCE) {
    echo "CLI maintenance mode active; this script is disabled.\n";
    exit(1);
}

//============== MAIN ROUTINE =============

$start_stamp = microtime();

// create an instance
$autogrouper = new peoplesoft_autogroup();


echo "\nStart updating PeopleSoft-based groups ... ";

// get the list of course IDs that have auto-update selected
$auto_courses = $DB->get_records('enrol_umnauto_course', array('auto_group' => '1'));

$course_ids = array();
foreach ($auto_courses as $id => $record) {
    $course_ids[] = $record->courseid;
}

unset($auto_courses);     // release memory

echo "\n", count($course_ids), ' course(s) to be updated';

$result = $autogrouper->run($course_ids);

// redirect errors to STDERR
if (count($result['errors']) > 0) {
    $stderr = fopen('php://stderr', 'w+');

    foreach ($result['errors'] as $course_id => $msgs) {
        fwrite($stderr, "\nCourse {$course_id}:" . implode("\n", $msgs));
    }

    fclose($stderr);
}

// print out summary stats
echo "\nRun stats: ";
print_r($autogrouper->get_stats());

echo "\n\nTime spent: ", microtime_diff($start_stamp, microtime());
echo "\nMemory peak usage: ", memory_get_peak_usage(true), "\n";
