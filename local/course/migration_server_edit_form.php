<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once $CFG->libdir.'/formslib.php';

class edit_migrationserver_form extends moodleform {

    function definition() {
        global $DB;

        $mform =& $this->_form;

        $mform->addElement('header',
                           'migrationserverinfoeditdelete',
                           get_string('migrationserver', 'local_course'));

        $sql =<<<SQL
select mi.id, mi.wwwroot from mdl_moodle_instances mi 
left join mdl_course_request_servers crs
           on mi.id = crs.sourceinstanceid and crs.requestinginstanceid=:currentrequester
where (crs.sourceinstanceid is null or crs.sourceinstanceid=:currentsource)
       and isupgradeserver=0
order by mi.name
SQL;

        $params = array('currentrequester' => $this->_customdata['currentrequester'],
                        'currentsource'    => $this->_customdata['currentsource']);

        $sourceservers = $DB->get_records_sql_menu($sql, $params);

        $mform->addElement('select',
                           'sourceinstanceid',
                           get_string('migrationserverwwwroot', 'local_course'),
                           $sourceservers);

        $upgradeservers = $DB->get_records_menu('moodle_instances',
                                                array('isupgradeserver' => 1),
                                                '',
                                                'id, wwwroot');

        // We want a 'none' option because an upgrade server might not be required.
        $upgradeservers = array(0 => 'none') + $upgradeservers;

        $mform->addElement('select',
                           'upgradeinstanceid',
                           get_string('upgradeserverwwwroot', 'local_course'),
                           $upgradeservers);

        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'local_course'));

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }
}

