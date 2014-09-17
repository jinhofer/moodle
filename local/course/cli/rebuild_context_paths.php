<?php

/**
 * A wrapper around this function in class context_helper in lib/accesslib.php:
 *
 *      public static function build_all_paths($force = false);
 *
 * If the force argument is false in the function call, only missing
 * context paths get built.  For this script, the setting is always
 * $force = true, which results in all context paths getting rebuilt.
 *
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(array('help'      => false),
                                               array('h' => 'help'));

if ($options['help']) {
    $help =
"Invokes context_helper::build_all_paths(true)

Options:
-h, --help            Print out this help.

Example:
php local/course/cli/rebuild_context_paths.php

";

    echo $help;
    exit(0);
}

echo "About to rebuild all context paths.\n";
context_helper::build_all_paths(true);

echo "Done\n";

exit(0);
