<?php

function xmldb_local_user_upgrade($oldversion = 0) {
    global $DB;

    $DB->delete_records('config_plugins', array('plugin'    => 'auth/shibboleth',
                                                'name'      => 'field_map_city'));
    $DB->delete_records('config_plugins', array('plugin'    => 'auth/shibboleth',
                                                'name'      => 'field_map_country'));
    return true;
}