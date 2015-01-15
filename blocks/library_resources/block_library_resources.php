<?php


class block_library_resources extends block_base {
    /**
     * List of items to be displayed in the block (configurable)
     * @var array
     */
    public static $display_items = array(
        'library_search'        => true,
        'course_reserves'       => true,
        'course_resources'      => true
    );


    /**
     * List of campuses
     * @var array
     */
    public static $campuses = array(
        'tc'    => 'TC',
        'dl'    => 'Duluth',
        'cr'    => 'Crookston',
        'mr'    => 'Morris'
    );


    /**
     * list of search engines
     * @var array
     */
    public static $search_engines = array(
        'articlediscovery' => array('default_state' => 'tc'),
        'ebscohost'        => array('default_state' => 'on'),
        'catalog'          => array('default_state' => 'tc'),
        'pubmed'           => array('default_state' => 'off'),
    );



    /**
     * initialize the plugin
     */
    function init() {
        $this->title = get_string('blocktitle', 'block_library_resources');
    }


    /**
     * @see block_base::applicable_formats()
     */
    function applicable_formats() {
        return array('site-index' => true, 'course-view' => true);
    }


    /**
     * no need to have multiple blocks to perform the same functionality
     */
    function instance_allow_multiple() {
        return false;
    }


    /**
     * @see block_base::instance_allow_config()
     */
    public function instance_allow_config() {
      return true;
    }


    /**
     * @see block_base::get_content()
     */
    function get_content() {
        global $CFG, $PAGE, $COURSE;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();

        // use default config if none is found (user never saved config before)
        if (empty($this->config)) {
            $this->config = new stdClass();

            foreach (self::$display_items as $item => $default) {
                $config_name = 'show_' . $item;
                $this->config->$config_name = $default ? 'yes' : 'no';
            }
        }

        if (!isset($this->content->text)) {
            $this->content->text = '';
        }

        // process the content based on display configuration
        foreach (self::$display_items as $item => $default) {
            switch ($item) {
                case 'library_search':
                    // display search form
                    if (isset($this->config->show_library_search) && $this->config->show_library_search == 'yes' ) {
                        $this->content->text .= $this->get_search_form();
                    }
                    break;

                case 'course_resources':
                    if ($COURSE->id != SITEID) {
                        if (isset($this->config->show_course_resources) && $this->config->show_course_resources == 'yes') {
                            $this->content->text .= $this->get_course_specific_links();
                        }
                    }
                    break;

                case 'course_reserves':
                    // check reserves link visibility
                    if (isset($this->config->show_course_reserves) && $this->config->show_course_reserves == 'yes' ) {
                        $link = $CFG->wwwroot.'/local/library_reserves/lister.php?course_id='.$COURSE->id;
                        $this->content->text .= '<p><a class="course_reserves" href="'.$link.'" target="_blank">'.
                                                get_string('ui_course_reserves', 'block_library_resources').'</a></p>';
                    }
                    break;

                default:
                    // ignore
            }
        }

        // add the personal account link
        $campus = 'no';
        if (isset($this->config->personal_account)) {
            $campus = $this->config->personal_account;
        }
        else if (isset($this->config->show_personal_account) && $this->config->show_personal_account == 'yes' ) {
            $campus = 'tc';    // if previous setting was "yes", use TC instead
        }

        if ($campus != 'no') {
            $lib_link    = get_config('library_resources', 'link_'.$campus.'_personal');
            $link_name   = get_string('ui_personal_account_'.$campus, 'block_library_resources');

            $this->content->text .= "<p><a href=\"{$lib_link}\" target=\"_blank\">{$link_name}</a></p>";
        }

        // add the librarian chat link
        $campus = 'no';
        if (isset($this->config->librarian_chat)) {
            $campus = $this->config->librarian_chat;
        }
        else if (isset($this->config->show_librarian_chat) && $this->config->show_librarian_chat == 'yes' ) {
            $campus = 'tc';
        }

        if ($campus != 'no') {
            $lib_link    = get_config('library_resources', 'link_librarian_chat_' . $campus);
            $link_name   = get_string('ui_librarian_chat_'.$campus, 'block_library_resources');

            $this->content->text .= "<p><a href=\"{$lib_link}\" target=\"_blank\">{$link_name}</a></p>";
        }

        // add the library homepage link
        $campus = 'no';
        if (isset($this->config->show_homepage)) {
            if ($this->config->show_homepage != 'no') {
                $campus = $this->config->show_homepage;
            }
        }
        else {
            // check for value of previous version
            foreach (array('mr', 'dl', 'cr', 'tc') as $abbrev) {
                $setting_name = 'show_' . $abbrev . '_lib';

                if (isset($this->config->$setting_name) && $this->config->$setting_name == 'yes') {
                    $campus = $abbrev;
                }
            }
        }

        if ($campus != 'no') {
            $lib_link    = get_config('library_resources', 'link_' . $campus . '_lib');
            $link_name   = get_string('ui_lib_homepage_'.$campus, 'block_library_resources');

            $this->content->text .= "<p><a href=\"{$lib_link}\" target=\"_blank\">{$link_name}</a></p>";
        }


        // add the catalog link
        $campus = 'no';
        if (isset($this->config->show_catalog)) {
            if ($this->config->show_catalog != 'no') {
                $campus = $this->config->show_catalog;
            }
        }
        else {
            // check for value of previous version
            foreach (array('mr', 'dl', 'cr', 'tc') as $abbrev) {
                $setting_name = 'show_' . $abbrev . '_cat';

                if (isset($this->config->$setting_name) && $this->config->$setting_name == 'yes') {
                    $campus = $abbrev;
                }
            }
        }

        if ($campus != 'no') {
            $lib_link    = get_config('library_resources', 'link_' . $campus . '_cat');
            $link_name   = get_string('ui_catalog_'.$campus, 'block_library_resources');

            $this->content->text .= "<p><a href=\"{$lib_link}\" target=\"_blank\">{$link_name}</a></p>";
        }


        // add the research guide link
        $campus = 'no';
        if (isset($this->config->show_research_guide)) {
            if ($this->config->show_research_guide != 'no') {
                $campus = $this->config->show_research_guide;
            }
        }

        if ($campus != 'no') {
            $guide_link  = get_config('library_resources', 'link_' . $campus . '_rs_guide');
            $link_name   = get_string('ui_research_guide_'.$campus, 'block_library_resources');

            $this->content->text .= "<p><a href=\"{$guide_link}\" target=\"_blank\">{$link_name}</a></p>";
        }

        $this->content->footer = '';
        return $this->content;
    }


    /**
     *
     * @return string
     */
    protected function get_search_form() {
        global $CFG;

        $str = '<form id="library_search_form" name="library_search_form" action="'.
                $CFG->wwwroot.'/blocks/library_resources/search.php" method="POST" target="_blank">';
        $str .= '<span class="library_search_title">'.get_string('ui_search_in', 'block_library_resources').': </span>';
        $str .= '<select id="library_search_domain" name="library_search_domain">';

        foreach (self::$search_engines as $engine_id => $cfg) {
            $setting_name = 'search_use_' . $engine_id;

            $is_enabled = false;

            // when the block doesn't have setting for new engines yet
            if ( !isset($this->config->$setting_name) ) {
                $is_enabled = $cfg['default_state'] != 'off';

                // map the article discovery engine to the selected campus
                switch ($engine_id) {
                    case 'articlediscovery':
                        $engine_id .= '_tc';
                        break;

                    case 'catalog':
                        $old_engine_map = array(
                            'crookstoncatalog' => 'cr',
                            'duluthcatalog'    => 'dl',
                            'morriscatalog'    => 'mr',
                            'mncatplus'        => 'tc');

                        // get the first "yes" of the old four campuses
                        foreach ($old_engine_map as $old_setting => $campus) {
                            $old_setting = 'search_use_'.$old_setting;
                            if (isset($this->config->$old_setting) && $this->config->$old_setting == 'yes') {
                                $engine_id = 'catalog_'.$campus;
                                break;
                            }
                        }
                        break;
                    //STRY0010378 20140606 mart0969 - Add PubMed search
                    case 'pubmed':
                        $engine_id .= '_gen';
                        break;
                }
            }
            else {
                $is_enabled = $this->config->$setting_name != 'no';
                $campuses   = array('dl', 'cr', 'mr', 'tc');

                // map the engine_id to the selected campus
                if ($engine_id == 'articlediscovery' || $engine_id == 'catalog') {

                    if (in_array($this->config->$setting_name, $campuses)) {
                        $engine_id .= '_' . $this->config->$setting_name;
                    }
                    else {
                        $engine_id .= '_tc';     // default to TC
                    }
                } elseif ($engine_id == 'pubmed') {
                    if (in_array($this->config->$setting_name, $campuses)) {
                        $engine_id .= '_' . $this->config->$setting_name;
                    }
                    else {
                        $engine_id .= '_gen';
                    }
                }
            }

            if ($is_enabled) {
                $str .= '<option value="'.$engine_id.'">'.get_string('ui_se_'.$engine_id, 'block_library_resources').'</option>';
            }
        }

        $str .= '</select><br>';
        $str .= '<input type="text" id="library_search_text" name="library_search_text" value="">';
        $str .= '<input id="library_search_submit" type="submit" value="Go"/></form>';

        return $str;
    }



    /**
     * format the course-resources and course-reserves links
     * @return string
     */
    protected function get_course_specific_links() {
        global $DB, $COURSE;

        // get the related PeopleSoft classes   //TODO: verify this
        $query = "SELECT DISTINCT
                         ppsft_classes.subject AS subject,
                         ppsft_classes.catalog_nbr AS number
                  FROM   {enrol} enrol
                         INNER JOIN {enrol_umnauto_classes} enrol_umnauto_classes
                             ON enrol.id = enrol_umnauto_classes.enrolid
                         INNER JOIN {ppsft_classes} ppsft_classes
                             ON ppsft_classes.id = enrol_umnauto_classes.ppsftclassid
                  WHERE  ppsft_classes.institution = 'UMNTC' AND
                         enrol.courseid = :course_id";

        $rs = $DB->get_recordset_sql($query, array('course_id' => $COURSE->id));

        $str = '';

        // process each class
        foreach ($rs as $row) {
            // check resources link visibility
            if ( $this->config->show_course_resources == 'yes' ) {
                $link = str_replace(array('{SUBJECT}', '{NUMBER}'),
                                    array($row->subject, $row->number),
                                    get_config('library_resources', 'link_course_resources'));

                $str .= '<p><a class="course_resources" href="'.$link.'" target="_blank">'.
                        get_string('ui_course_resource', 'block_library_resources', $row->subject.' '.$row->number).'</a></p>';
            }
        }

        return $str;
    }


    /**
     * some validation before saving
     * @see block_base::instance_config_save()
     */
    public function instance_config_save($data, $nolongerused = false) {
        // at least one search engine has to be enabled if the search option is enabled
        if ($data->show_library_search == 'yes') {
            $engine_selected = false;
            foreach (self::$search_engines as $engine_id => $options) {
                $setting_name = 'search_use_' . $engine_id;

                if ($data->$setting_name != 'no') {
                    $engine_selected = true;
                    break;
                }
            }

            if ($engine_selected == false) {
                print_error('no_engine_error', 'block_library_resources');
                return false;
            }
        }

        // And now forward to the default implementation defined in the parent class
        return parent::instance_config_save($data);
    }


    /**
     * this block has global config
     * @see block_base::has_config()
     */
    function has_config() {
        return true;
    }

}
