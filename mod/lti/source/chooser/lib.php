<?PHP

/**
 * STRY0010148 20140530 dhanzely
 *
 * Adds functionality to allow LTI Tool Types to be displayed in the Activity Chooser.
 *
 * @package    ltisource_chooser
 * @author     Dominic Hanzely <dhanzely@umn.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/lti/locallib.php');

/**
 * Called from lti/mod_form.php when adding an instance of a LTI Tool, which then retrieves the tool's
 * configuration and hydrates form using those values.
 *
 * @param string $alphatypeid
 * @return array|null
 */
function ltisource_chooser_add_instance_hook($alphatypeid) {
    $type = lti_get_type(ltisource_chooser_alpha_to_int($alphatypeid));
    if ($type) {
        return array(
            'typeid' => $type->id,
            'name'   => $type->name,
        );
    }
}

/**
 * Called from lti/edit_form.php when creating or editing tool type settings and creates a settings section for this sub-plugin.
 *
 * @param moodleform $form
 * @param bool $isadmin
 * @return null
 */
function ltisource_chooser_edit_types_form($form, $isadmin = false) {
    if ($isadmin) {
        $form->addElement('header', 'ltisource_chooser', get_string('settingsheader', 'ltisource_chooser'));

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

        $form->addElement('select', 'ltisource_chooser_show', get_string('show', 'ltisource_chooser'), $ynoptions);
        $form->addHelpButton('ltisource_chooser_show', 'show', 'ltisource_chooser');

        $form->addElement('editor', 'ltisource_chooser_helptext', get_string('helptext', 'ltisource_chooser'));
        $form->setType('ltisource_chooser_helptext', PARAM_RAW);
        $form->addHelpButton('ltisource_chooser_helptext', 'helptext', 'ltisource_chooser');
    } else {
        $form->addElement('hidden', 'ltisource_chooser_show', '0');
    }

    $form->setType('ltisource_chooser_show', PARAM_BOOL);
    $form->disabledIf('ltisource_chooser_show', 'lti_coursevisible');
}

/**
 * Returns Activity Chooser configuration details for the tool
 *
 * @param int $typeid   Basic LTI tool typeid
 *
 * @return array        Tool Configuration
 */
function ltisource_chooser_get_type_config($typeid) {
    global $DB;

    $query = "SELECT name, value
                FROM {lti_source_chooser}
               WHERE typeid = :typeid";

    $typeconfig = array();
    $configs = $DB->get_records_sql($query, array('typeid' => $typeid));

    if (!empty($configs)) {
        foreach ($configs as $config) {
            $typeconfig["ltisource_chooser_{$config->name}"] = $config->value;
        }
    }

    return $typeconfig;
}

/**
 * Updates Activity Chooser configuration details for the tool
 *
 * @param  object $type         Basic LTI object
 * @param  array  $config       Tool configuration
 */
function ltisource_chooser_update_type($type, $config) {
    global $DB;

    if ($DB->update_record('lti_source_chooser', $type)) {
        foreach ($config as $key => $value) {
            if (substr($key, 0, 18)=='ltisource_chooser_' && !is_null($value)) {
                $record = new StdClass();
                $record->typeid = $type->id;
                $record->name = substr($key, 18);

                if (is_array($value)) {
                    $record->value = serialize($value);
                } else {
                    $record->value = $value;
                }

                ltisource_chooser_update_config($record);
            }
        }
    }

}

/**
 * Updates a tool Activity Chooser configuration in the database
 *
 * @param $config   Tool configuration
 *
 * @return Record id number
 */
function ltisource_chooser_update_config($config) {
    global $DB;

    $return = true;
    $old = $DB->get_record('lti_source_chooser', array('typeid' => $config->typeid, 'name' => $config->name));

    if ($old) {
        $config->id = $old->id;
        $return = $DB->update_record('lti_source_chooser', $config);
    } else {
        $return = $DB->insert_record('lti_source_chooser', $config);
    }
    return $return;
}

/**
 * Called from lti/locallib.php when editing tool type settings and applies this sub-plugin's configuration values.
 *
 * @param stdClass $type
 * @param array $config
 * @return null
 */
function ltisource_chooser_get_type_type_config($type, $config) {
    if (isset($config['ltisource_chooser_show'])) {
        $type->ltisource_chooser_show= $config['ltisource_chooser_show'];
    }

    if (isset($config['ltisource_chooser_helptext'])) {
        $type->ltisource_chooser_helptext = unserialize($config['ltisource_chooser_helptext']);
    }
}

/**
 * Called from lti/lib.php when in course editing mode to render activity chooser list.
 *
 * @return array
 */
function ltisource_chooser_get_types() {
    $types = array();
    $tools = lti_get_types_for_add_instance();
    foreach ($tools as $tool) {
        if (!isset($tool->id))
            continue;

        $typeconfig = ltisource_chooser_get_type_config($tool->id);
        if (isset($typeconfig['ltisource_chooser_show']) && $typeconfig['ltisource_chooser_show']) {
            $type = new stdClass();
            $type->modclass = MOD_CLASS_ACTIVITY;
            $type->type = 'lti&amp;type='.ltisource_chooser_int_to_alpha($tool->id);
            $type->typestr = $tool->name;

            if (!empty($typeconfig['ltisource_chooser_helptext'])) {
                $helptextarray = unserialize($typeconfig['ltisource_chooser_helptext']);
                $type->help = $helptextarray['text'];
            } else {
                $type->help = get_string('helptext_default', 'ltisource_chooser');
            }

            $types[] = $type;
        }
    }

    return $types;
}

/**
 * Converts an integer to alpha notation (A-Za-z), which is needed
 * since the only way to reference the tool through the /course/mod.php
 * URL is by using the PARAM_ALPHA parameter 'type'.
 *
 * @param integer $input The integer to convert
 * @param string $ret    Used while recursively constructing alpha string
 * @return string
 */
function ltisource_chooser_int_to_alpha($input, $ret = '') {
    $mod = $input % 52;
    $ret = chr($mod < 26 ? $mod + 65 : $mod + 71) . $ret;

    if ($quotient = floor($input / 52)) {
        $quotient = $input > 52 && $quotient == 0 ? 1 : $quotient;
        $ret = ltisource_chooser_int_to_alpha($quotient, $ret);
    }

    return $ret;
}

/**
 * Converts an alpha string back to an integer
 *
 * @param string $input The alpha string to convert back to an integer
 * @return integer
 */
function ltisource_chooser_alpha_to_int($input) {
    $retval = 0;
    $len = strlen($input);
    $power = $len - 1;

    for ($i=0; $i < $len; $i++, $power--) {
        $ascii = ord(substr($input, $i, 1));
        $value = $ascii >= 97 ? $ascii - 71 : $ascii - 65;
        $retval += $value * pow(52, $power);
    }

    return $retval;
}
