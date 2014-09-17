<?php

/**
 * A wrapper around this function in lib/modinfolib.php:
 *
 *      function rebuild_course_cache($courseid=0, $clearonly=false)
 *
 * If the courseid argument is zero in the function call, all course
 * caches get rebuilt.  For this script, setting --all sets that
 * courseid argument to zero.
 *
 * A couple of the options do not use the rebuild_course_cache function.
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(array('help'      => false,
                                                     'all'       => false,
                                                     'course'    => 0,
                                                     'clearonly' => false,
                                                     'checkonly'  => false,
                                                     'resetmemorycache' => false),
                                               array('h' => 'help'));

// Can use --resetmemorycache alone to force reading modinfo from database after,
// for example, modifying it to force an error.  Can modify modinfo with something like:
// mysql> update mdl_course set modinfo = replace(modinfo, '693', '793') where id=31;

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if ($options['help']) {
    $help =
"Invokes rebuild_course_cache

Options:
-h, --help            Print out this help.
--all                 Rebuild caches for all courses.
--course              The courseid of the course whose cache is to be rebuilt.
--clearonly           Clear the cache. Let Moodle rebuild on demand.
--checkonly           Checks cached course modules for existence in database.
--resetmemorycache    Clears the static memory cache in get_fast_modinfo.

Example:
php local/course/cli/rebuild_course_cache.php --course=7645 --clearonly

";

    echo $help;
    exit(0);
}

$courseid  = $options['course'];
$clearonly = $options['clearonly'];
$checkonly  = $options['checkonly'];
$resetmemorycache  = $options['resetmemorycache'];

if ($courseid > 0) {

    if ($checkonly) {
        echo "About to check course modules for courseid $courseid.\n";
        $course = $DB->get_record('course', array('id'=>$courseid));
        $modinfo = unserialize($course->modinfo);
        #print_r($modinfo);
        foreach ($modinfo as $cmid => $cm) {
            print_r($cm->mod);
            print(': ');
            print_r($cm->name);
            print("\n");
            if ($DB->record_exists('course_modules', array('id'=>$cmid))) {
                print("         OK\n");
            } else {
                print("         ERROR: The following cached course module does not exist in the database:\n");
                print_r($cm);
            }
        }
    } else {
        echo "About to rebuild course cache for courseid $courseid.\n";
        rebuild_course_cache($courseid, $clearonly);
    }
} elseif ($options['all']) {
    echo "About to rebuild course caches for all courses.\n";
    rebuild_course_cache(0, $clearonly);
} elseif ($resetmemorycache) {
    echo "About to reset the static cache in get_fast_modinfo.\n";
    $reset = 'reset'; // Need a variable because the parameter is passed by reference.
    get_fast_modinfo($reset);
} else {
    echo "You must pass either a --course parameter or --all or --resetmemorycache.\n";
}

echo "Done\n";

exit(0);
