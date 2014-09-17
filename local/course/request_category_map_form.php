<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class local_course_request_category_map_form extends moodleform {

    protected function definition() {
        global $CFG;

        $mform =& $this->_form;

        // aliasing for readability
        $this->current_settings =& $this->_customdata;

        // We need at least one form element before we start the table or we end up with
        // a fieldset opening tag inside the field and the corresponding fieldset closing
        // tag outside the table.  Grep on _openHiddenFieldsetTemplate and _closeFieldsetTemplate
        // for details.  This is worth having anyway.
        $mform->addElement('header', 'categorymappinglist', get_string('categorymappinglist', 'local_course'));

        $mform->addElement('html', '<div class="categorymapinstructions">');
        $mform->addElement('html', '<p>'.get_string('categorymapinstructions', 'local_course').'</p>');
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '<table class="categorymap">');
        $mform->addElement('html', '<thead>');
        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<th>'.get_string('categories', 'local_course').'</th>');
        $mform->addElement('html', '<th>'.get_string('allowrequest', 'local_course').'</th>');
        $mform->addElement('html', '</tr>');
        $mform->addElement('html', '</thead>');
        $mform->addElement('html', '<tbody>');
        $this->display_category_recursively(null);
        $mform->addElement('html', '</tbody>');
        $mform->addElement('html', '</table');

        $this->add_action_buttons();
    }

    /**
     *
     */
    private function display_category_recursively($category) {
        $mform =& $this->_form;

        // Top-level categories have parent == 0.
        $categoryid = empty($category) ? 0 : $category->id;

        if (!empty($category)) {
            $indent = $this->get_category_indent($category);
            $categorylink = $this->get_category_link_string($category, $indent);

            $mform->addElement('html', '<tr>');

            $mform->addElement('html', "<td>$categorylink</td>");

            // Currently, course requests only go to second or third level categories.
            if ($category->depth == 2 or $category->depth == 3) {
                $mform->addElement('html', "<td>");
                $mform->addElement('advcheckbox', "allowrequests[$categoryid]", $indent);
                $mform->addElement('html', '</td>');
            } else {
                $mform->addElement('html', '<td>&nbsp;</td>');
            }

            $mform->addElement('html', '</tr>');

            if (array_key_exists($categoryid, $this->current_settings)) {
                $current = $this->current_settings[$categoryid];
                $mform->setDefault("allowrequests[$categoryid]", $current->allowrequest);
            }
        }

        if ($categories = coursecat::get($categoryid)->get_children()) {

            foreach ($categories as $cat) {
                $this->display_category_recursively($cat);
            }
        }

    }

    private function get_category_indent($category) {
        $indent = '';
        for ($i=1; $i<$category->depth; ++$i) {
            $indent .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        }
        return $indent;
    }

    private function get_category_link_string($category, $indent) {
        global $CFG;

        $linkcss = $category->visible ? '' : ' class="dimmed" ';

        $linkurl = "$CFG->wwwroot/course/index.php?categoryid=$category->id";

        # TODO: 1.9 version uses format_string.  Do we need it?
        $categoryname = $category->name;

        $linkstring = "$indent<a $linkcss href=\"$linkurl\">$categoryname</a>";

        return $linkstring;
    }

    public function validation($data, $files) {
        $errors = array();
        return $errors;
    }

}

