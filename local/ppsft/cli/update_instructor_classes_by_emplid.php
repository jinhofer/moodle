<?php

/**
 * Takes emplids on stdin delimited by new lines.
 *
 * Example: echo 4556655 | /swadm/usr/local/php/bin/php update_instructor_classes_by_emplid.php
 */

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');
require_once(__DIR__.'/../ppsft_data_updater.class.php');
require_once(__DIR__.'/../ppsft_data_adapter.class.php');
require_once($CFG->dirroot.'/local/user/lib.php');

$USER = get_admin();

$ppsft_updater = ppsft_get_updater();

while ($emplid = rtrim(fgets(STDIN, 32))) {

    if (!$userid = $DB->get_field('user', 'id', array('idnumber'=>$emplid))) {

        $rv = local_user::create_from_emplid(array($emplid));
        if (! empty($rv['moodle_ids'])) {
            $userid = array_shift($rv['moodle_ids']);
            echo "Created user $userid";
            echo "\n";
        } else {
            print_r($rv['errors']);
        }
    }

    if ($userid) {
        $user = new stdClass();
        $user->id = $userid;
        $user->idnumber = $emplid;
        $ppsft_updater->update_instructor_classes($user);
        echo "Updated ppsft classes for $userid $emplid\n";
    }
}


echo "done\n";

