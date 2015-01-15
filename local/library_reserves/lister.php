<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * library/utility for the local Library Reserves plugin
 *
 * @package   local
 * @subpackage library_reserves
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright University of Minnesota 2013
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/local/library_reserves/lib.php');

$course_id  = required_param('course_id', PARAM_INT);

$course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);

$urlparams = array('course_id' => $course->id);
$PAGE->set_url('/local/library_reserves/lister.php', $urlparams); // Defined here to avoid notices on errors etc

$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);

$PAGE->requires->css('/local/library_reserves/styles.css');
$PAGE->set_title(get_string('library_reserves_course', 'local_library_reserves', $course->shortname));
$PAGE->set_heading(get_string('library_reserves_course', 'local_library_reserves', $course->shortname));
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('library_reserves', 'local_library_reserves'));

echo $OUTPUT->header();
echo $OUTPUT->box_start();

// display the reserve list

echo html_writer::start_tag('div', array('class'=>'library-reserves-ctn'));

// get the associated PPSFT classes together with the ppsft_class_enrol records
// important: the criteria of ppsft_class_enrol is in LEFT JOIN instead of WHERE
// so that we can tell if the course has any class associated at all
$query = 'SELECT DISTINCT
                 enrol_umnauto_classes.ppsftclassid AS ppsftclassid,
                 ppsft_classes.institution AS institution,
                 ppsft_classes.subject AS subject,
                 ppsft_classes.catalog_nbr AS catalog_nbr,
                 ppsft_classes.section AS section,
                 ppsft_class_enrol.status AS enrol_status
          FROM {enrol_umnauto_classes} enrol_umnauto_classes
               INNER JOIN {ppsft_classes} ppsft_classes ON ppsft_classes.id = enrol_umnauto_classes.ppsftclassid
               INNER JOIN {enrol} enrol ON enrol.id = enrol_umnauto_classes.enrolid
               LEFT JOIN {ppsft_class_enrol} ppsft_class_enrol
                   ON ppsft_class_enrol.ppsftclassid = enrol_umnauto_classes.ppsftclassid AND
                      ppsft_class_enrol.userid = :user_id AND
                      ppsft_class_enrol.status = :status_enrolled
          WHERE enrol.courseid = :course_id
          ORDER BY institution, subject, catalog_nbr, section';

$rs = $DB->get_recordset_sql($query, array('course_id'               => $course->id,
                                                  'user_id'          => $USER->id,
                                                  'status_enrolled'  => 'E'));

$classes = array();
foreach ($rs as $r) {
    if (!isset($classes[$r->ppsftclassid])) {
        $classes[$r->ppsftclassid] = array(
            'info' => array('institution' => $r->institution,
                            'subject'     => $r->subject,
                            'catalog_nbr' => $r->catalog_nbr,
                            'section'     => $r->section),
            'enrol' => array()
        );
    }

    if (!is_null($r->enrol_status)) {
        $classes[$r->ppsftclassid]['enrol'][] = $r->enrol_status;
    }
}
$rs->close();

if (count($classes) == 0) {
    echo get_string('no_class', 'local_library_reserves');
}
else {
    $can_view_all = has_capability('local/library_reserves:viewallresources', $context);

    // get the list of allowed classes
    $viewable_class_ids = array();
    foreach ($classes as $class_id => $class) {
        // only print a class if has capability viewallresources or enrolled in class as student
        if ($can_view_all || count($class['enrol']) > 0) {
            $viewable_class_ids[] = $class_id;
        }
    }

    if (count($viewable_class_ids) == 0) {
        echo get_string('no_section_enrolled', 'local_library_reserves');
    }
    else {
        $content = '';    // buffer the ouput

        // print the reserve list one class at a time
        $has_multiple_sections = count($viewable_class_ids) > 1;
        $has_reserve_at_all = false;

        foreach ($viewable_class_ids as $class_id) {
            // print the section title if needed
            if ($has_multiple_sections) {
                $info = $classes[$class_id]['info'];
                $section_title = "{$info['institution']} {$info['subject']} {$info['catalog_nbr']}-{$info['section']}";
                $content .= html_writer::tag('h2', $section_title, array('class' => 'library-reserves-section-title'));
            }

            $result = print_preserves($class_id);
            $has_reserve = $result['status'];

            if ($has_reserve == 'unknown') {
                $syncer = new library_reserves_syncer();
                $ppsft_class = $DB->get_record('ppsft_classes', array('id' => $class_id));
                $syncer->update_class($ppsft_class);

                // try again
                $result = print_preserves($class_id);
                $has_reserve = $result['status'];

                if ($has_reserve == 'yes') {
                    $has_reserve_at_all = true;
                    $content .= $result['content'];
                }
            }
            else if ($has_reserve == 'yes') {
                $has_reserve_at_all = true;
                $content .= $result['content'];
            }

            // close the section
            if ($has_multiple_sections) {
                $content .= html_writer::tag('div', '', array('class' => 'library-reserves-section-end'));
            }
        }

        if ($has_reserve_at_all == false) {
            if ($can_view_all) {
                echo get_string('no_reserve', 'local_library_reserves');    // instructor message
            }
            else {
                echo get_string('no_enrolled_reserve', 'local_library_reserves'); // student message
            }
        }
        else {
            // print the view-all message
            if ($can_view_all && $has_multiple_sections) {
                echo html_writer::tag('div', get_string('view_all_explain', 'local_library_reserves'),
                        array('class' => 'library-reserves-view-all-explain'));
            }

            // print the resource list
            echo $content;
        }
    }
}

echo html_writer::end_tag('div');


echo $OUTPUT->box_end();
echo $OUTPUT->footer();


/**
 * retrieve and display the reserves
 *
 * @param int $class_id
 * @param array $options
 * @return array('status'    => string unknown|yes|no,
 *               'content'   => string)
 */
function print_preserves($class_id) {
    global $DB;

    //STRY0010333 20140627 mart0969 - Add notes to reserve listing
    // retrieve the list of reserves
    $query = 'SELECT DISTINCT
                  resource.resource_id AS resource__id,
                  resource.title AS resource__title,
                  resource.resource_type AS resource__type,
                  resource.url AS resource__url,
                  resource.note AS resource__note,
                  reserve.sort_number AS reserve__sort_number
              FROM {local_library_reserve} reserve
                   LEFT JOIN {local_library_resource} resource ON
                       reserve.resource_id = resource.resource_id
              WHERE reserve.ppsft_class_id = :class_id
              ORDER BY reserve__sort_number ASC,
                       resource__title ASC';

    $rs = $DB->get_recordset_sql($query, array('class_id' => $class_id));

    // process the list
    $has_reserve = 'unknown';

    $content = '';

    $ul_started = false;    // keep track of the wrapping UL for the item to be in LI
    foreach ($rs as $r) {
        if (is_null($r->resource__id)) {
            // only set to "no" if it's still "unknown"
            if ($has_reserve == 'unknown') {
                $has_reserve = 'no';
            }
        }
        else{

            $has_reserve = 'yes';

            switch (strtoupper($r->resource__type)) {
                case 'HEADING':
                    // close UL tag as needed
                    if ($ul_started == true) {
                        $content .= html_writer::end_tag('ul');
                        $ul_started  = false;
                    }

                    $content .= html_writer::tag('p', $r->resource__title, array('class' => 'library-reserves-heading'));
                    break;

                case 'ITEM':
                default:
                    // start UL tag as needed
                    if ($ul_started == false) {
                        $content .= html_writer::start_tag('ul', array('class' => 'library-reserves-items'));
                        $ul_started  = true;
                    }
                    $content .= html_writer::start_tag('li', array('class' => 'library-reserves-item'));
                    $content .= html_writer::tag('a', $r->resource__title, array('href' => $r->resource__url, 'target' => '_blank'));
                    //STRY0010333 20140627 mart0969 - Add notes to reserve listing
                    if ($r->resource__note != 'NULL') {
                        $content .= html_writer::tag('p', $r->resource__note, array('class' => 'library-reserves-note'));
                    }
                    $content .= html_writer::end_tag('li');
            }
        }
    }
    $rs->close();

    // close UL tag as needed
    if ($ul_started == true) {
        $content .= html_writer::end_tag('ul');
    }

    return array('status'    => $has_reserve,
                 'content'   => $content);
}
