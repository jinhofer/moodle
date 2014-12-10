<?php
/**
 * STRY0010318 mart0969 20140528 - Add page for quizzes that use a particular question *
 *
 * This page displays the quizzes that use a particular question
 *
 * This list is the list of every quiz that uses a particular question, and includes links
 * to the quiz settings page for each.
 *
 * @copyright  University of Minnesota {@link http://www.umn.edu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../config.php');
require_once($CFG->libdir . '/questionlib.php');

$id = required_param('id', PARAM_INT);
$question = question_bank::load_question($id);

// Were we given a particular context to run the question in?
// This affects things like filter settings, or forced theme or language.
if ($cmid = optional_param('cmid', 0, PARAM_INT)) {
    $cm = get_coursemodule_from_id(false, $cmid);
    require_login($cm->course, false, $cm);
    $context = context_module::instance($cmid);

} else if ($courseid = optional_param('courseid', 0, PARAM_INT)) {
    require_login($courseid);
    $context = context_course::instance($courseid);

} else {
    require_login();
    $category = $DB->get_record('question_categories',
            array('id' => $question->category), '*', MUST_EXIST);
    $context = context::instance_by_id($category->contextid);
    $PAGE->set_context($context);
    // Note that in the other cases, require_login will set the correct page context.
}
question_require_capability_on($question, 'use');
$PAGE->set_pagelayout('popup');

$page_url = new moodle_url('/question/question_used.php',array('id' => $id));
$PAGE->set_url($page_url);
$title = get_string('usedby_title', 'question', format_string($question->name));
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();

echo html_writer::tag('h3','Used in quiz:');

$sql = 'SELECT qu.id, qu.name, qu.course FROM (SELECT DISTINCT quizid FROM {quiz_slots} WHERE questionid = ?
        UNION SELECT DISTINCT quiza.quiz FROM {quiz_attempts} quiza
        JOIN {question_attempts} qa ON qa.questionusageid = quiza.uniqueid WHERE qa.questionid = ?) uniqu JOIN {quiz} qu ON uniqu.quizid=qu.id';

$result = array();
$coursemodules = array();
$result = $DB->get_records_sql($sql, array($question->id, $question->id));

if (!empty($result)) {
    foreach ($result as $quiz) {
        if (!isset($coursemodules[$quiz->course])) {
            $coursemodules[$quiz->course] = get_fast_modinfo($quiz->course);
        }
        $cmid = $coursemodules[$quiz->course]->instances['quiz'][$quiz->id]->id;
        $params = array();
        $params['cmid'] = $cmid;
        $link = new moodle_url('/mod/quiz/edit.php', $params);
        $out = $link->out();
        $print = html_writer::tag('a',$quiz->name,array('href' => $out));
        echo html_writer::tag('p', $print);
    }
}

echo $OUTPUT->footer();
