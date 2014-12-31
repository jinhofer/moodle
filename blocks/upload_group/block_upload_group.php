<?php

class block_upload_group extends block_base {

    /**
     * initialize the plugin
     */
    function init() {
        $this->title = get_string('blocktitle', 'block_upload_group');
    }


    /**
     * @see block_base::applicable_formats()
     */
    function applicable_formats() {
        return array('course-view' => true);
    }


    /**
     * no need to have multiple blocks to perform the same functionality
     */
    function instance_allow_multiple() {
        return false;
    }

    /**
     * @see block_base::get_content()
     */
    function get_content() {
        global $CFG, $PAGE, $USER, $COURSE, $OUTPUT;

        if ($this->content !== NULL) {
            return $this->content;
        }

        // display admin or user page depending capability
        $context = context_block::instance($this->instance->id);

        $this->content = new stdClass();

        if (has_capability('moodle/course:managegroups', $context)) {
            $this->content->text = '<a href="'.$CFG->wwwroot.'/blocks/upload_group/index.php?id='.$COURSE->id.'">Upload groups</a>';
        } else {
            $this->content = '';
        }

        $this->content->footer = '';
        return $this->content;
    }
}
