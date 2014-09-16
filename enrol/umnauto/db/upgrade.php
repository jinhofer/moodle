<?php

function xmldb_enrol_umnauto_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012101701) {
        // Define field autogroup_option to be added to enrol_umnauto_course
        $table = new xmldb_table('enrol_umnauto_course');
        $field = new xmldb_field('autogroup_option', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Conditionally launch add field autogroup_option
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // umnauto savepoint reached
        upgrade_plugin_savepoint(true, 2012101701, 'enrol', 'umnauto');
    }

    if ($oldversion < 2012121301) {

        // Changing the default of field auto_withdraw on table enrol_umnauto_course to 1
        $table = new xmldb_table('enrol_umnauto_course');
        $field = new xmldb_field('auto_withdraw', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', 'auto_drop');

        // Launch change of default for field auto_withdraw
        $dbman->change_field_default($table, $field);

        // Change values to the new default for courses that have no ppsft classes associated
        // that are for terms prior to Spring 2013 (1133).
        $sql = "
update {enrol_umnauto_course} uc
    join (
select ucs.id, min(pc.term)
from {enrol_umnauto_course} ucs
  join {enrol} e on e.courseid=ucs.courseid
  join {enrol_umnauto_classes} ucl on ucl.enrolid=e.id
  join {ppsft_classes} pc on pc.id=ucl.ppsftclassid
where e.enrol='umnauto' and ucs.auto_withdraw=0
group by ucs.id
having min(pc.term) >= '1133'
    ) ucslist on ucslist.id = uc.id
set uc.auto_withdraw=1";

        $DB->execute($sql);

        // umnauto savepoint reached
        upgrade_plugin_savepoint(true, 2012121301, 'enrol', 'umnauto');
    }

    return true;
}

