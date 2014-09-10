<?php

/**
 *
 * Example: php sync_vanished_enrollments.php 
 */

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');
require_once(__DIR__.'/../ppsft_data_updater.class.php');
require_once(__DIR__.'/../ppsft_data_adapter.class.php');
require_once($CFG->dirroot.'/local/user/lib.php');

$USER = get_admin();

$ppsft_updater = ppsft_get_updater();

$ppsft_updater->sync_vanished_enrollments();

echo "done\n";

