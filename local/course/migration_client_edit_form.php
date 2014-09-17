<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once $CFG->libdir.'/formslib.php';

class edit_migrationclient_form extends moodleform {

    function definition() {
        global $DB;

        $mform =& $this->_form;

        $mform->addElement('header',
                           'migrationserverinfoeditdelete',
                           get_string('migrationclient', 'local_course'));

        $sql =<<<SQL
select mi.id, mi.wwwroot from mdl_moodle_instances mi 
left join mdl_course_request_servers crs
           on mi.id = crs.requestinginstanceid and crs.sourceinstanceid=:currentsource
where (crs.requestinginstanceid is null or crs.requestinginstanceid=:currentrequester)
       and isupgradeserver=0
order by mi.name
SQL;

        $params = array('currentrequester' => $this->_customdata['currentrequester'],
                        'currentsource'    => $this->_customdata['currentsource']);

        $requestingservers = $DB->get_records_sql_menu($sql, $params);

        $mform->addElement('select', 
                           'requestinginstanceid', 
                           get_string('migrationclientwwwroot', 'local_course'),
                           $requestingservers);

        $mform->addElement('hidden', 'upgradeinstanceid', 0);

        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'local_course'));

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }
}

