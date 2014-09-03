<?php

/**
 * Provide a way to logout without the need of the session key
 * (by redirecting to /login/logout.php with the session key)
 *
 * @package    local
 * @subpackage login
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

redirect($CFG->wwwroot . '/login/logout.php?sesskey=' . $USER->sesskey);