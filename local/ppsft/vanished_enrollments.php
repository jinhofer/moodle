<?php

// This displays PeopleSoft enrollments that simply vanished without a "drop"
// record.  The user can then select to drop that enrollment so that the next
// umnauto cron update drops the corresponding Moodle enrollment.

// In the rare case that a user is enrolled in two Moodle courses for the same
// PeopleSoft class, two rows will appear--one for each course.

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('manage_vanished_ppsft_enrollments');

// return back to self
$returnurl = "$CFG->wwwroot/local/ppsft/vanished_enrollments.php";

$sort = optional_param('sort', 'name', PARAM_ALPHANUM);
if ($sort === 'vanished') {
    $sortorder = 'pe.vanished desc, u.lastname, u.firstname';
} else {
    $sortorder = 'u.lastname, u.firstname, pe.vanished desc';
}

$sql =<<<SQL
select pe.id as psenrolid, pe.userid, u.firstname, u.lastname, u.idnumber, pc.descr,
       pc.term, pc.institution, pc.subject, pc.catalog_nbr, pc.section,
       pc.class_nbr, from_unixtime(pe.vanished) as vanished,
       e.courseid, c.shortname
from {ppsft_class_enrol} pe
  join {user} u on u.id=pe.userid
  join {ppsft_classes} pc on pc.id=pe.ppsftclassid
  join {enrol_umnauto_classes} eu on eu.ppsftclassid=pc.id
  join {enrol} e on e.id=eu.enrolid
  join {course} c on c.id=e.courseid
where pe.status='E' and pe.vanished > 0
order by $sortorder
SQL;

$vanishedenrollments = $DB->get_records_sql($sql);

$table = new html_table();
$table->head = array(html_writer::link(new moodle_url($PAGE->url, array('sort'=>'name')),
                                       get_string('userfullname', 'local_ppsft')),
                     get_string('emplid'       , 'local_ppsft'),
                     get_string('mdlcourse'    , 'local_ppsft'),
                     get_string('classdescr'   , 'local_ppsft'),
                     get_string('catalogentry' , 'local_ppsft'),
                     get_string('ppsfttriplet' , 'local_ppsft'),
                     html_writer::link(new moodle_url($PAGE->url, array('sort'=>'vanished')),
                                       get_string('vanished', 'local_ppsft')),
                     get_string('dropppsft'    , 'local_ppsft'));

foreach ($vanishedenrollments as $v) {
    $row = array();
    $row[] = html_writer::link(new moodle_url('/user/view.php',
                                              array('id' => $v->userid)),
                               $v->firstname . ' ' . $v->lastname);
    $row[] = $v->idnumber;
    $row[] = html_writer::link(new moodle_url('/course/view.php',
                                              array('id' => $v->courseid)),
                               $v->shortname);
    $row[] = $v->descr;
    $row[] = $v->subject . ' ' . $v->catalog_nbr . ' ' . $v->section;
    $row[] = $v->term . '&nbsp;' . $v->institution . '&nbsp;' . $v->class_nbr;
    $row[] = $v->vanished;

    $button = $OUTPUT->action_icon(new moodle_url('forcedrop.php',
                                                  array('psenrolid' => $v->psenrolid)),
                                   new pix_icon('t/delete',
                                                get_string('dropppsft', 'local_ppsft')));

    $row[] = $button;
    $table->data[] = $row;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managevanishedenrollments', 'local_ppsft'));
echo html_writer::table($table);

echo $OUTPUT->footer();

