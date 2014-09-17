<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class category_course_settings_form extends moodleform {

    function definition() {
        global $CFG, $USER;

        $categoryid = $this->_customdata['categoryid'];

        $mform = $this->_form;

//-------------------------------------------------------------------------------
        // TODO dhanzely 20140808 - consider making this a lanaguage string, but for now leaving it
        // as-is which appears to be consistent with other plugins.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'defaultsourcecourseid', get_string('categorycoursetemplateid', 'local_course'));
        $mform->setType('defaultsourcecourseid', PARAM_INT);
        $mform->addHelpButton('defaultsourcecourseid', 'categorycoursetemplateid', 'local_course');


        if (!empty($CFG->allowcoursethemes)) {
            $themeobjects = get_list_of_themes();
            $themes=array();
            $themes[''] = get_string('forceno');
            foreach ($themeobjects as $key=>$theme) {
                if (empty($theme->hidefromselector)) {
                    $themes[$key] = get_string('pluginname', 'theme_'.$theme->name);
                }
            }

            // 20120217 hoang027 >>> add additional themes owned by this user (in theme_customizer block)
            if (file_exists($CFG->dirroot.'/blocks/theme_customizer/lib.php')) {
                include_once($CFG->dirroot.'/blocks/theme_customizer/lib.php');

                $theme_lib = new theme_customizer();
                $owned_themes = $theme_lib->get_owner_themes($USER->id);

                foreach ($owned_themes as $key => $name) {
                    $themes[$key] = get_string('pluginname', 'theme_'.$name);
                }
            }
            // <<< 20120217 hoang027

            $mform->addElement('select', 'theme', get_string('defaultcategorytheme', 'local_course'), $themes);
            $mform->addHelpButton('theme', 'defaultcategorytheme', 'local_course');
        }

        $mform->addElement('hidden', 'categoryid', 0);
        $mform->setType('categoryid', PARAM_INT);
        $mform->setDefault('categoryid', $categoryid);

//-------------------------------------------------------------------------------
        $this->add_action_buttons(true, get_string('savechanges'));

//-------------------------------------------------------------------------------
        $this->set_data($this->_customdata);
    }

    /**
     * Validates the data submit for this form.
     *
     * @param array $data An array of key,value data pairs.
     * @param array $files Any files that may have been submit as well.
     * @return array An array of errors.
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);
        if (!empty($data['defaultsourcecourseid'])) {
            if (!$existing = $DB->get_record('course', array('id' => $data['defaultsourcecourseid']))) {
                $errors['defaultsourcecourseid'] = get_string('categorycoursetemplateid_error', 'local_course');
            }
        }

        return $errors;
    }
}
