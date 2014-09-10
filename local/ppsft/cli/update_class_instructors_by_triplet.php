<?php

/**
 * Takes triplets on stdin delimited by new lines.  Each triplet
 * is term, institution, and class number delimited by spaces.
 *
 * Throws exception if triplet has not already been added to
 * ppsft_class_enrol.
 *
 * Example: echo "1113 UMNTC 62479" | /swadm/usr/local/php/bin/php update_class_enrollment_by_triplet.php
 */

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');
require_once(__DIR__.'/../ppsft_data_updater.class.php');
require_once(__DIR__.'/../ppsft_data_adapter.class.php');
require_once($CFG->dirroot.'/local/user/lib.php');

$USER = get_admin();

$ppsft_updater = ppsft_get_updater();

while ($triplet = rtrim(fgets(STDIN, 32))) {

        list($term, $institution, $class_nbr) = preg_split("/\s+/", $triplet);

        $ppsftclass = $DB->get_record('ppsft_classes',
                                      array('term'        => $term,
                                            'institution' => $institution,
                                            'class_nbr'   => $class_nbr),
                                      '*',
                                      MUST_EXIST);

        // Change this value to either add or not add users who are not already in Moodle.
        $addusers = true;

        $ppsft_updater->update_class_instructors($ppsftclass, $addusers);
        echo "Updated ppsft instructors for $term, $institution, $class_nbr\n";
}

echo "done\n";

