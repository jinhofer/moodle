<?php

require_once '../../config.php';
require_once 'activityclipboard_table.php';
require_once 'lib.php';

require_login();

// Security is based on checking $USER->id against userid
// in the block_activityclipboard record.

$shared_id = optional_param('id', 0, PARAM_INT);
$return_to = required_param('return', PARAM_LOCALURL);
$move_to   = required_param('to', PARAM_CLEAN);

$move_to = trim($move_to);

$move_to = implode('/', array_filter(explode('/', $move_to), 'strlen'));

$shared = activityclipboard_table::get_record_by_id($shared_id)
    or print_error('err_shared_id', 'block_activityclipboard', $return_to);

$shared->userid == $USER->id
    or print_error('err_capability', 'block_activityclipboard', $return_to);

$shared->tree = $move_to;
activityclipboard_table::update_record($shared)
    or print_error('err_move', 'block_activityclipboard', $return_to);

redirect($return_to);
