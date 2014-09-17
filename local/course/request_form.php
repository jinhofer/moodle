<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

// Field validation for required radio buttons are set to 'server' because
// addRule fails with 'client' and group, and addGroupRule goes to the server
// regardless, in this case.

/**
 *
 */
abstract class local_course_request_form_base extends moodleform {

    /**
     *
     */
    public function definition_after_data() {
        $mform =& $this->_form;

        // Pending courses page breaks if whitespace is not trimmed on sourcecourseurl.
        $mform->applyFilter('sourcecourseurl', 'trim');
    }

    /**
     *
     */
    protected function define_submission_elements_with_close_header() {
        $mform =& $this->_form;

        $previous = $this->_customdata['previous'];

        $buttons = array();
        $buttons[] = &$mform->createElement(
                          'button',
                          'previousbutton',
                          get_string('previous'),
                          "onclick=\"window.location.href='$previous'; return false;\"");
        $buttons[] = &$mform->createElement('submit', 'requestsubmitbutton', get_string('submit'), array('class'=>'form-submit'));
        $mform->addGroup($buttons, 'buttons', '', array(' '), false);

        #$mform->closeHeaderBefore('submissioncomments');
        #$mform->addElement('static', 'submissioncomments', null, get_string('submissioncomments', 'local_course'));
        #$this->add_action_buttons(true, get_string('requestcourse', 'local_course'));
    }

    /**
     *
     */
    protected function define_depth1_category_element() {
        global $DB;
        $mform =& $this->_form;

        #$sql = 'select id, name from {course_categories} where depth=1 order by sortorder';
        $sql =<<<SQL
select cc.id, cc.name
from {course_categories} cc
  join {course_request_category_map} cm on cm.categoryid = cc.id
where cm.display = 1 and cc.depth = 1
order by cc.sortorder
SQL;

        $depth1categories = $DB->get_records_sql_menu($sql);
        $depth1categories = array('' => get_string('depth1select', 'local_course')) + $depth1categories;
        $mform->addElement('select',
                           'depth1category',
                           get_string('depth1category', 'local_course'),
                           $depth1categories);

        $mform->addRule('depth1category',
                        get_string('missingdepth1category', 'local_course'),
                        'required', null, 'client');

        $mform->setType('depth1category', PARAM_INTEGER);
    }

    /**
     *
     */
    protected function define_depth2_category_element() {
        global $DB;
        $mform =& $this->_form;

        // Even though Javascript takes care of loading the dropdown dynamically, we must load
        // it with all possible value here so that the moodle form works correctly.
        $sql =<<<SQL
select cc.id, cc.name
from {course_categories} cc
  join {course_request_category_map} cm on cm.categoryid = cc.id
where cm.display = 1 and cc.depth = 2
order by cc.sortorder
SQL;

        $depth2categories = $DB->get_records_sql_menu($sql);
        $depth2categories = array('' => get_string('depth2select', 'local_course')) + $depth2categories;
        $mform->addElement('select',
                           'depth2category',
                           get_string('depth2category', 'local_course'),
                           $depth2categories);

        $mform->addRule('depth2category',
                        get_string('missingdepth2category', 'local_course'),
                        'required', null, 'client');

        $mform->setType('depth2category', PARAM_INTEGER);
    }

    /**
     *
     */
    protected function define_depth3_category_element() {
        global $DB;
        $mform =& $this->_form;

        // Even though Javascript takes care of loading the dropdown dynamically, we must load
        // it with all possible value here so that the moodle form works correctly.
        $sql =<<<SQL
select cc.id, cc.name
from {course_categories} cc
  join {course_request_category_map} cm on cm.categoryid = cc.id
where cm.display = 1 and cc.depth = 3
order by cc.sortorder
SQL;

        $depth3categories = $DB->get_records_sql_menu($sql);
        $depth3categories = array('' => get_string('depth3select', 'local_course')) + $depth3categories;
        $mform->addElement('select',
                           'depth3category',
                           get_string('depth3category', 'local_course'),
                           $depth3categories);

        $mform->setType('depth3category', PARAM_INTEGER);
    }

    protected function define_category_elements() {
        $mform =& $this->_form;

        $mform->addElement('html', '<div class="elementwrapper" id="depth1div">');
        $this->define_depth1_category_element();
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '<div class="elementwrapper" id="depth2div">');
        $this->define_depth2_category_element();
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '<div class="elementwrapper" id="depth3div">');
        $this->define_depth3_category_element();
        $mform->addElement('html', '</div>');
    }

    protected function define_role_element() {
        global $DB;

        $mform =& $this->_form;

        $mform->addElement('html', '<div class="elementwrapper" id="yourrolediv">');

        $rolemenu = get_course_request_assignable_roles();

        $mform->addElement('select',
                           'yourrole',
                           get_string('yourrole', 'local_course'),
                           array(''    =>get_string('roleselect', 'local_course'),
                                 'none'=>get_string('roleselectnone', 'local_course'))
                               + $rolemenu);

        $mform->addRule('yourrole',
                        get_string('missingyourrole', 'local_course'),
                        'required', null, 'client');

        $mform->setType('yourrole', PARAM_ALPHANUM);

        $mform->addElement('html', '</div>');
    }

    /**
     *
     */
    protected function define_form_header() {
        $mform =& $this->_form;

        $mform->addElement('header', 'requestformfieldset');
    }

    /**
     *
     */
    protected function define_course_name_elements($fullnamelabel, $shortnamelabel) {
        $mform =& $this->_form;

        $mform->addElement('html', '<div class="elementwrapper" id="fullnamediv">');
        $mform->addElement('text', 'fullname', $fullnamelabel, 'maxlength="254"');
        $mform->addRule('fullname', get_string('missingfullname', 'local_course'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addElement('html', '<div class="helpnote">'.get_string('fullnamehelpnote', 'local_course').'</div>');
        $mform->addElement('html', '</div>');  // fullnamediv


        $mform->addElement('html', '<div class="elementwrapper" id="shortnamediv">');
        $mform->addElement('text', 'shortname', $shortnamelabel, 'maxlength="100"');
        $mform->addElement('html', '<div class="helpnote">'.get_string('shortnamehelpnote', 'local_course').'</div>');
        $mform->addElement('html', '</div>');  // shortnamediv

        $mform->addRule('shortname', get_string('missingshortname', 'local_course'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_TEXT);
    }

    /**
     *
     */
    protected function define_requestformtype_elements($requestformtype) {
        $this->_form->addElement('hidden', 'requestform', $requestformtype);
        $this->_form->setType('requestform', PARAM_TEXT);
    }

    /**
     *
     */
    protected function define_other_role_elements() {
        global $OUTPUT;
        $mform =& $this->_form;

        $rolemenu = get_course_request_assignable_roles();
        $rolemenu = array('' => get_string('additionalroleselecttop', 'local_course')) + $rolemenu;

        $mform->addElement('html', "<div id=\"additionalrolesdiv\">");

        $repeatarray = array();

        $repeatarray[] = &$mform->createElement('html', "<div class=\"additionalrolediv\">");
        $repeatarray[] = &$mform->createElement('html', "<div class=\"elementwrapper additionalroleselectdiv\">");

        $repeatarray[] = &$mform->createElement('select',
                           "additionalroleselect",
                           get_string('additionalroleselect', 'local_course'),
                           $rolemenu);

        $repeatarray[] = &$mform->createElement('html', '</div>');  // additionalrolediv

        $repeatarray[] = &$mform->createElement('html', "<div class=\"elementwrapper additionalroleuserlistdiv\">");
        $repeatarray[] = &$mform->createElement('text',
                           "additionalroleuserlist",
                           get_string('additionalroleuserlist', 'local_course'),
                           'maxlength="200"');
        $repeatarray[] = &$mform->createElement('advcheckbox',
                           "additionalroleemail",
                           '',
                           get_string('sendemail', 'local_course'));
        $repeatarray[] = &$mform->createElement('html', '</div><!-- end additionalroleuserlistdiv -->');
        $repeatarray[] = &$mform->createElement('html', '</div><!-- end additionalrolediv -->');

        $repeatoptions = array();

        $mform->setType("additionalroleselect", PARAM_INTEGER);
        $mform->setType("additionalroleuserlist", PARAM_TEXT);

        $this->repeat_elements($repeatarray, 1, $repeatoptions, 'rolediv_repeats', 'rolediv_add',
                                1, get_string('addadditionalrolerow', 'local_course'), true);

        $mform->addElement('html', '</div><!-- end additionalrolesdiv -->');


        $plusimg = '<img src="'.$OUTPUT->pix_url('green_plus', 'local_course').'"/>';
        $mform->addElement('html', html_writer::tag('div',
                                        html_writer::tag('span',
                                                         $plusimg.get_string('addadditionalrolerow', 'local_course')),
                                        array('id'=>'addadditionalrolerowdiv',
                                              'class'=>'addrowsorlookup elementwrapper')));

        $mform->addElement('html', '<div class="helpnote elementwrapper">'.get_string('addadditionalroleuserhelpnote', 'local_course').'</div>');
    }

    /**
     *
     */
    protected function define_sourcecourseurl_elements($instructions_stringid='sourcecourseurl_instructions') {
        $mform =& $this->_form;

        #$mform->addElement('static', 'sourcecourseurl_instructions', null, get_string($instructions_stringid, 'local_course'));
        $mform->addElement('html', '<div class="elementwrapper" id="sourcecourseurldiv">');
        $mform->addElement('text', 'sourcecourseurl', get_string('sourcecourseurl', 'local_course'), 'maxlength="200"');
        $mform->addElement('html', '<div class="helpnote">'.get_string('sourcecourseurlhelpnote', 'local_course').'</div>');
        $mform->addElement('html', '</div><!-- end sourcecourseurldiv -->');
        $mform->setType('sourcecourseurl', PARAM_TEXT);
    }

    // Although this comes from the 'reason' elements of the original form,
    // UMN is using the text box for additional comments or instructions.
    protected function define_reason_text_elements() {
        $mform =& $this->_form;

        $mform->addElement('html', '<div class="elementwrapper" id="courserequestsupportdiv">');
        $mform->addElement('textarea', 'reason', get_string('courserequestsupport', 'local_course'), array('rows'=>'5'));
        $mform->addElement('html', '<div class="helpnote">'.get_string('courserequestsupporthelpnote', 'local_course').'</div>');
        $mform->addElement('html', '</div>');  // courserequestsupportdiv
        $mform->setType('reason', PARAM_TEXT);
        $mform->addRule('reason', get_string('maximumchars', '', 500), 'maxlength', 500, 'client');
    }

    /**
     *
     */
    function validation($data, $files) {

        $mform =& $this->_form;

        $errors = parent::validation($data, $files);

        $errors += $this->validate_additional_role_users($data);
        $errors += $this->validate_sourcecourseurl($data);

        return $errors;
    }

    /**
     *
     */
    protected function validate_unique_names($data) {
        global $DB;

        $errors = array();

        $shortname = array_key_exists('shortname', $data) ? $data['shortname'] : '';
        $fullname  = array_key_exists('fullname' , $data) ? $data['fullname']  : '';

        if (!empty($shortname)) {
            if ($DB->record_exists('course', array('shortname'=>$shortname))) {
                $errors['shortname'] = get_string('shortnametakenbycourse', 'local_course');
            } else if ($DB->record_exists('course_request_u', array('shortname'=>$shortname))) {
                $errors['shortname'] = get_string('shortnametakenbyrequest', 'local_course');
            }
        }

        if (!empty($fullname)) {
            if ($DB->record_exists('course', array('fullname'=>$fullname))) {
                $errors['fullname'] = get_string('fullnametakenbycourse', 'local_course');
            } else if ($DB->record_exists('course_request_u', array('fullname'=>$fullname))) {
                $errors['fullname'] = get_string('fullnametakenbyrequest', 'local_course');
            }
        }

        return $errors;
    }

    /**
     *
     */
    protected function validate_additional_role_users($data) {

        $errors = array();

        if (! array_key_exists('additionalroleuserlist', $data)) {
            return $errors;
        }

        foreach ($data['additionalroleuserlist'] as $index => $userlist) {
            $x500s = split_x500_text_list($userlist);

            // First, ensure that entries look like internet ids.
            if (preg_grep("/^[\w-]+$/", $x500s, PREG_GREP_INVERT)) {
                $errors["additionalroleuserlist[$index]"] = get_string('internetidinvalid', 'local_course');
            } else {

                // If here, the entries at least look like they could be x500s.  Now check to
                // see that they are actual x500s.  Add the user represented by the x500 as a
                // Moodle user if not already a Moodle user.
                $invalidx500s = array();
                foreach ($x500s as $x500) {
                    if (! $this->find_moodle_user_by_x500($x500)) {
                        $invalidx500s[] = $x500;
                    }
                }
                if (!empty($invalidx500s)) {
                    $errors["additionalroleuserlist[$index]"] = get_string('x500notinldap',
                                                                           'local_course',
                                                                           implode(', ', $invalidx500s));
                }
            }
        }
        return $errors;
    }

    /**
     * Adds the user to Moodle if not already there.
     */
    public function find_moodle_user_by_x500($x500) {
        global $DB;

        $username = x500_to_moodle_username($x500);
        $userid = $DB->get_field('user', 'id', array('username' => $username));
        if (! $userid) {
            try {
                $userid = $this->_customdata['usercreator']->create_from_x500($x500);
            } catch (local_user_notinldap_exception $ex) {
                return false;
            }
        }
        return $userid;
    }

    /**
     *
     */
    protected function validate_sourcecourseurl($data) {

        $errors = array();

        $sourcecourseurl = trim($data['sourcecourseurl']);

        if (empty($sourcecourseurl)) {
            return $errors;
        }

        try {
            local_course_validate_sourcecourseurl($sourcecourseurl);
        } catch (local_course_url_parse_exception $ex) {
            $errors['sourcecourseurl'] = get_string('sourcecourseurlinvalid',
                                                    'local_course');
        } catch (local_course_invalidinstancename_exception $ex) {
            $errors['sourcecourseurl'] = get_string('sourcecourseinstanceinvalid',
                                                    'local_course');
        } catch (local_course_invalidcourseid_exception $ex) {
            $errors['sourcecourseurl'] = get_string('sourcecourseidinvalid',
                                                    'local_course',
                                                    $ex->courseid);
        }

        return $errors;
    }

}

/**
 *
 */
class local_course_request_form_nonacad extends local_course_request_form_base {

    public function definition() {
        $this->define_form_header();

        $fullnamelabel = get_string('fullnamecoursenonacad', 'local_course');
        $shortnamelabel = get_string('shortnamecoursenonacad', 'local_course');
        $this->define_course_name_elements($fullnamelabel, $shortnamelabel);

        $this->define_category_elements();

        $this->define_role_element();

        $this->define_other_role_elements();

        $this->define_sourcecourseurl_elements();

        $this->define_requestformtype_elements('nonacad');

        $this->define_reason_text_elements();

        $this->define_submission_elements_with_close_header();
    }

    /**
     *
     */
    function validation($data, $files) {

        $mform =& $this->_form;

        $errors = parent::validation($data, $files);

        $errors += $this->validate_unique_names($data);

        return $errors;
    }
}


/**
 *
 */
class local_course_request_form_acad extends local_course_request_form_base {

    public function definition() {

        $this->define_form_header();

        $this->define_category_elements();

        $this->define_role_element();

        $this->define_other_role_elements();

        $this->define_sourcecourseurl_elements();

        $this->define_requestformtype_elements('acad');

        $this->define_reason_text_elements();

        $this->define_disclaimer_elements();

        $this->define_submission_elements_with_close_header();
    }

    protected function define_role_element() {
        global $USER;

        $emplids = $this->get_primary_instructor_emplids();

        // If the current user is a primary instructor for the course,
        // don't show the role selection because the user will be assigned
        // and instructor role regardless.
        if (in_array($USER->idnumber, $emplids)) {
            $this->_form->addElement('hidden', 'yourrole', 'editingteacher');
            $this->_form->setType('yourrole', PARAM_ALPHANUM);
            return;
        }

        parent::define_role_element();
    }

    /**
     *
     */
    private function get_course_title_html_and_set_name_elements($ppsftclasses) {
        $mform =& $this->_form;

        $courserequestmanager = $this->_customdata['courserequestmanager'];
        # TODO: Refactor build_course_names_from_ppsft_classes after creating a unit test.

        $namesobj = new stdclass();
        $courserequestmanager->build_course_names_from_ppsft_classes($namesobj, $ppsftclasses);

        $mform->addElement('hidden', "fullname", $namesobj->fullname);
        $mform->setType('fullname', PARAM_TEXT);

        $mform->addElement('hidden', "shortname", $namesobj->shortname);
        $mform->setType('shortname', PARAM_TEXT);

        return html_writer::tag('div',
                                get_string('fullnamecourseacad', 'local_course')
                                  . ': ' . $namesobj->fullname,
                                array());
    }

    /**
     *
     */
    private function get_classes_html($ppsftclasses) {
        $terms = enrol_get_plugin('umnauto')->get_term_map();
        $autoenrolclasses = get_string('autoenrolclasses', 'local_course');

        $rowarray = array();
        foreach ($ppsftclasses as $class) {
            $rowarray[] = new html_table_row(array(
                  new html_table_cell($autoenrolclasses),
                  new html_table_cell(attempt_mapping($class->term, $terms)),
                  new html_table_cell($class->subject),
                  new html_table_cell($class->catalog_nbr.'-'.$class->class_section),
                  new html_table_cell($class->ssr_component),
                  new html_table_cell($class->class_nbr),
                  new html_table_cell($class->institution)
                  ));

            // Display label only for first row.
            $autoenrolclasses = '&nbsp;';
        }

        $classestable = new html_table();
        $classestable->data = $rowarray;
        $classestable->id = 'requestheaderclasses';
        return html_writer::table($classestable);
    }

    private function get_primary_instructor_emplids() {

        if (! array_key_exists('primaryinstructoremplids', $this->_customdata)) {
            throw new Exception('primaryinstructoremplids required in form customdata');
        }

        return $this->_customdata['primaryinstructoremplids'];
    }

    private function get_primary_instructors_html() {
        global $DB;

        $usercreator = $this->_customdata['usercreator'];

        // First, we add any missing primary instructors to the Moodle user table.
        $emplids = $this->get_primary_instructor_emplids();
        $usercreator->create_from_missing_emplids($emplids);

        $users = $DB->get_records_list('user',
                                       'idnumber',
                                       $emplids,
                                       'lastname,firstname',
                                       'id,lastname,firstname');

        $usernames = array_map(function ($u) { return "$u->firstname $u->lastname"; },
                               $users);
        $usernames = implode(', ', $usernames);

        $html = html_writer::tag('div',
                                 get_string('primaryinstructors', 'local_course')
                                   . $usernames,
                                 array());

        $html .= html_writer::tag('div',
                                  get_string('primaryinstructoremailnote', 'local_course'),
                                  array('class'=>'helpnote'));

        return $html;
    }

    /**
     *
     */
    protected function define_form_header() {
        $mform =& $this->_form;

        $ppsftclasses = $this->_customdata['ppsftclasses'];

        $triplets = $this->_customdata['triplets'];

        // Submit back the triplets using hidden fields.
        foreach ($triplets as $tripletstring=>$ignore) {
            $mform->addElement('hidden', "classes[$tripletstring]", $tripletstring);
            $this->_form->setType("classes[$tripletstring]", PARAM_ALPHANUM);
        }

        $coursetitlehtml = $this->get_course_title_html_and_set_name_elements($ppsftclasses);

        $classeshtml = $this->get_classes_html($ppsftclasses);
        $primaryinstructorshtml = $this->get_primary_instructors_html();

        $headerhtml = html_writer::tag('div',
                                       $coursetitlehtml
                                         . $classeshtml
                                         . $primaryinstructorshtml,
                                       array('id'=>'acadrequestformheader'));

        $mform->addElement('html', $headerhtml);

        $mform->addElement('header', 'requestformfieldset');
    }

    private function define_disclaimer_elements() {
        $mform =& $this->_form;

        $disclaimerhead = html_writer::tag('div',
                                           get_string('disclaimerhead',
                                                      'local_course'),
                                           array('class'=>'disclaimerhead'));

        $disclaimertext = html_writer::tag('div',
                                           get_string('disclaimertext',
                                                      'local_course'),
                                           array('class'=>'disclaimertext'));

        $disclaimerhtml = html_writer::tag('div',
                                           $disclaimerhead.$disclaimertext,
                                           array('id'=>'acadrequestdisclaimer',
                                                 'class'=>'elementwrapper'));

        $mform->addElement('html', $disclaimerhtml);
    }
}

