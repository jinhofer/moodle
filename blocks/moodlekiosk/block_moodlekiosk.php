<?php

/**
 * Course overview block
 *
 * @package    block_moodlekiosk
 * @copyright  2013 University of Minnesota
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot .'/blocks/moodlekiosk/locallib.php');

/**
 * MoodleKiosk block
 *
 */
class block_moodlekiosk extends block_base {
    public $service = null;

    /**
     * Block initialization
     */
    public function init() {
        $this->title   = get_string('pluginname', 'block_moodlekiosk');
        $this->service = new moodlekiosk_service();
    }

    /**
     * Return contents of MoodleKiosk block
     *
     * @return stdClass contents of block
     */
    public function get_content() {
        global $USER, $PAGE, $CFG, $DB;

        if($this->content !== NULL) {
            return $this->content;
        }

        // check for submitted preferences
        $new_non_acad_first = optional_param('kiosk_nonacadfirst', NULL, PARAM_TEXT);
        if (!is_null($new_non_acad_first)) {
            set_user_preference('kiosk_nonacadfirst', $new_non_acad_first);
        }

        $new_list_size = optional_param('kiosk_listsize', NULL, PARAM_TEXT);
        if (!is_null($new_list_size)) {
            set_user_preference('kiosk_listsize', $new_list_size);
        }

        // include Javascript
        $site_config = get_config('block_moodlekiosk');

        $mini_list_size = get_user_preferences('kiosk_listsize');

        if (is_null($mini_list_size) || $mini_list_size == 'site_setting') {
            $mini_list_size = $site_config->mini_list_size;
            $hiding_tolerance = $site_config->hiding_tolerance;
        }
        else {
            $hiding_tolerance = 0;
        }

        $PAGE->requires->js_init_call('M.block_moodlekiosk.init',
                                      array(array('mini_list_size'   => $mini_list_size,
                                                  'hiding_tolerance' => $hiding_tolerance)),
                                      true, $this->service->get_jsmodule());

        $this->content = new stdClass();

        // perform remote search
        try {
            $result = $this->getCourses();
        }
        catch(Exception $e) {
            $this->content->text = 'Error: '.$e->getMessage();
            return $this->content;
        }

        if (isset($result->error)) {
            $this->content = 'Error: ' . $result->reason;
            return $this->content;
        }

        // intro text
        $this->content->text = '<div class="introtext">'.get_string('introtext', 'block_moodlekiosk').'</div>';

        // display user preferences if in editing-mode
        if ($this->page->user_is_editing()) {
            $this->content->text .= $this->service->display_preferences($this->config);
        }

        $this->content->text .= $this->service->display_courses($result, $this->config, array(
                'initial_hide' => ($mini_list_size > 0)
        ));
        $this->content->text .= $this->service->get_search_form($this->instance->id);

        return $this->content;
    }

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my-index' => true);
    }

    /**
     * no need to have multiple blocks to perform the same functionality
     */
    function instance_allow_multiple() {
        return false;
    }

    /**
     * Sets block header to be hidden or visible
     * @return bool if true then header will be visible.
     */
    public function hide_header() {
        // Hide header if welcome area is show.
        return false;
    }


    /**
     *
     */
    protected function getCourses() {
        return $this->service->search_course(array(
                'roles'             => 'student,teacher,editingteacher,participant,visitor,designer',
                'exclude_instances' => get_config('block_moodlekiosk', 'instance_name')
        ));
   }
}
