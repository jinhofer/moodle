<?php

/**
 * @package    blocks
 * @subpackage moodlekiosk
 * @copyright  2013 University of Minnesota
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require('../../config.php');
require_once($CFG->dirroot .'/blocks/moodlekiosk/locallib.php');

require_login(SITEID);

$action       = required_param('action', PARAM_TEXT);
$instance_id  = required_param('id', PARAM_INT);

$instance_record = $DB->get_record('block_instances', array('id' => $instance_id));
$block_instance = block_instance('moodlekiosk', $instance_record);

$PAGE->requires->css('/blocks/moodlekiosk/styles.css');
$PAGE->set_url(new moodle_url('/blocks/moodlekiosk/action.php'));
$PAGE->set_title(get_string('search'));
$PAGE->set_heading(get_string('search'));
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('searchresults'));

// include Javascript
$jsmodule = array(
        'name'         => 'block_moodlekiosk',
        'fullpath'     => '/blocks/moodlekiosk/module.js',
        'requires'     => array('base', 'io', 'node', 'json', 'event', 'event-simulate'),
        'strings'	   => array(
                array('entersearchprompt', 'block_moodlekiosk'),
        )
);

$service = new moodlekiosk_service();

// include Javascript
$mini_list_size   = get_config('block_moodlekiosk', 'mini_list_size');
$hiding_tolerance = get_config('block_moodlekiosk', 'hiding_tolerance');

if (!empty($block_instance->config->mini_list_size) && $block_instance->config->mini_list_size != 'site_setting') {
    $mini_list_size = $block_instance->config->mini_list_size;
    $hiding_tolerance = 0;    // don't apply tolerance if user selects a size
}

$PAGE->requires->js_init_call('M.block_moodlekiosk.init',
        array(array('mini_list_size' => $mini_list_size)),
        true, $service->get_jsmodule());

echo $OUTPUT->header();

// dispatch the submitted action
switch ($action) {
    case 'search':
        $search_value = trim(required_param('search_value', PARAM_TEXT));

        if ($search_value == '' || strlen($search_value) < 3) {
            echo '<h2 class="main">', get_string('invalidsearchvalue', 'block_moodlekiosk'), '</h2>';
            echo $OUTPUT->box_start();
        }
        else {
            $result = $service->search_course(array('course_name' => $search_value));

            if (isset($result->error)) {
                echo 'Error: ' . $result->reason;
                exit;
            }

            echo '<h2 class="main">', get_string('searchresult', 'block_moodlekiosk', $search_value), '</h2>';
            echo $OUTPUT->box_start();

            $content = $service->display_courses($result, $block_instance->config, array('initial_hide' => ($mini_list_size > 0)));

            if ($content == '') {
                echo 'No course match.';
            }
            else {
                echo $content;
            }
        }

        // the search box
        echo $service->get_search_form($instance_id);

        echo $OUTPUT->box_end();
        break;

    default:
        print_error('invalidaction', 'block_moodlekiosk', $action);
}

echo $OUTPUT->footer();
exit;
