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

defined('MOODLE_INTERNAL') || die();

/**
 * This file contains the classes for the admin settings for grades
 *
 * @package   grade
 * @copyright 2014 University of Minnesota
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Include adminlib.php */
require_once($CFG->libdir . '/adminlib.php');

class grade_plugin_manager {

    /** @var object the url of the manage plugin page */
    private $pageurl;
    /** @var string any error from the current action */
    private $error = '';
    /** @var string either submission or feedback */
    private $subtype = '';

    /**
     * Constructor for this assignment plugin manager
     * @param string $subtype - either assignsubmission or assignfeedback
     */
    public function __construct($type) {
        $this->pageurl = new moodle_url('/grade/edit/plugins/index.php', array('type'=>$type));
        $this->subtype = $type;
    }


    /**
     * Return a list of plugins sorted by the order defined in the admin interface
     *
     * @return array The list of plugins
     */
    public function get_sorted_plugins_list() {
        $names = get_plugin_list($this->subtype);

        $result = array();

        foreach ($names as $name => $path) {
            $idx = get_config($this->subtype . '_' . $name, 'sortorder');
            if (!$idx) {
                $idx = 0;
            }
            while (array_key_exists($idx, $result)) $idx +=1;
            $result[$idx] = $name;
        }
        ksort($result);

        return $result;
    }


    /**
     * Util function for writing an action icon link
     *
     * @param string $action URL parameter to include in the link
     * @param string $plugintype URL parameter to include in the link
     * @param string $icon The key to the icon to use (e.g. 't/up')
     * @param string $alt The string description of the link used as the title and alt text
     * @return string The icon/link
     */
    private function format_icon_link($action, $plugintype, $icon, $alt) {
        global $OUTPUT;

        return $OUTPUT->action_icon(new moodle_url($this->pageurl,
                array('action' => $action, 'plugin'=> $plugintype, 'sesskey' => sesskey())),
                new pix_icon($icon, $alt, 'moodle', array('title' => $alt)),
                null, array('title' => $alt)) . ' ';
    }

    /**
     * Write the HTML for the plugins table.
     *
     * @return None
     */
    private function view_plugins_table() {
        global $OUTPUT, $CFG;
        /** Include tablelib.php */
        require_once($CFG->libdir . '/tablelib.php');

        // Set up the table.
        $this->view_header();
        $table = new flexible_table($this->subtype . 'pluginsadminttable');
        $table->define_baseurl($this->pageurl);
        $table->define_columns(array('pluginname', 'hideshow', 'order', 'settings'));
        $table->define_headers(array(get_string($this->subtype , 'grades'),
                get_string('hideshow', 'grades'), get_string('order'), get_string('settings')));
        $table->set_attribute('id', $this->subtype . 'plugins');
        $table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthwide');
        $table->setup();


        $plugins = $this->get_sorted_plugins_list();

        foreach ($plugins as $idx => $plugin) {
            $row = array();

            $row[] = get_string('pluginname', $this->subtype . '_' . $plugin);

            $visible = !get_config($this->subtype . '_' . $plugin, 'disabled');

            if ($visible) {
                $row[] = $this->format_icon_link('hide', $plugin, 't/hide', get_string('disable'));
            } else {
                $row[] = $this->format_icon_link('show', $plugin, 't/show', get_string('enable'));
            }

            $movelinks = '';
            if (!$idx == 0) {
                $movelinks .= $this->format_icon_link('moveup', $plugin, 't/up', get_string('up'));
            } else {
                $movelinks .= $OUTPUT->spacer(array('width'=>16));
            }
            if ($idx != count($plugins) - 1) {
                $movelinks .= $this->format_icon_link('movedown', $plugin, 't/down', get_string('down'));
            }
            $row[] = $movelinks;

            if ($row[1] != '' && file_exists($CFG->dirroot . '/grade/report/' . $plugin . '/settings.php')) {
                $row[] = html_writer::link(new moodle_url('/admin/settings.php',
                        array('section' => $this->subtype . $plugin)), get_string('settings'));
            } else {
                $row[] = '&nbsp;';
            }
            $table->add_data($row);
        }


        $table->finish_output();
        $this->view_footer();
    }

    /**
     * Write the page header
     *
     * @return None
     */
    private function view_header() {
        global $OUTPUT;
        admin_externalpage_setup('manage' . $this->subtype);
        // Print the page heading.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('manage' . $this->subtype, 'grades'));
    }

    /**
     * Write the page footer
     *
     * @return None
     */
    private function view_footer() {
        global $OUTPUT;
        echo $OUTPUT->footer();
    }

    /**
     * Check this user has permission to edit the list of installed plugins
     *
     * @return None
     */
    private function check_permissions() {
        // Check permissions.
        require_login();
        $systemcontext = context_system::instance();
        require_capability('moodle/site:config', $systemcontext);
    }


    /**
     * Hide this plugin
     *
     * @param string $plugin - The plugin to hide
     * @return string The next page to display
     */
    public function hide_plugin($plugin) {
        set_config('disabled', 1, $this->subtype . '_' . $plugin);
        return 'view';
    }

    /**
     * Change the order of this plugin
     *
     * @param string $plugintomove - The plugin to move
     * @param string $dir - up or down
     * @return string The next page to display
     */
    public function move_plugin($plugintomove, $dir) {
        // get a list of the current plugins
        $plugins = $this->get_sorted_plugins_list();

        $currentindex = 0;

        // throw away the keys

        $plugins = array_values($plugins);

        // find this plugin in the list
        foreach ($plugins as $key => $plugin) {
            if ($plugin == $plugintomove) {
                $currentindex = $key;
                break;
            }
        }

        // make the switch
        if ($dir == 'up') {
            if ($currentindex > 0) {
                $tempplugin = $plugins[$currentindex - 1];
                $plugins[$currentindex - 1] = $plugins[$currentindex];
                $plugins[$currentindex] = $tempplugin;
            }
        } else if ($dir == 'down') {
            if ($currentindex < (count($plugins) - 1)) {
                $tempplugin = $plugins[$currentindex + 1];
                $plugins[$currentindex + 1] = $plugins[$currentindex];
                $plugins[$currentindex] = $tempplugin;
            }
        }

        // save the new normal order
        foreach ($plugins as $key => $plugin) {
            set_config('sortorder', $key, $this->subtype . '_' . $plugin);
        }
        return 'view';
    }


    /**
     * Show this plugin
     *
     * @param string $plugin - The plugin to show
     * @return string The next page to display
     */
    public function show_plugin($plugin) {
        set_config('disabled', 0, $this->subtype . '_' . $plugin);
        return 'view';
    }


    /**
     * This is the entry point for this controller class
     *
     * @param string $action - The action to perform
     * @param string $plugin - Optional name of a plugin type to perform the action on
     * @return None
     */
    public function execute($action, $plugin) {
        if ($action == null) {
            $action = 'view';
        }

        $this->check_permissions();

        // process
        if ($action == 'hide' && $plugin != null) {
            $action = $this->hide_plugin($plugin);
        } else if ($action == 'show' && $plugin != null) {
            $action = $this->show_plugin($plugin);
        } else if ($action == 'moveup' && $plugin != null) {
            $action = $this->move_plugin($plugin, 'up');
        } else if ($action == 'movedown' && $plugin != null) {
            $action = $this->move_plugin($plugin, 'down');
        }


        // view
        if ($action == 'view') {
            $this->view_plugins_table();
        }
    }
}
