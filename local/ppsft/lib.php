<?php

require_once($CFG->libdir.'/dml/oci_native_moodle_database.php');
require_once('ppsft_data_adapter.class.php');
require_once('ppsft_data_updater.class.php');
require_once($CFG->dirroot.'/local/user/lib.php');

/**
  * Core cron_run function in lib/cronlib.php invokes
  * get_plugin_list_with_function in lib/moodlelib.php
  * and will pick up this function because its name follows
  * the pattern plugintype . '_' . plugin . '_' . cron.
  */
function local_ppsft_cron() {
    # TODO: Can we find a way to use the lastcron config to check that
    #       slow_cron is actually getting executed periodically.
    #       See cron_execute_plugin_type in lib/cronlib.php.
    echo "INFO: Use other cron to execute local/ppsft updates through ppsft_slow_cron().\n";
}

/**
 * The main Moodle cron should not execute this because the
 * name does not exactly match the convention. (See local_ppsft_cron.)
 * Instead, we should use a separate cron due to the time required
 * for this to run.  See cli directories under auth and enrol for
 * examples of additional cron scripts.
 */
function ppsft_slow_cron() {

    # TODO: FINISH THIS.  Update instructor classes, also.
    # TODO: Need to update instructors with updater->update_instructor_classes.
    # Include all Moodle instructors associated with a Moodle course that
    # is associated with PeopleSoft class in the current term or later.
    # Consider doing all instructors in another place since the class associations
    # are part of the autoenrollment functionality and don't want to
    # to create a dependency on that.

    # TODO: Generally, we should not need to run update_class_enrollments as
    #       we can stay current updating students based on the
    #       ps_um_da_dly_audit table.
    $updater = ppsft_get_updater();
    $updater->update_all_class_enrollments();
}

/**
 * Helper for getting the Oracle PeopleSoft database connection.
 * Uncommenting the "set_debug" line will result in rather
 * verbose debug logging.
 */
 # TODO: Look at whether we can lazily connect. Could pass around
 #       a connection factory, if necessary.
function ppsft_database_connection() {
    global $CFG;

    $prefix = false;

    $external = true;

    $ppsft = new oci_native_moodle_database($external);
    #$ppsft->set_debug(true);

    # 20110915 Colin. Added dbpersist so that multiple calls will
    #                 not create multiple connections.
    if (!$ppsft->connect($CFG->ppsft_dbhost,
                         $CFG->ppsft_dbuser,
                         $CFG->ppsft_dbpass,
                         $CFG->ppsft_dbname,
                         $prefix,
                         array('dbpersist' => true)))
    {
        throw new Exception('Failed to connect to ppsft database');
    }

    return $ppsft;
}

/**
 *
 */
function ppsft_get_adapter() {
    return new ppsft_data_adapter(ppsft_database_connection());
}

/**
 *
 */
function ppsft_get_updater($ppsft_data_adapter=null, $user_creator=null) {
    if (! $ppsft_data_adapter) {
        $ppsft_data_adapter = ppsft_get_adapter();
    }

    if (! $user_creator) {
        $user_creator = new local_user_creator();
    }

    return new ppsft_data_updater($ppsft_data_adapter, $user_creator);
}

/**
 *
 */
 # TODO: Look at caching in config setting.
 # TODO: Do we need this AND the method in updater?
function ppsft_current_term() {
    return ppsft_get_adapter()->get_current_term();
}

/**
 * Helper to create database-specific SQL string concatenation of triplet.
 * TODO: Consider moving to ppsft_data_updater.class.php as a private
 *       method, if that's the only place it's used.
 *       Or, also use in ppsft_data_adapter.class.php to help build
 *       triplet strings in that SQL where appropriate.  Must match
 *       in both files, anyway.
 */
function ppsft_triplet_concat_sql($db) {
    return $db->sql_concat('pc.term', 'pc.institution', 'pc.class_nbr');
}

/**
 *
 */
function ppsft_term_string_from_number($termnum, $abbreviated=false) {
    if (preg_match('/^\d\d\d\d$/', $termnum)) {
        $year = substr($termnum, 0, 3);
        $session = substr($termnum, 3, 1);

        if ($abbreviated) {
            $year = substr($termnum, 1, 2);
        } else {
            $year = substr($termnum, 0, 3);
            $year += 1900;
        }

        switch($session) {
            case 9 : return $abbreviated ? "F$year"  : "Fall $year";
            case 3 : return $abbreviated ? "S$year"  : "Spring $year";
            case 5 : return $abbreviated ? "Su$year" : "Summer $year";
        }
    }
    throw new InvalidArgumentException("Invalid term number: $termnum");
}


/**
 *
 */
# TODO: Should we, can we, get this dynamically?
#       Or, should we make configurable?
function ppsft_institutions() {
    return array ( 'UMNDL'=>'Duluth',
                   'UMNMO'=>'Morris',
                   'UMNTC'=>'Twin Cities',
                   'UMNCR'=>'Crookston' );
}

function ppsft_institution_name($code) {
    $institution_map = ppsft_institutions();

    if (array_key_exists($code, $institution_map)) {
        return $institution_map[$code];
    }
    return $code;
}

/**
 * Event handler for user deletion.  See reference in
 * local/ppsft/db/events.php.
 */
function ppsft_handle_user_deleted($user) {
    $updater = ppsft_get_updater();
    $updater->purge_user($user);
}

