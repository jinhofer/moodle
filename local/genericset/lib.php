<?php

/**
 * Simple wrapper methods around generic_set table.
 */

/**
 *
 */
function genericset_get($setname) {
    global $DB;

    $values = $DB->get_fieldset_sql('select distinct value from {generic_set} where name=?', 
                                    array($setname));

    return $values;
}

/**
 *
 */
function genericset_add($setname, $value) {
    global $DB;

    try {
        $DB->insert_record('generic_set',
                           array('name' => $setname,
                                 'value' => $value,
                                 'created' => time()));
    } catch (dml_write_exception $ex) {
        // If the value already exists in the set, assume the exception is caused 
        // by a key violation and ignore the exception.  Otherwise, rethrow.
        if (! $DB->record_exists('generic_set',
                                 array('name' => $setname,
                                       'value' => $value)))
        {
            throw $ex;
        }
    }
}

/**
 *
 */
function genericset_remove($setname, $value) {
    global $DB;

    $DB->delete_records('generic_set', array('name' => $setname,
                                             'value' => $value));
}
