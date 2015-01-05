<?php

function activityclipboard_get_icon($modname, $icon = NULL) {
    global $OUTPUT;

    if (empty($icon)) {
        if ($modname == 'label')
            return '';
        return '<img src="'.$OUTPUT->pix_url('icon',$modname).'" alt="" class="icon" />';
    } else {
        return '<img src="'.$OUTPUT->pix_url($icon).'" alt="" class="icon" />';
    }
}

// Can include items from only a single user.
function activityclipboard_delete_items($itemstodelete, $userid=null) {
    global $USER;

    $userid = $userid ?: $USER->id;

    activityclipboard_table::delete_items(array_keys($itemstodelete));

    // We want to delete files only for those items that are now deleted.
    $remainingitems = activityclipboard_table::get_user_items($userid);
    $itemsdeleted = array_diff_key($itemstodelete, $remainingitems);

    if ($itemsdeleted) {
        $fs = get_file_storage();

        foreach ($itemsdeleted as $itemdeleted) {
            if ($file = $fs->get_file_by_id($itemdeleted->fileid)) {
                $file->delete();
            }
        }
    }
}
