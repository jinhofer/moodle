<?php

/**
 * This script initializes mdl_course_request_servers and populates
 * mdl_moodle_instances as necessary. We do this outside upgrade API
 * due to the dependency of the local/course plugin on local/instances.
 *
 * Insert any existing $CFG->migration_server_map and $CFG->migration_clients
 * servers into mdl_moodle_instances. Prefix all names with "https://" and
 * replace all underscores ("_") with slashes ("/") on the assumption that
 * they originally were slashes.
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
#require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/local/instances/lib.php');

cron_setup_user();

function get_wwwroot_from_instancename($instancename) {
    return 'https://'.str_replace('_', '/', $instancename);
}

function check_add_moodle_instance($wwwroot, $isupgradeserver=false) {
    global $DB;

    ###### TODO: Get id of existing and return.

    if ($DB->record_exists('moodle_instances', array('wwwroot' => $wwwroot))) {
        echo "$wwwroot already in mdl_moodle_instances.\n";
    } else {
        echo "Adding $wwwroot to mdl_moodle_instances.\n";
        $instancedata = new stdClass;
        $instancedata->wwwroot = $wwwroot;
        if ($isupgradeserver) {
            $instancedata->isupgradeserver = 1;
        }
        return add_moodle_instance($instancedata);
    }
}

function get_moodle_instance_id_from_name($name) {
    global $DB;

    return $DB->get_field('moodle_instances',
                          'id',
                          array('name'=>$name),
                          MUST_EXIST);
}


function check_add_migration_source($sourceservername, $upgradeservername) {
    global $CFG, $DB;

    $thisservername = get_instancename_from_wwwroot($CFG->wwwroot);
    $thisinstanceid = get_moodle_instance_id_from_name($thisservername);

    $mapdata = array(
        'requestinginstanceid' => $thisinstanceid,
        'sourceinstanceid' => get_moodle_instance_id_from_name($sourceservername));

    if ($DB->record_exists('course_request_servers', $mapdata)) {
        echo "Mapping for source $sourceservername already exists in "
             ."mdl_course_request_servers.\n";
    } else {

        echo "Adding mapping for source $sourceservername to mdl_course_request_servers.\n";

        $mapdata['upgradeinstanceid'] = empty($upgradeservername)
                                      ? 0
                                      : get_moodle_instance_id_from_name($upgradeservername);
        $mapdata['enabled'] = 1;

        $DB->insert_record('course_request_servers', $mapdata);
    }
}

function check_add_migration_client($clientname) {
    global $CFG, $DB;

    $thisservername = get_instancename_from_wwwroot($CFG->wwwroot);
    $thisinstanceid = get_moodle_instance_id_from_name($thisservername);

    $mapdata = array(
        'sourceinstanceid' => $thisinstanceid,
        'requestinginstanceid' => get_moodle_instance_id_from_name($clientname));

    if ($DB->record_exists('course_request_servers', $mapdata)) {
        echo "Mapping for client $clientname already exists in "
             ."mdl_course_request_servers.\n";
    } else {
        echo "Adding mapping for $clientname to mdl_course_request_servers.\n";

        $mapdata['upgradeinstanceid'] = 0;
        $mapdata['enabled'] = 1;

        $DB->insert_record('course_request_servers', $mapdata);
    }
}


// Ensure that the current instance is in mdl_moodle_instances.
check_add_moodle_instance($CFG->wwwroot);

###### TODO: Add records to mdl_course_request_servers.

if (isset($CFG->migration_server_map)) {

    // Get any migration upgrade servers.
    $upgrade_servers = array_unique($CFG->migration_server_map);
    foreach ($upgrade_servers as $servername) {
        if (empty($servername)) {
            continue;
        }
        $wwwroot = get_wwwroot_from_instancename($servername);
        check_add_moodle_instance($wwwroot, true);
    }

    // Get any other migration servers. Upgrade servers must
    // be loaded already have been loaded.
    foreach ($CFG->migration_server_map as $migservername=>$upgradeservername) {
        $wwwroot = get_wwwroot_from_instancename($migservername);
        check_add_moodle_instance($wwwroot);
        check_add_migration_source($migservername, $upgradeservername);
    }
}

// Get any migration clients.
if (isset($CFG->migration_clients)) {
    foreach ($CFG->migration_clients as $migclientname) {
        $wwwroot = get_wwwroot_from_instancename($migclientname);
        check_add_moodle_instance($wwwroot);
        check_add_migration_client($migclientname);
    }
}
