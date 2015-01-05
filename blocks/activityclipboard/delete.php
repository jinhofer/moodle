<?php

    require_once '../../config.php';
    require_once 'activityclipboard_table.php';

    require_login();

    // Security is based on checking $USER->id against userid
    // in the block_activityclipboard record.

    $shared_id = required_param('id', PARAM_INT);
    $return_to = required_param('return', PARAM_LOCALURL);

    $shared = activityclipboard_table::get_record_by_id($shared_id)
        or print_error('err_shared_id', 'block_activityclipboard', $return_to);

    $shared->userid == $USER->id
        or print_error('err_capability', 'block_activityclipboard', $return_to);

    $fs = get_file_storage();
    if ($file = $fs->get_file_by_id($shared->fileid)) {
        $file->delete();
    }

    activityclipboard_table::delete_record($shared)
        or print_error('err_delete', 'block_activityclipboard', $return_to);

    redirect($return_to);
