<?php

// When a user selects to drop a PeopleSoft enrollment on vanished_enrollments.php,
// this is where they go to get it done.

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');

$psenrolid = required_param('psenrolid', PARAM_INT);
$PAGE->set_url('/local/ppsft/forcedrop.php',
               $psenrolid ? array('psenrolid'=>$psenrolid) : null);

admin_externalpage_setup('manage_vanished_ppsft_enrollments');

$returnurl = "$CFG->wwwroot/local/ppsft/vanished_enrollments.php";

class drop_vanished_ppsft_enrollment_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $mform->addElement('header', 'ppsftenrollmentdrop', get_string('dropppsftenrollment', 'local_ppsft'));

        $mform->addElement('static', 'userfullname', get_string('userfullname', 'local_ppsft'));
        $mform->addElement('static', 'triplet', get_string('ppsfttriplet', 'local_ppsft'));
        $mform->addElement('static', 'catalogentry', get_string('catalogentry', 'local_ppsft'));

        if (!empty($this->_customdata->term_name)) {
            $mform->addElement('static', 'term_name', get_string('termname', 'local_ppsft'));
        }
        $mform->addElement('static', 'long_title', get_string('classdescr', 'local_ppsft'));
        $mform->addElement('static', 'coursefullname', get_string('mdlcourse', 'local_ppsft'));
        $mform->addElement('static', 'vanished', get_string('vanished', 'local_ppsft'));

        $mform->addElement('hidden', 'psenrolid', 0);
        $mform->setType('psenrolid', PARAM_INT);

        $this->add_action_buttons(true, get_string('dropppsft', 'local_ppsft'));
    }
}

$sql =<<<SQL
select pe.id as psenrolid, pe.userid, u.firstname, u.lastname, pc.descr,
       pc.term, pc.institution, pc.subject, pc.catalog_nbr, pc.section,
       pc.class_nbr, pc.long_title, from_unixtime(pe.vanished) as vanished,
       e.courseid, c.shortname, c.fullname as coursefullname, t.term_name
from {ppsft_class_enrol} pe
  join {user} u on u.id=pe.userid
  join {ppsft_classes} pc on pc.id=pe.ppsftclassid
  join {enrol_umnauto_classes} eu on eu.ppsftclassid=pc.id
  join {enrol} e on e.id=eu.enrolid
  join {course} c on c.id=e.courseid
  left join {ppsft_terms} t on t.term=pc.term
where pe.status='E' and pe.vanished > 0 and pe.id=:psenrolid
SQL;

$v = $DB->get_record_sql($sql, array('psenrolid' => $psenrolid), MUST_EXIST);

$v->userfullname = $v->firstname.' '.$v->lastname;
$v->triplet = $v->term.' '.$v->institution.' '.$v->class_nbr;
$v->catalogentry = $v->subject.' '.$v->catalog_nbr.' '.$v->section;

$form = new drop_vanished_ppsft_enrollment_form(null, $v);

$form->set_data($v);

if ($form->is_cancelled()) {

    redirect($returnurl);

} else if ($data = $form->get_data()) {
    $DB->update_record('ppsft_class_enrol', array('id' => $psenrolid, 'status' => 'D'));
    redirect($returnurl);
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();

