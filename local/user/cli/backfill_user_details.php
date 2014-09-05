<?php

/**
 * This script is to carry out local_user_creator::backfill_missing_data()
 * and print the result to console.
 */

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once("{$CFG->dirroot}/local/user/lib.php");

if (CLI_MAINTENANCE) {
    echo "CLI maintenance mode active; this script is disabled.\n";
    exit(1);
}

$creator = new local_user_creator();
$logs = $creator->backfill_missing_data();


// print out the stats
$not_in_ldap = array_diff($logs['candidates'], $logs['updated'], $logs['no_update']);

echo "[", date('Y-m-d H:i:s'), "]";
echo "\nCandidates: ", count($logs['candidates']);
echo "\nNot found in LDAP: ", count($not_in_ldap);
echo "\nUpdated: ", count($logs['updated']);
echo "\nNo-update: ", count($logs['no_update']);

echo "\n\nUpdated usernames: ", implode(', ', $logs['updated']);
echo "\n\nNot updated usernames: ", implode(', ', $logs['no_update']);
echo "\n\nNot found in LDAP: ", implode(', ', $not_in_ldap);

if (count($logs['error']) == 0) {
    echo "\n\nNo error found.";
}
else {
    echo "\n\nErrors:";
    foreach ($logs['error'] as $error) {
        echo "\n{$error}";
    }
}
echo "\n\n";