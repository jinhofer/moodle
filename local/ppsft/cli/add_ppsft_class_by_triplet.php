<?php

/**
 * Takes triplets on stdin delimited by new lines.  Each triplet
 * is term, institution, and class number delimited by spaces.
 *
 * Example: echo "1109 UMNTC 56837" | /swadm/usr/local/php/bin/php add_ppsft_class_by_triplet.php 
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

        $ppsft_updater->add_class_by_triplet($term, $institution, $class_nbr);
        echo "Added ppsft classes $term, $institution, $class_nbr\n";
}


echo "done\n";

