<?php

// 20130829 kerzn002
// This script is a wrapper around db_replace and mimics the functionality of
// admin/tool/replace/index.php including rebuilding the course cache.

define('CLI_SCRIPT', true);
define('NO_OUTPUT_BUFFERING', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/adminlib.php');

list($options, $unrecognized) = cli_get_params(
	array(
		'search'	    => '',
		'replace'	    => '',
		'help'          => false,
		'really-sure'   => false,
	),
	array(
		'h' => 'help',
	)
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if ($options['help']) {
    $help =
"Replace all instances of one string with another in the database.

Options:
-h, --help      Print out this help
--search        Substition original string.
--replace       New string.
--really-sure   Are you sure you want to do this?

Example:

Replace foo with bar.

\$/usr/bin/php admin/cli/substitute.php --really-sure --search \"foo\" --replace \"bar\"
";

    echo $help;
    exit(0);
}

if (!$options['really-sure']) {
	cli_error("You must be sure that you want to do this!  Use -h or --help to show help.");
}

cli_heading("Replacing \"" . $options['search'] . "\" with \"" . $options['replace'] . "\"");

db_replace($options['search'], $options['replace']);

// For 2.8, Colin removed call to rebuild_course_cache to be consistent with
// admin/tool/replace/index.php and the new overall approach for cache management
// in Moodle.

exit(0);
