<?php

    require_once '../../config.php';
    require_once 'activityclipboard_table.php';

    require_login();

    // Security is based on checking $USER->id against userid
    // in the block_activityclipboard record.

    $shared_id = required_param('id', PARAM_INT);
    $return_to = required_param('return', PARAM_LOCALURL);
    $insert_to = optional_param('to', null, PARAM_INT);

    $shared = activityclipboard_table::get_record_by_id($shared_id)
        or print_error('err_shared_id', 'block_activityclipboard', $return_to);

    $shared->userid == $USER->id
        or print_error('err_capability', 'block_activityclipboard', $return_to);

    $dest_sort = 0;
    if (!empty($insert_to)
        and $target = $DB->get_record('block_activityclipboard',
                                      array('id'     => $insert_to,
                                            'tree'   => $shared->tree,
                                            'userid' => $USER->id)))
    {
        $dest_sort = $target->sort;
    } else {
        $max_sort = $DB->get_field_sql(
            "SELECT MAX(sort) FROM {block_activityclipboard}
            WHERE userid = '$USER->id' AND tree = '$shared->tree'");
        $dest_sort = $max_sort + 1;
    }

    $sql = "UPDATE {block_activityclipboard} SET sort = sort + 1
            WHERE userid = '$USER->id' AND tree = '$shared->tree'
            AND sort >= $dest_sort";
    $DB->execute($sql);

    $shared->sort = $dest_sort;
    activityclipboard_table::update_record($shared)
        or print_error('err_move', 'block_activityclipboard', $return_to);

    redirect($return_to);
