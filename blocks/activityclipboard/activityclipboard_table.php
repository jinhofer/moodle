<?php
/**
 *
 */

class activityclipboard_table
{
    /**
     *
     */
    public static function get_record_by_id($id)
    {
      global $DB;
      $record = $DB->get_record('block_activityclipboard', array('id'=> $id));
        if (!$record)
            return NULL;
        return $record;
    }

    public static function get_user_items($userid=null) {
        global $DB, $USER;

        $userid = $userid ?: $USER->id;

        // Using tree='' in the condition puts items that are not
        // in folders at the bottom, as they are in the block.
        return $DB->get_records('block_activityclipboard',
                                array("userid" => $USER->id),
                                "tree='', tree, sort");
    }


    /**
     *
     */
    public static function insert_record($record)
    {
        global $DB;
        if (!$DB->insert_record('block_activityclipboard', $record))
            return FALSE;
        self::renumber($record->userid);
        return TRUE;
    }

    /**
     *
     */
    public static function update_record($record)
    {
      global $DB;
        if (!$DB->update_record('block_activityclipboard', $record))
            return FALSE;
        self::renumber($record->userid);
        return TRUE;
    }

    /**
     *
     */
    public static function delete_record($record)
    {
      global $DB;
      if (!$DB->delete_records('block_activityclipboard', array('id'=>$record->id)))
            return FALSE;
        self::renumber($record->userid);
        return TRUE;
    }

    public static function delete_items($deleteids, $userid=null) {
        global $DB, $USER;

        $userid = $userid ?: $USER->id;

        foreach ($deleteids as $deleteid) {
            $DB->delete_records('block_activityclipboard',
                                array('id'     => $deleteid,
                                      'userid' => $userid));
        }

        self::renumber($userid);
        return TRUE;
    }



    /**
     *
     */
    public static function renumber($user_id = NULL)
    {
        global $DB, $USER;
        if (empty($user_id)) {
            $user_id = $USER->id;
        }
        if ($records = $DB->get_records('block_activityclipboard', array('userid'=> $user_id))) {
            $tree = array();
            foreach ($records as $record) {
                if (!isset($tree[$record->tree]))
                    $tree[$record->tree] = array();
                $tree[$record->tree][] = $record;
            }
            foreach ($tree as $items) {
                usort($items, array(__CLASS__, 'renumber_cmp'));
                foreach ($items as $i => $item) {
                    $item->sort = 1 + $i;
                    $item->text = $item->text;
                    if (!$DB->update_record('block_activityclipboard', $item))
                        return FALSE;
                }
            }
        }
        return TRUE;
    }
    protected static function renumber_cmp($a, $b)
    {
        if ($a->sort < $b->sort) return -1;
        if ($a->sort > $b->sort) return +1;
        return strnatcasecmp($a->text, $b->text);
    }
}
