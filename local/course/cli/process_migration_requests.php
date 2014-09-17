<?php

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');
require_once($CFG->libdir.'/clilib.php');

if (CLI_MAINTENANCE) {
    echo "CLI maintenance mode active; this script is disabled.\n";
    exit(1);
}

// start cli parameter processing
// no-delete does not delete unmatched responses, delete-only skips every other than that.
list($options, $unrecognized) = cli_get_params(array('no-delete'=>false, 'delete-only'=>false));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}
// end cli parameter processing

$USER = get_admin();

$migration_responder = get_migration_responder();

if (empty($options['delete-only'])) {
    echo "Processing new requests...\n";
    $migration_responder->process_migration_requests();
}

if (empty($options['no-delete'])) {
    echo "Deleting unmatched responses...\n";
    $migration_responder->delete_unmatched_responses();
}

echo "done\n";
