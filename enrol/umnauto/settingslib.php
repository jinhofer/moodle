<?php

defined('MOODLE_INTERNAL') || die();

class admin_setting_umnautoterms extends admin_setting {


    /**
     * Constructor: uses parent::__construct
     *
     * @param string $name unique ascii name, either 'mysetting' for settings that in config, or 'myplugin/mysetting' for ones in config_plugins.
     * @param string $visiblename localised
     * @param string $description long localised info
     * @param string $defaultsetting default value for the setting (actually unused)
     */
    public function __construct($name, $visiblename, $description, $defaultsetting) {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
    }

    /**
     * Must return the data structure expected by output_html, below.
     */
    public function get_setting() {
        $enabled_terms = $this->config_read('enabled_terms');
        $default_term  = $this->config_read('default_term');

        return array('enabled' => explode(',', $enabled_terms),
                     'default' => $default_term);
    }

    /**
     * In admin_settingpage (lib/adminlib.php), admin_write_settings
     * calls this with the part of the submitted form data from the
     * setting page that is in formdata[$fullname] (or null, if none).
     * admin_write_settings interprets an return value other than
     * an empty string as an error. admin_write_settings always
     * passes at least and empty array.
     * Also, called from admin_apply_default_settings in the same
     * file.
     *
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            return '';
        }

        $return = '';

        $default = isset($data['default']) ? $data['default'] : '';

        $enabled = isset($data['enabled']) ? implode(',', $data['enabled']) : '';

        if (!$this->config_write('default_term', $default)) {
            $return = get_string('errorsetting', 'admin');
        }
        if (!$this->config_write('enabled_terms', $enabled)) {
            $return = get_string('errorsetting', 'admin');
        }

        return $return;
    }

    /**
     * The implementation of output_html in admin_settingpage first calls
     * a setting's get_setting and passes the returned data to the setting's
     * output_html. (See lib/adminlib.php.)
     * The data parameter must be an associative array with two keys: 'default'
     * and 'enabled'. The value for default is a term string and the value for
     * enabled is an array of term strings.
     */
    public function output_html($data, $query='') {

        $checked = ' checked="checked" ';

        $terms = $this->get_display_terms();
        
        $html = '<div class="umnauto_configterms">';
        $html .= '<table><tr><th>Code</th><th>Term</th><th>Enable</th><th>Default</th></tr>';

        $enabledname = $this->get_full_name().'[enabled][]';
        $defaultname = $this->get_full_name().'[default]';

        foreach ($terms as $termobj) {
            $term = $termobj->term;

            $enabledid   = $this->get_id().'['.$term.'][enabled]';
            $enabledchecked = in_array($term, $data['enabled']) ? $checked : '';

            $defaultid   = $this->get_id().'['.$term.'][default]';
            $defaultchecked = $data['default']==$term ? $checked : '';

            $row  = '<tr><td>'.$term.'</td>';
            $row .=     '<td>'.$termobj->term_name.'</td>';
            $row .=     '<td class="button"><input type="checkbox" id="'.$enabledid.'" name="'.$enabledname.'" value="'.$term.'" '.$enabledchecked.' /></td>';
            $row .=     '<td class="button"><input type="radio" id="'.$defaultid.'" name="'.$defaultname.'" value="'.$term.'" '.$defaultchecked.' /></td>';
            $html .= $row;
        }
        $html .= '</table>';
        
        // Add dummy parameter or we will not enter write_setting if none of the
        // checkboxes or radiobuttons are checked.
        $html .= '<input type="hidden" name="'.$this->get_full_name().'[dummy]" value="1" />';

        $html .= '</div>';

        return format_admin_setting($this, $this->visiblename, $html,
                                    $this->description, true, '', '', $query);
    }

    /**
     * Gets the terms to display on the configuration page.
     * Includes enabled terms and terms not more than one
     * year old.
     */
    private function get_display_terms() {
        global $DB;

        $enabled = $this->config_read('enabled_terms');
        $enabled_sql = $enabled ? " term in ($enabled) " : ' 0 = 1 ';

        $currentterm = ppsft_current_term();
        $oldterm = $currentterm - 10;

        $sql =<<<SQL
select *
from {ppsft_terms} t
where $enabled_sql or term >= :oldterm
order by term desc
SQL;

        $terms = $DB->get_records_sql($sql, array('oldterm' => $oldterm));

        return $terms;
    }

}

