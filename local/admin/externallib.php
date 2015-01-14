<?php

/**
 * External admin API
 *
 * @package    local
 * @subpackage admin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/externallib.php");

class local_admin_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_roles_parameters() {
        return new external_function_parameters(
            array()
        );
    }


   /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_roles_returns() {
        return new external_multiple_structure(
            new external_single_structure(array(
                'id' 			=> new external_value(PARAM_INT, 'role ID'),
                'shortname'		=> new external_value(PARAM_TEXT, 'role short name'),
                'description'	=> new external_value(PARAM_CLEANHTML, 'role description'),
            ))
        );
    }


    /**
     * Get all roles in Moodle
     * @return array
     */
    public static function get_roles() {
        global $DB;

        $out = array();

        $roles = $DB->get_records('role');

        foreach ($roles as $role) {
            $out[] = array(
                'id'			=> $role->id,
                'shortname'		=> $role->shortname,
                'description'	=> $role->description);
        }

        return $out;
    }

}
