<?php

// The view defined here to get it off manage.php.  Not using
// moodleform because the page is too complex to be a good
// fit.

# TODO: Display list of instructor courses.  Include button to refresh.
# TODO: Display list of other associated courses even if instructor
#       is not ppsft instructor.

class enrol_umnauto_manage_view {

    private $errors;
    private $instance;
    private $course;
    private $ppsft;   // ppsft_data_adapter
    private $umnauto;   // the umnauto enrol plugin

    private $classes = null;    // cache the list of associated classes

    public function __construct($instance, $course, $ppsft) {
        global $SESSION;

        $this->instance = $instance;
        $this->course = $course;
        $this->ppsft = $ppsft;
        $this->umnauto = enrol_get_plugin('umnauto');

        # TODO: Consider moving this to manage.php so that all the
        #       SESSION references are in one file. Then pass errors
        #       to the constructor.
        if (!empty($SESSION->enrol_umnauto)) {
            $enrol_umnauto_data = $SESSION->enrol_umnauto;
            unset($SESSION->enrol_umnauto);

            $this->errors = $enrol_umnauto_data['errors'];
        }
    }

    /**
     *
     */
    public function render() {
        global $PAGE;
        global $OUTPUT;

        $PAGE->set_pagelayout('admin');
        $strtitle = get_string('managepagetitle', 'enrol_umnauto');
        $PAGE->set_title($strtitle);
        $PAGE->set_heading($this->course->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->heading($strtitle);

        if ($this->errors) {
            $errorstext = implode('<br />', $this->errors);

            echo $OUTPUT->box($errorstext, 'generalbox', 'errorstext');
        }

        echo $OUTPUT->box(get_string('instructionstext', 'enrol_umnauto'),
                          'generalbox',
                          'instructionstext');

        echo html_writer::start_tag('div', array('id'=>'autoenrolltables'));

        echo html_writer::start_tag('form', array('method'=>'post'));
        echo html_writer::empty_tag('input',
                                    array('type'=>'hidden',
                                          'name'=>'sesskey',
                                          'value'=>sesskey()));
        echo html_writer::empty_tag('input',
                                    array('type'=>'hidden',
                                          'name'=>'enrolid',
                                          'value'=>$this->instance->id));

        echo html_writer::tag('div',
                              get_string('classtablecaption', 'enrol_umnauto'),
                              array('class'=>'tablecaption'));

        $class_table = $this->build_class_table();
        echo html_writer::table($class_table);

        echo html_writer::start_tag('div', array('class'=>'autoenrollupdate'));
        echo html_writer::empty_tag('input',
                                    array('type'=>'submit',
                                          'name'=>'update_course_enrollment',
                                          'value'=>'Update course enrollment'));
        echo html_writer::end_tag('div');

        echo html_writer::end_tag('form');

        $student_tables = $this->build_student_tables();

        foreach ($student_tables as $class) {
            echo html_writer::tag('div',
                                  $class['title'],
                                  array('class'=>'studenttabletitle'));
            echo html_writer::table($class['table']);
        }

        echo html_writer::end_tag('div');

        echo $OUTPUT->footer();
    }


    /**
     * build the student tables from enrollment
     *
     * @return array of array('title'	=> {string},
     * 						  'table'	=> {html_table})
     */
    private function build_student_tables() {
        // get the list of classes
        if (is_null($this->classes)) {
            $this->classes = $this->get_class_list();
        }

        $data = array();    // store the list of enrollments separated into classes (indexed by class IDs)

        // get the list of associated classes as placeholders
        foreach ($this->classes as $class) {
            if ( !isset($class->enrolid) ) {
                continue;    // skip non-linked classs
            }

            if ( !isset($data[$class->id]) ) {
                $data[$class->id] = array('info'        => $class,
                                          'students'    => array());
            }
        }

        // sort the enrollment into the placeholders
        $all_enrollments = $this->get_course_autoenrollments();

        foreach ($all_enrollments as $enrollment) {
            $data[$enrollment->class_id]['students'][] = $enrollment;
        }

        // build the tables
        $table_list = array();

        foreach ($data as $class_id => $class_data) {

            $table = new html_table();

            $table->head = array(get_string('idnumbercolumnheader'      , 'enrol_umnauto'),
                                 get_string('lastnamecolumnheader'      , 'enrol_umnauto'),
                                 get_string('firstnamecolumnheader'     , 'enrol_umnauto'),
                                 get_string('internetidcolumnheader'    , 'enrol_umnauto'),
                                 get_string('psenrolstatuscolumnheader' , 'enrol_umnauto'),
                                 get_string('mdlenrolstatuscolumnheader', 'enrol_umnauto'),
                                 get_string('psenroldtcolumnheader'     , 'enrol_umnauto'),
                                 get_string('psdropdtcolumnheader'      , 'enrol_umnauto'));

            // For styling.
            $table->colclasses[4] = 'ppsftstatus';
            $table->colclasses[5] = 'mdlstatus';

            $table->rowclasses = array();

            if (count($class_data['students']) == 0) {
                $cell = new html_table_cell();
                $cell->text = get_string('studenttableempty', 'enrol_umnauto');
                $cell->colspan = 8;
                $cell->attributes = array('class' => 'empty_enrollment');

                $row = new html_table_row();
                $row->cells[] = $cell;

                $table->data[] = $row;
            }
            else {
                foreach ($class_data['students'] as $row => $enrollment) {
                    switch ($enrollment->status) {
                        case 'E':
                            $ppsft_status = 'Enrolled';
                            break;
                        case 'D':
                            $ppsft_status = 'Dropped';
                            $table->rowclasses[$row] = 'ppsftdropped';
                            break;
                        case 'H':
                            $ppsft_status = 'Withdrawn';
                            $table->rowclasses[$row] = 'ppsftwithdrawn';
                            break;
                    }

                    if ($enrollment->mdlenrollee) {
                        $mdl_status = 'Yes';
                    } else {
                        $mdl_status = 'No';
                        if (array_key_exists($row, $table->rowclasses)) {
                            $table->rowclasses[$row] .= ' mdlnotenrolled';
                        } else {
                            $table->rowclasses[$row] = 'mdlnotenrolled';
                        }
                    }

                    # TODO: Do we, or should we, have a lib function for getting
                    #       UMN internet id from username?
                    if ($atpos = strpos($enrollment->username, '@umn.edu')) {
                        $internetid = substr($enrollment->username, 0, $atpos);
                    } else {
                        $internetid = $enrollment->username;
                    }

                    $table->data[] = array($enrollment->idnumber,
                                           $enrollment->lastname,
                                           $enrollment->firstname,
                                           $internetid,
                                           $ppsft_status,
                                           $mdl_status,
                                           $enrollment->add_date,
                                           $enrollment->drop_date);
                }
            }

            // compose the table title
            $title = get_string('studenttabletitle', 'enrol_umnauto', array(
                            'subject'	    => $class_data['info']->subject,
                            'catalog_nbr'	=> $class_data['info']->catalog_nbr,
                            'section'	    => $class_data['info']->section,
                            'class_nbr'	    => $class_data['info']->class_nbr,
                            'institution'	=> $class_data['info']->institution
            ));

            $table_list[] = array('title'    => $title,
                                  'table'    => $table);
        }

        return $table_list;
    }

    /**
     *
     */
    private function build_class_table() {

        $table = new html_table();  // See http://docs.moodle.org/en/Development:Deprecated_functions_in_2.0#print_table_.280.29
        #$table->set_classes('classtable');
        $table->head = array(get_string('linkedcolumnheader'        , 'enrol_umnauto'),
                             get_string('institutioncolumnheader'   , 'enrol_umnauto'),
                             get_string('termcolumnheader'          , 'enrol_umnauto'),
                             get_string('psclassnumbercolumnheader' , 'enrol_umnauto'),
                             get_string('psclasssectioncolumnheader', 'enrol_umnauto'),
                             get_string('titlecolumnheader'         , 'enrol_umnauto'),
                             get_string('studentcountcolumnheader'  , 'enrol_umnauto'),
                             '&nbsp;');

        $table->colclasses[6] = 'enrolcount';

        $default_term = $this->umnauto->get_config('default_term', '');

        $terms = $this->umnauto->get_term_map();

        if (is_null($this->classes)) {
            $this->classes = $this->get_class_list();
        }

        $classes = $this->classes;

        $enrl_tot_sum = 0;

        $context = context_course::instance($this->course->id, MUST_EXIST);
        $canenrolanystudent = has_capability('enrol/umnauto:enrolanystudent', $context);

        foreach ($classes as $class) {

            if (isset($class->enrolid)) {

                // Disable the remove button unless the user is an instructor for this
                // PeopleSoft class or has special powers.
                $noremove = ($class->user_is_instr or $canenrolanystudent) ? null : true;

                $linked = 'Yes';
                $action = html_writer::empty_tag('input',
                                                 array('type' =>'submit',
                                                       'name' =>'remove_'.$class->ppsftclassid,
                                                       'value'=>'Remove',
                                                       'disabled'=>$noremove));
            } else {
                $linked = 'No';
                $action = html_writer::empty_tag('input',
                                                 array('type' =>'submit',
                                                       'name' =>'add_'.$class->triplet,
                                                       'value'=>'Add'));
            }

            // Show term code instead of term name if code not mapped. Unlikely.
            $term = array_key_exists($class->term, $terms) ? $terms[$class->term]
                                                           : $class->term;

            // These are the data columns corresponding to the $table->head assignment above.
            $table->data[] = array($linked,
                                   ppsft_institution_name($class->institution),
                                   $term,
                                   $class->class_nbr,
                                   $class->subject.' '.$class->catalog_nbr.' '.$class->section,
                                   $class->descr,
                                   $class->enrl_tot,
                                   $action);

            $enrl_tot_sum += $class->enrl_tot;
        }

        // If the user as appropriate permissions (typically, support or admin), a
        // table row appears that allows linking any triplet.
        if ($canenrolanystudent) {

            $table->data[] = array('Other class:',
                                   html_writer::select(ppsft_institutions(),
                                                       'institution',
                                                       'UMNTC',
                                                       ''),
                                   html_writer::select($this->umnauto->get_term_map(true),
                                                       'term',
                                                       $default_term,
                                                       ''),
                                   html_writer::empty_tag('input',
                                                          array('type'=>'text',
                                                                'name'=>'classnbr',
                                                                'size'=>'8')),
                                   '&nbsp;',
                                   '&nbsp;',
                                   "Total: $enrl_tot_sum",
                                   html_writer::empty_tag('input',
                                                          array('type'=>'submit',
                                                                'name'=>'add_triplet',
                                                                'value'=>'Add')));
        }
        return $table;
    }

    private function get_course_autoenrollments() {
        global $DB;

        $catalog_section_sql = $DB->sql_concat('c.subject', "' '",
                                               'c.catalog_nbr', "' '",
                                               'c.section');

        $sql =<<<SQL
select ce.id, ce.status, ce.add_date, ce.drop_date,
       u.lastname, u.firstname, u.idnumber, u.username,
       $catalog_section_sql as catalog_section, c.class_nbr,
       mdlenrol.mdlenrollee, c.id AS class_id
from {enrol_umnauto_classes} uc
  join {ppsft_classes} c on c.id = uc.ppsftclassid
  join {ppsft_class_enrol} ce on ce.ppsftclassid = uc.ppsftclassid
  join {user} u on u.id = ce.userid

  left join (select distinct ue.userid as mdlenrollee
             from {user_enrolments} ue
               join {enrol} e on e.id = ue.enrolid
             where e.courseid = :courseid
            ) mdlenrol on mdlenrol.mdlenrollee = u.id
where uc.enrolid = :enrolid
ORDER BY catalog_section, u.lastname, u.firstname
SQL;

        // Could get to courseid through additional join, but this is probably
        // more efficient.

        $students = $DB->get_records_sql($sql,
                                         array('enrolid'  => $this->instance->id,
                                               'courseid' => $this->instance->courseid));

        return $students;
    }


    # TODO: Need to ensure that enrollment is current for already-associated
    #       classes.  Might be okay as is, actually.
    private function get_class_list() {
        global $DB, $USER;

        $triplet_sql = $DB->sql_concat('c.term', 'c.institution', 'c.class_nbr');

        // Get classes already associated.
        // The subselect involving ppsft_class_enrol
        // is for getting the enrollment total for each class.
        // The subselect involving ppsft_class_instr is to determine
        // whether the user is listed as an instructor for the ppsftclass.
        $sql =<<<SQL
select $triplet_sql as triplet, c.id,
       c.institution, c.term, c.class_nbr, c.subject, c.catalog_nbr,
       c.section, c.descr, uc.enrolid, uc.ppsftclassid,
       (select count(id) from {ppsft_class_enrol} where ppsftclassid=c.id and status='E') as enrl_tot,
       (select count(id) from {ppsft_class_instr} where ppsftclassid=c.id and userid=:userid) as user_is_instr
from {ppsft_classes} c
  join {enrol_umnauto_classes} uc on uc.ppsftclassid=c.id
where uc.enrolid = :enrolid
SQL;

        $associated_classes = $DB->get_records_sql(
                            $sql,
                            array('enrolid' => $this->instance->id,
                                  'userid'  => $USER->id));

        $instructor_classes = $this->ppsft->get_instructor_classes($USER->idnumber);

        $enabled_terms = $this->umnauto->get_enabled_terms();

        // Instructor classes should only include enabled terms. If any classes
        // in other terms are already linked, they will appear by being in
        // in $associated_classes
        $instructor_classes_filtered = array_filter($instructor_classes,
                                                    function ($class) use ($enabled_terms)
                                                      {
                                                        return in_array($class->term,
                                                                        $enabled_terms);
                                                      });

        $classes = array_merge($instructor_classes_filtered, $associated_classes);

        ksort($classes);

        return $classes;

    }
}

