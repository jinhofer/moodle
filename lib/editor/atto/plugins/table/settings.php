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
 * Settings that allow turning on and off various table features
 *
 * @package     atto_table
 * @copyright   2015 Joseph Inhofer <jinhofer@umn.edu>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('editoratto', new admin_category('atto_table', new lang_string('pluginname', 'atto_table')));

$settings = new admin_settingpage('att_table_settings', new lang_string('settings', 'atto_table'));
if ($ADMIN->fulltree) {
    $name = new lang_string('allowborder', 'atto_table');
    $desc = new lang_string('allowborder_desc', 'atto_table');
    $default = 0;

    $setting = new admin_setting_configcheckbox('atto_table/allowborder',
                                                $name,
                                                $desc,
                                                $default);
    $settings->add($setting);

    $name = new lang_string('allowborderstyle', 'atto_table');
    $desc = new lang_string('allowborderstyle_desc', 'atto_table');
    $default = 0;

    $setting = new admin_setting_configcheckbox('atto_table/allowborderstyle',
                                                $name,
                                                $desc,
                                                $default);
    $settings->add($setting);

    $name = new lang_string('borderstyle', 'atto_table');
    $desc = new lang_string('borderstyles_desc', 'atto_table');
    $default = new lang_string('borderstyles_default', 'atto_table');

    $setting = new admin_setting_configtextarea('atto_table/borderstyles',
                                                $name,
                                                $desc,
                                                $default,
                                                PARAM_TEXT,
                                                '50',
                                                '10');
    $settings->add($setting);

    $name = new lang_string('allowbordersize', 'atto_table');
    $desc = new lang_string('allowbordersize_desc', 'atto_table');
    $default = 0;

    $setting = new admin_setting_configcheckbox('atto_table/allowbordersize',
                                                $name,
                                                $desc,
                                                $default);
    $settings->add($setting);

    $name = new lang_string('allowbordercolor', 'atto_table');
    $desc = new lang_string('allowbordercolor_desc', 'atto_table');
    $default = 0;

    $setting = new admin_setting_configcheckbox('atto_table/allowbordercolor',
                                                $name,
                                                $desc,
                                                $default);
    $settings->add($setting);

    $name = new lang_string('allowbackgroundcolor', 'atto_table');
    $desc = new lang_string('allowbackgroundcolor_desc', 'atto_table');
    $default = 0;

    $setting = new admin_setting_configcheckbox('atto_table/allowbackgroundcolor',
                                                $name,
                                                $desc,
                                                $default);
    $settings->add($setting);
}
