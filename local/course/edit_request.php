<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir.'/formslib.php');
require_once('lib.php');
require_once('request_form.php');

require_login();
require_capability('moodle/site:approvecourse', context_system::instance());

$PAGE->set_url("$CFG->wwwroot/local/course/edit_request.php");
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
#admin_externalpage_setup('editcourserequest');

$strtitle = get_string('editrequestpagetitle', 'local_course');
$PAGE->set_title($strtitle);

$PAGE->set_heading(get_site()->fullname);

$returnurl = "$CFG->wwwroot/local/course/pending.php";

class local_course_request_form_edit extends local_course_request_form_base {

    function definition() {
        $mform =& $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->define_form_header();

        $this->define_sourcecourseurl_elements('sourcecourseurl_editinstructions');

        // The sixth parameter for advcheckbox contains an array of the unchecked
        // and checked values.
        $mform->addElement('html', '<div class="elementwrapper">');
        $mform->addElement('advcheckbox',
                           'copyuserdata',
                           get_string('copyuserdata', 'local_course'),
                           null,
                           null,
                           array(0, 1));
        $mform->addElement('html', '</div>');

        $this->add_action_buttons();
    }
}

$editform = new local_course_request_form_edit();

if ($editform->is_cancelled()) {
    redirect($returnurl);
}

$requestid = required_param('id', PARAM_INT);


if (!empty($requestid) and confirm_sesskey()) {

    if ($formdata = $editform->get_data()) {
        $requestupdate = $formdata;
        $requestupdate->timemodified = time();
        $requestupdate->modifierid   = $USER->id;
        $DB->update_record('course_request_u', $requestupdate);
        redirect($returnurl);
    }

    $request = $DB->get_record('course_request_u', array('id'=>$requestid));
    $editform->set_data($request);

    $PAGE->navbar->add(get_string('coursespending'),
                                  new moodle_url($returnurl));
    $PAGE->navbar->add($strtitle);

    echo $OUTPUT->header();
    echo $OUTPUT->heading($strtitle);

    $editform->display();

    echo $OUTPUT->footer();
}
