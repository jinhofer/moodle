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
 * Provides the interface for overall authoring of lessons
 *
 * @package    mod
 * @subpackage lesson
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/lesson/locallib.php');





/**
 * expose some of the methods of lesson
 *
 */
class lesson_wrapper extends lesson {

    public function get_first_page_id() {
        try {
            return parent::get_firstpageid();
        }
        catch(Exception $e) {
            return null;
        }
    }
}


/**
 * Helper class to extract data structure of a lesson
 * and export into different formats
 *
 */
class lesson_structure_extractor {

    /**
     * get the data structure of a lesson from the DB
     * @param int $lesson_id
     * @param array
     */
    public function get_lesson_structure($lesson_id) {
        global $DB;

        $lesson_record = $DB->get_record('lesson', array('id' => $lesson_id));
        $lesson = new lesson_wrapper($lesson_record);

        $manager  = lesson_page_type_manager::get($lesson);

        // get the pages and answers from DB
        $pages = $DB->get_records('lesson_pages', array('lessonid' => $lesson_id), 'id ASC');
        $answers = $DB->get_records('lesson_answers', array('lessonid' => $lesson_id));

        // sort them into the output structure
        $out = array();
        foreach ($pages as $page_id => $page) {
            $out[$page_id] = array(
                'id'         => (string)$page_id,
                'qtype'      => (string)$page->qtype,
                'title'      => $page->title,
                'prevpageid' => $page->prevpageid,
                'nextpageid' => $page->nextpageid,
                'answers'    => array());
        }

        foreach ($answers as $answer_id => $answer) {
            // skip if we don't have its question
            if (!isset($out[$answer->pageid])) {
                continue;
            }

            switch ($out[$answer->pageid]['qtype']) {
                case LESSON_PAGE_MATCHING:
                    if ($answer->jumpto == '0') {
                        break;
                    }
                    // flow through
                case LESSON_PAGE_TRUEFALSE:
                case LESSON_PAGE_MULTICHOICE:
                case LESSON_PAGE_BRANCHTABLE:
                default:
                    $tmp_ans = array(
                        'answer'        => $answer->answer,
                        'response'      => $answer->response,
                        'score'         => $answer->score,
                        'jumpto'        => (string)$answer->jumpto
                    );

                    $out[$answer->pageid]['answers'][$answer_id] = $tmp_ans;
                    break;
            }
        }

        // add lesson information to output
        $out = array('pages' => $out);

        $out['lesson'] = array(
                'name'            => $lesson_record->name,
                'firstpageid'     => $lesson->get_first_page_id(),
                'page_count'      => count($out['pages']));

        return $out;
    }


    /**
     * return the lesson structure in JSON for
     * the flow viewer
     *
     * @param int $lesson_id
     * @return array
     *
     */
    public function get_lesson_for_chart($lesson_id) {
        $lesson_struct = $this->get_lesson_structure($lesson_id);

        foreach ($lesson_struct['pages'] as $page_id => $page) {
            foreach ($page['answers'] as $answer_id => $answer) {
                switch ($answer['jumpto']) {
                    case LESSON_THISPAGE:
                        $answer['jumpto'] = (string)$page_id;
                        break;

                    case LESSON_UNSEENPAGE:
                        $answer['jumpto'] = 'UNSEENPAGE';
                        break;

                    case LESSON_UNANSWEREDPAGE:
                        $answer['jumpto'] = 'UNANSWEREDPAGE';
                        break;

                    case LESSON_NEXTPAGE:
                        if ($page['nextpageid'] == 0) {
                            $answer['jumpto'] = 'EOL';
                        }
                        else {
                            $answer['jumpto'] = (string)$page['nextpageid'];
                        }
                        break;

                    case LESSON_EOL:
                        $answer['jumpto'] = 'EOL';
                        break;

                    case LESSON_UNSEENBRANCHPAGE:
                        $answer['jumpto'] = 'UNSEENBRANCHPAGE';
                        break;

                    case LESSON_PREVIOUSPAGE:
                        if ($page['prevpageid'] == '0') {
                            $answer['jumpto'] = (string)$page_id;
                        }
                        else {
                            $answer['jumpto'] = (string)$page['prevpageid'];
                        }
                        break;

                    case LESSON_RANDOMPAGE:
                        $answer['jumpto'] = 'RANDOMPAGE';
                        break;

                    case LESSON_RANDOMBRANCH:
                        $answer['jumpto'] = 'RANDOMBRANCH';
                        break;

                    case LESSON_CLUSTERJUMP:
                        $answer['jumpto'] = 'CLUSTERJUMP';
                        break;
                }

                $lesson_struct['pages'][$page_id]['answers'][$answer_id] = $answer;
            }

            // if has no answer, set to next page if available
            if (count($page['answers']) == 0) {
                if ($page['nextpageid'] != '0') {
                    $jumpto = $page['nextpageid'];
                }
                else {
                    $jumpto = 'EOL';
                }

                $tmp_ans = array(
                    'answer'        => 'Next',
                    'response'      => '',
                    'score'         => 0,
                    'jumpto'        => $jumpto
                );

                $lesson_struct['pages'][$page_id]['answers'][0] = $tmp_ans;

            }
        }

        return $lesson_struct;
    }
}




// MAIN

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('lesson', $id, 0, false, MUST_EXIST);;
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$lesson = new lesson($DB->get_record('lesson', array('id' => $cm->instance), '*', MUST_EXIST));

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/lesson:manage', $context);

$PAGE->set_url('/mod/lesson/flowviewer.php', array('id'=>$cm->id));
$PAGE->set_title("FlowViewer prototype");
$PAGE->set_heading($SITE->fullname);

// $PAGE->navbar->add(get_string('edit'));

// load and display the specified lesson
if (!$cm) {
    print_error('Could not find course module ID '.$id);
    exit(0);
}

$extractor = new lesson_structure_extractor();
$data = $extractor->get_lesson_for_chart($cm->instance);
$data = array_merge($data, array(
            'feedback_link'  => get_config('lesson', 'flowviewer_feedback_link'),
            'help_link'      => get_config('lesson', 'flowviewer_help_link')
));

$PAGE->requires->css('/mod/lesson/css/flowviewer.css');

$PAGE->requires->yui_module(
        array('moodle-mod_lesson-d3',
              'moodle-mod_lesson-jsplumb',
              'moodle-mod_lesson-flowviewer'),
        'M.mod_lesson.flowviewer.init',
        array($data)
);
// $PAGE->requires->string_for_js('example', 'block_fruit');

// display the forms
$PAGE->set_pagelayout('embedded');
$PAGE->set_title('FlowViewer: '.$data['lesson']['name']);

echo $OUTPUT->header();

echo html_writer::start_tag('div', array('id' => 'javascript-required'));
echo 'The FlowViewer requires Javascript. ';
echo html_writer::tag('a', 'Edit the lesson.', array('href' => 'edit.php?id='.$cm->id));
echo html_writer::end_tag('div');

echo html_writer::tag('div', '', array('id'     => 'control-container',
                                       'class'  => 'lesson-flowviewer-control-container'));
echo html_writer::tag('div', '', array('id'     => 'chart-container',
                                       'class'  => 'lesson-flowviewer-chart-container'));

echo $OUTPUT->footer();

