<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/local/ppsft/lib.php');
require_once($CFG->dirroot.'/local/course/helpers.php');
require_once($CFG->dirroot.'/local/course/constants.php');

class ppsft_search {

/**
 * Given an array of triplet strings, returns a map of those strings
 * as keys with parsed triplet arrays (keys: term, institution, clsnbr) as values.
 */
static public function get_triplet_array_map($tripletstrings) {
    # TODO: Check against ppsft_institutions and 
    #$terms = enrol_get_plugin('umnauto')->get_term_map();
    #$institutions = ppsft_institutions();
    $tripletmap = array();

    foreach ($tripletstrings as $tripletstring) {
        if (!preg_match('/^(\d+)([A-Z]+)(\d+)$/', $tripletstring, $matches)) {
            error_log($tripletstring);
            throw new Exception("Invalid PeopleSoft class triplet");
        }
        $tripletmap[$tripletstring] = array('term'=>$matches[1],
                                            'institution'=>$matches[2],
                                            'clsnbr'=>$matches[3]);
    }
    return $tripletmap;
}

/**
 * This builds a map that includes info on existing and pending courses.
 */
static private function get_sitelink_html_map($classes) {
    global $DB;

    $tuples = array();
    foreach ($classes as $triplet=>$class) {
        $tuples[] = "( '$class->term', '$class->institution', $class->class_nbr)";
    }
    $tuplesql = implode(',', $tuples);

    $tripletconcat = $DB->sql_concat('pc.term','pc.institution','pc.class_nbr');

    $sql =<<<SQL
select $tripletconcat as triplet, count(*) as count
from mdl_ppsft_classes pc
  join mdl_course_request_classes rc on rc.ppsftclassid=pc.id
  join mdl_course_request_u r on r.id = rc.courserequestid
where (pc.term, pc.institution, pc.class_nbr) in ($tuplesql)
  and r.status < :status
group by triplet
SQL;

    $pending = $DB->get_records_sql($sql, array('status' => CRS_REQ_STATUS_CANCELED));

    $sql =<<<SQL
select $tripletconcat as triplet, count(*) as count, max(e.courseid) as courseid
from mdl_ppsft_classes pc
  join mdl_enrol_umnauto_classes ec on ec.ppsftclassid=pc.id
  join mdl_enrol e on e.id=ec.enrolid
where (pc.term, pc.institution, pc.class_nbr) in ($tuplesql)
group by triplet
SQL;

    $courses = $DB->get_records_sql($sql);

    $sitelinkhtmlmap = array();
    
    foreach ($classes as $triplet=>$class) {
        $sitelinkhtml = '';
        if (array_key_exists($triplet, $courses)) {
            if ( $courses[$triplet]->count == 1 ) {
                $sitelinkhtml = html_writer::link(
                                 new moodle_url('/course/view.php',
                                                array('id'=>$courses[$triplet]->courseid)),
                                 'Link');
            } else {
                $sitelinkhtml = 'Multiple ('.$courses[$triplet]->count.')';
            }
        }

        if (array_key_exists($triplet, $pending)) {
            $sitelinkhtml .= empty($sitelinkhtml) ? '' : '<br />';
            $sitelinkhtml .= 'Pending';
            if ($pending[$triplet]->count > 1) {
                $sitelinkhtml .= '&nbsp;('.$pending[$triplet]->count.')';
            }
        }
        $sitelinkhtmlmap[$triplet] = (empty($sitelinkhtml)) ? '&nbsp;' : $sitelinkhtml;
    }

    return $sitelinkhtmlmap;
}

static public function get_result_form($classes, $previous) {
    global $CFG;

    $terms = enrol_get_plugin('umnauto')->get_term_map();

    $sitelinkmap = static::get_sitelink_html_map($classes);

    $resulttable = new html_table();
    $resulttable->id = 'ppsftsearchresulttable';

    $resulttable->head = array('&nbsp;',
                               get_string('termcolumnheader'       , 'local_course'),
                               get_string('subjectcolumnheader'    , 'local_course'),
                               get_string('catsectioncolumnheader' , 'local_course'),
                               get_string('classtypecolumnheader'  , 'local_course'),
                               get_string('descrcolumnheader'      , 'local_course'),
                               get_string('classnbrcolumnheader'   , 'local_course'),
                               get_string('institutioncolumnheader', 'local_course'),
                               get_string('sitelinkcolumnheader'   , 'local_course')
                              );

    $rowarray = array();
    foreach ($classes as $triplet=>$class) {
        $catsection = $class->catalog_nbr.'-'.$class->class_section;

        if ($class->selected) {
            $pickcheckbox = html_writer::empty_tag('input',
                                                   array('type'=>'checkbox',
                                                         'name'=>"classes[$class->triplet]",
                                                         'checked'=>'checked'));
        } else {
            $pickcheckbox = html_writer::empty_tag('input',
                                                   array('type'=>'checkbox',
                                                         'name'=>"classes[$class->triplet]"));
        }

        $rowarray[] = new html_table_row(array(
            new html_table_cell($pickcheckbox),
            new html_table_cell(attempt_mapping($class->term, $terms)),
            new html_table_cell($class->subject),
            new html_table_cell($catsection),
            new html_table_cell($class->ssr_component),
            new html_table_cell($class->long_title),
            new html_table_cell($class->class_nbr),
            new html_table_cell($class->institution),
            new html_table_cell($sitelinkmap[$triplet])));
    }
    $resulttable->data = $rowarray;
    $resultformcomponents = html_writer::table($resulttable);

    $resultformcomponents .= html_writer::tag('div', null, array('class'=>'errormessage'));

    $previousbutton = html_writer::tag('button',
                                        get_string('previous'),
                                        array('onclick' => "window.location.href='$previous'; return false;",
                                               'name' => 'previous'));

    $nextbutton = html_writer::tag('button',
                                   get_string('next'),
                                   array('name' => 'next', 'value' => 1, 'class' => 'form-submit'));

    $crtypehidden = html_writer::empty_tag('input',
                                           array('type'=>'hidden',
                                                 'name'=>'crtype',
                                                 'value'=>'acad'));

    $buttondiv = html_writer::tag('div',
                                  $previousbutton.$nextbutton.$crtypehidden,
                                  array('class' => 'prevnextbuttons'));

    $resultformcomponents .= $buttondiv;

    $form = html_writer::tag('form',
                             $resultformcomponents,
                             array('method'=>'get',
                                   'action'=>$CFG->wwwroot . "/local/course/request.php",
                                   'id' => 'classselectform'));

    return html_writer::tag('div',
                            $form,
                            array('id' => 'classselectformdiv'));
}


/**
 * $myterm could be set when returning via a Previous button.
 */
static public function get_myclass_search_form($userid, $defaultterm=null) {

    // ppsftsearch.js must follow expected naming conventions

    // We are using a table for this non-tabular layout only because the other
    // search boxes do.  Being consistent in this respect will make it much easier
    // to make the appearance consistent.

    $theadcontents = html_writer::tag('tr',
        html_writer::tag('th', get_string('termselecthdr', 'local_course')));
    $thead = html_writer::tag('thead', $theadcontents);

    $tbodycontents = html_writer::tag('tr',
        html_writer::tag('td', static::get_term_select($defaultterm)),
        array('class'=>'lastrow'));
    $tbody = html_writer::tag('tbody', $tbodycontents);

    $table = html_writer::tag('table', $thead.$tbody, array('id'=>'myclassestable'));

    $formcomponents = $table;

    // the search button
    $searchbutton = html_writer::empty_tag('input',
                                           array('type'=>'submit',
                                                 'value'=>'Search',
                                                 'class'=>'form-submit'));
    $formcomponents .= html_writer::tag('div',
                                        $searchbutton,
                                        array('class'=>'searchbuttondiv'));

    $formcomponents .= html_writer::empty_tag('input',
                                               array('type'=>'hidden',
                                                     'name'=>'type',
                                                     'value'=>'my'));

    $formcomponents .= html_writer::empty_tag('input',
                                               array('type'=>'hidden',
                                                     'name'=>'userid',
                                                     'value'=>$userid));

    return html_writer::tag('form',
                            $formcomponents,
                            array('method'=>'get',
                                  'action'=>'ppsftsearchresult.php'));
}

static private function get_by_subject_table() {

    $theadcontents = html_writer::tag('tr',
        html_writer::tag('th', get_string('termselecthdr', 'local_course'))
       .html_writer::tag('th', get_string('institutionselecthdr', 'local_course'))
       .html_writer::tag('th', get_string('subjecttexthdr', 'local_course'))
       .html_writer::tag('th', get_string('catalogtexthdr', 'local_course')));
    $thead = html_writer::tag('thead', $theadcontents);

    $tfootcontents = html_writer::tag('tr',
        html_writer::tag('td', '&nbsp;')
       .html_writer::tag('td', '&nbsp;')
       .html_writer::tag('td', get_string('subjecthelpnote', 'local_course'))
       .html_writer::tag('td', get_string('catalognumberhelpnote', 'local_course')));
    $tfoot = html_writer::tag('tfoot', $tfootcontents);

    $tbodycontents = html_writer::tag('tr',
         html_writer::tag('td', static::get_term_select())
        .html_writer::tag('td', static::get_institution_select())
        .html_writer::tag('td', static::get_subject_text_input())
        .html_writer::tag('td', static::get_catalog_text_input()),
        array('class'=>'lastrow'));
    $tbody = html_writer::tag('tbody', $tbodycontents);

    $table = html_writer::tag('table', $thead.$tfoot.$tbody, array('id'=>'subjecttable'));

    return $table;
}

static public function get_by_subject_form() {
    global $OUTPUT;

    // ppsftsearch.js must follow expected naming conventions

    $formcomponents = static::get_by_subject_table();

    $formcomponents .= html_writer::tag('div', null, array('class'=>'errormessage'));

    $plusimg = '<img src="'.$OUTPUT->pix_url('green_plus', 'local_course').'"/>';
    $formcomponents .= html_writer::tag('div',
                                        html_writer::tag('span',
                                                         $plusimg.get_string('addanotherrow', 'local_course')),
                                        array('id'=>'addanothersubjectdiv',
                                              'class'=>'addrowsorlookup addanotherrow'));

    // the search button
    $searchbutton = html_writer::empty_tag('input',
                                           array('type'=>'submit',
                                                 'value'=>'Search',
                                                 'class'=>'form-submit'));
    $formcomponents .= html_writer::tag('div',
                                        $searchbutton,
                                        array('class'=>'searchbuttondiv'));

    $formcomponents .= html_writer::empty_tag('input',
                                               array('type'=>'hidden',
                                                     'name'=>'type',
                                                     'value'=>'subj'));

    return html_writer::tag('form',
                            $formcomponents,
                            array('method'=>'get',
                                  'action'=>'ppsftsearchresult.php',
                                  'id'    =>'subjectform'));
}

static private function get_by_number_table() {

    $theadcontents = html_writer::tag('tr',
        html_writer::tag('th', get_string('termselecthdr', 'local_course'))
       .html_writer::tag('th', get_string('institutionselecthdr', 'local_course'))
       .html_writer::tag('th', get_string('classnumbertexthdr', 'local_course')));
    $thead = html_writer::tag('thead', $theadcontents);

    $tfootcontents = html_writer::tag('tr',
        html_writer::tag('td', '&nbsp;')
       .html_writer::tag('td', '&nbsp;')
       .html_writer::tag('td', get_string('classnumberhelpnote', 'local_course')));
    $tfoot = html_writer::tag('tfoot', $tfootcontents);

    $tbodycontents = html_writer::tag('tr',
         html_writer::tag('td', static::get_term_select())
        .html_writer::tag('td', static::get_institution_select())
        .html_writer::tag('td', static::get_classnumber_text_input()),
        array('class'=>'lastrow'));
    $tbody = html_writer::tag('tbody', $tbodycontents);

    $table = html_writer::tag('table', $thead.$tfoot.$tbody, array('id'=>'classnumbertable'));

    return $table;
}

static public function get_by_number_form() {
    global $OUTPUT;

    // ppsftsearch.js must follow expected naming conventions

    $formcomponents = static::get_by_number_table();

    $formcomponents .= html_writer::tag('div', null, array('class'=>'errormessage'));

    // Add another row functionality
    $plusimg = '<img src="'.$OUTPUT->pix_url('green_plus', 'local_course').'"/>';
    $formcomponents .= html_writer::tag('div',
                                        html_writer::tag('span',
                                                         $plusimg.get_string('addanotherrow', 'local_course')),
                                        array('id'=>'addanothernumberdiv',
                                              'class'=>'addrowsorlookup addanotherrow'));

    // Lookup class numbers functionality
    $lookupimg = '<img src="'.$OUTPUT->pix_url('find', 'local_course').'"/>';

    # TODO: Consider whether we can guess the user's campus from email address or
    #       some other data element, perhaps from LDAP or ppsft.
    $formcomponents .= html_writer::tag('div',
                                        html_writer::tag('span',
                                                         $lookupimg.get_string('lookupclassnumbers',
                                                                               'local_course',
                                                                               'UMNTC')),
                                        array('id'=>'lookupclassnumberdiv',
                                              'class'=>'addrowsorlookup'));

    // the search button
    $searchbutton = html_writer::empty_tag('input',
                                           array('type'=>'submit',
                                                 'value'=>'Search',
                                                 'class'=>'form-submit'));
    $formcomponents .= html_writer::tag('div',
                                        $searchbutton,
                                        array('class'=>'searchbuttondiv'));

    $formcomponents .= html_writer::empty_tag('input',
                                              array('type'=>'hidden',
                                                    'name'=>'type',
                                                    'value'=>'clsnbr'));

    return html_writer::tag('form',
                            $formcomponents,
                            array('method'=>'get',
                                  'action'=>'ppsftsearchresult.php',
                                  'id'    =>'classnumberform'));
}

/**
 *
 */
static private function get_subject_text_input() {

    $subjecttext = html_writer::empty_tag('input',
                                          array('type'=>'text',
                                                'name'=>'s[0][subject]',
                                                'class'=>'subject',
                                                'maxlength'=>4));
    return $subjecttext;
}

/**
 *
 */
static private function get_catalog_text_input() {

    $catalogtext = html_writer::empty_tag('input',
                                          array('type'=>'text',
                                                'name'=>'s[0][catalog]',
                                                'class'=>'catalog',
                                                'maxlength'=>50));
    return $catalogtext;
}

/**
 *
 */
static private function get_classnumber_text_input() {

    $classnumbertext = html_writer::empty_tag('input',
                                              array('type'=>'text',
                                                    'name'=>'s[0][clsnbr]',
                                                    'class'=>'classnumber',
                                                    'maxlength'=>6));
    return $classnumbertext;
}

/**
 *
 */
static private function get_term_select($defaultterm=null) {
    $enabled_terms = enrol_get_plugin('umnauto')->get_term_map(true);
    $options = html_writer::tag('option', '-- Select one --', array('value' => ''));

    foreach ($enabled_terms as $termcode => $termname) {
        if ($termcode == $defaultterm) {
            $options .= html_writer::tag('option',
                                         $termname,
                                         array('value' => $termcode, 'selected'=>'selected'));
        } else {
            $options .= html_writer::tag('option',
                                         $termname,
                                         array('value' => $termcode));
        }
    }

    return html_writer::tag('select', $options, array('name'=>'s[0][term]',
                                                      'class'=>'term'));
}

/**
 *
 */
static private function get_institution_select() {
    $institutions = ppsft_institutions();
    $options = html_writer::tag('option', '-- Select one --', array('value' => ''));

    foreach ($institutions as $code => $name) {
        $options .= html_writer::tag('option', $name, array('value' => $code));
    }

    return html_writer::tag('select', $options, array('name'=>'s[0][institution]',
                                                      'class'=>'institution'));
}

/**
 * Intended for extracting the search parameters from the search-by-subject
 * form and the search-by-class-number form.
 */
public static function get_query_string_param_array() {
    global $_GET;

    $params = array_key_exists('s', $_GET) ? $_GET['s'] : null;
    $params = clean_param_array($params, PARAM_NOTAGS, true);

    // Use rows with at least one value set. Remove rows with trimmed total strlen == 0.
    $params = array_filter($params, function($sp) {return strlen(trim(implode($sp)));});

    // Re-index the array.
    $params = array_values($params);

    return $params;
}

public static function get_classes_from_search_query_string() {
    global $_GET, $DB, $SESSION;

    $ppsftdataadapter = ppsft_get_adapter();

    $classes = array();

    $type = $_GET['type'];
    if ('my' == $type) {
        // search for user's classes as instructor
        $term = $_GET['s'][0]['term'];
        $userid = $_GET['userid'];
        $emplid = $DB->get_field('user', 'idnumber', array('id'=>$userid), $strictness=MUST_EXIST);
        $classes = $ppsftdataadapter->get_classes_by_instructor_and_term($emplid, $term);
    } else {

        $searchparams = static::get_query_string_param_array();
        if (! empty($searchparams)) {
            $classes = $ppsftdataadapter->get_classes_by_triplets_or_catalog($searchparams);
        }
    }

     // Mark as selected if session information indicates that the checkbox was selected.
    foreach ($classes as $triplet => $class) {
        if (isset($SESSION->courserequest_selectedppsft)
            and array_key_exists($triplet, $SESSION->courserequest_selectedppsft))
        {
            $class->selected = true;
        } else {
            $class->selected = false;
        }
    }
    return $classes;
}

}
