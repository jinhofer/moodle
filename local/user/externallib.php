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
 * External groups API
 *
 * @package    local
 * @subpackage webservice
 * @copyright  2009 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("{$CFG->libdir}/externallib.php");
require_once("{$CFG->dirroot}/local/user/lib.php");

class local_user_external extends external_api {

    //========== GET_BY_X500 ==========

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_by_x500_parameters() {
        return new external_function_parameters(array(
            'x500' => new external_value(PARAM_TEXT, 'x500s; separated by comma, space, or semi-colon', VALUE_REQUIRED)
        ));
    }


    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_by_x500_returns() {
        return self::get_by_xxx_returns('x500');
    }


    /**
     * Get user profiles by x500
     * @param string $x500  multiple values separated
     * by comma, space, or semi-colon
     *
     * @return array, see get_by_xxx_returns()
     */
    public static function get_by_x500($x500) {
        $params = self::validate_parameters(self::get_by_x500_parameters(), array('x500' => $x500));

        // convert to array
        $ids =  preg_split("/[\s,;]+/", $params['x500']);

        return self::get_by_xxx('x500', $ids);
    }





    //========== GET_BY_EMPLID ==========

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_by_emplid_parameters() {
        return new external_function_parameters(array(
            'emplid' => new external_value(PARAM_TEXT, 'emplIDs; separated by comma, space, or semi-colon', VALUE_REQUIRED)
        ));
    }


    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_by_emplid_returns() {
        return self::get_by_xxx_returns('emplid');
    }


    /**
     * Get user profiles by emplID
     * @param string $emplid  multiple values separated
     * by comma, space, or semi-colon
     *
     * @return array, see get_by_xxx_returns()
     */
    public static function get_by_emplid($emplid) {
        $params = self::validate_parameters(self::get_by_emplid_parameters(), array('emplid' => $emplid));

        // convert to array
        $ids =  preg_split("/[\s,;]+/", $params['emplid']);

        return self::get_by_xxx('emplid', $ids);
    }




    //========== CREATE_FROM_X500 ==========

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function create_from_x500_parameters() {
        return new external_function_parameters(array(
            'x500' => new external_value(PARAM_TEXT, 'x500s; separated by comma, space, or semi-colon', VALUE_REQUIRED)
        ));
    }


   /**
     * Returns description of method result value
     * @return external_description
     * @see create_from_xxx_returns()
     */
    public static function create_from_x500_returns() {
        return self::create_from_xxx_returns('x500');
    }


    /**
     * create Moodle user accounts from UMN UIDs (x500)
     * @param string $x500 multiple values separated by comma, space, or semi-colon
     * @return array
     */
    public static function create_from_x500($x500) {
        global $DB;

        $params = self::validate_parameters(self::create_from_x500_parameters(),
                                            array('x500' => $x500));

        // check required capability
        $systemcontext = context_system::instance();
        require_capability('moodle/user:create', $systemcontext);

        $ids =  preg_split("/[\s,;]+/", $params['x500']);

        return self::create_from_xxx('x500', $ids);
    }



    //========== CREATE_FROM_EMLID ==========

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function create_from_emplid_parameters() {
        return new external_function_parameters(array(
            'emplid' => new external_value(PARAM_TEXT, 'emplID; separated by comma, space, or semi-colon', VALUE_REQUIRED)
        ));
    }


   /**
     * Returns description of method result value
     * @return external_description
     */
    public static function create_from_emplid_returns() {
        return self::create_from_xxx_returns('emplid');
    }


    /**
     * create Moodle user accounts from UMN emplIDs
     * @param string $emplid multiple values separated by
     * comma, space, or semi-colon
     *
     * @return array
     */
    public static function create_from_emplid($emplid) {
        global $DB;

        $params = self::validate_parameters(self::create_from_emplid_parameters(),
                                            array('emplid' => $emplid));

        // check required capability
        $systemcontext = context_system::instance();
        require_capability('local/user:view_idnumber', $systemcontext);
        require_capability('moodle/user:create', $systemcontext);

        $ids =  preg_split("/[\s,;]+/", $params['emplid']);

        return self::create_from_xxx('emplid', $ids);
    }


    //========== HELPER FUNCTIONS ==========

    /**
     * Returns description of method result value
     * @return external_description
     */
    protected static function get_by_xxx_returns($search_field) {
        return new external_single_structure(array(
            'users'    =>  new external_multiple_structure(
                new external_single_structure(array(
                    $search_field    => new external_value(PARAM_TEXT, "provided {$search_field}"),
                    'profile'        => new external_single_structure(array(
                        'id'             => new external_value(PARAM_INT, 'Moodle User ID'),
                        'auth'           => new external_value(PARAM_TEXT, 'authentication method'),
                        'mnethostid'     => new external_value(PARAM_TEXT, 'mnet host ID'),
                        'username'       => new external_value(PARAM_TEXT, 'username, x500@umn.edu'),
                        'idnumber'       => new external_value(PARAM_TEXT, 'UMN emplid number', VALUE_OPTIONAL),
                        'firstname'      => new external_value(PARAM_TEXT, 'first name'),
                        'lastname'       => new external_value(PARAM_TEXT, 'last name'),
                        'email'          => new external_value(PARAM_TEXT, 'email'),
                        'city'           => new external_value(PARAM_TEXT, 'city'),
                        'country'        => new external_value(PARAM_TEXT, 'country'),
                        'lang'           => new external_value(PARAM_TEXT, 'language'),
                        'timezone'       => new external_value(PARAM_TEXT, 'timezone'),
                        'lastaccess'     => new external_value(PARAM_TEXT, 'last access timestamp'),
                        'lastlogin'      => new external_value(PARAM_TEXT, 'last login timestamp'),
                        'description'    => new external_value(PARAM_CLEANHTML, 'description'),
                        'screenreader'   => new external_value(PARAM_TEXT, 'whether use screenreader or not')
                       ))
                   ))
            ),
            'errors' => new external_multiple_structure(
                new external_single_structure(array(
                    $search_field    => new external_value(PARAM_TEXT, "submitted {$search_field} that resulted in error"),
                    'message'        => new external_value(PARAM_TEXT, 'error message')
            )))
        ));
    }


    /**
     * Get user profiles by IDs (x500, emplID)
     * @param string $field 'x500', 'emplid'
     * @param array $ids
     *
     * @return array, see get_by_xxx_returns()
     */
    protected static function get_by_xxx($field, $ids) {
        global $DB;

        // map the structure for the algorithm
        switch ($field) {
            case 'x500':
                $moodle_field  = 'username';
                $in_adapter    = array('umn_ldap_person_accessor','uid_to_moodle_username');
                break;

            case 'emplid':
                $moodle_field    = 'idnumber';
                $in_adapter        = null;
                break;

            default:
                throw new invalid_parameter_exception("Unknown search field '{$field}'");
        }


        // define output template
        $out = array('users'     => array(),
                     'errors'    => array());

        // get the user record from x500/emplid
        foreach ($ids as $id) {
            if (!is_null($in_adapter)) {
                try {
                    $converted_id = call_user_func($in_adapter, $id);
                }
                catch(Exception $e) {
                    $out['errors'][] = array(
                        $field        => $id,
                        'message'    => "invalid {$field}: {$id} ({$e->getMessage()})");
                    continue;     // next record
                }
            }
            else
                $converted_id = $id;

            if (!$user = $DB->get_record('user', array($moodle_field => $converted_id))) {
                $out['errors'][] = array(
                    $field        => $id,
                    'message'     => "cannot find user record for '{$id}'");
                continue;    // next record
            }

            // security checks
            try {
                $context = context_user::instance($user->id);
                self::validate_context($context);
            } catch (Exception $e) {
                $out['errors'][] = array(
                    $field    => $id,
                    'message' => 'context exeption: ' . $e->getMessage());
                continue;     // next user record
            }

            if (!has_capability('moodle/user:viewdetails', $context)) {
                $out['errors'][] = array(
                    $field    => $id,
                    'message' => 'missing required capability for this user context'
                );
                continue;    // next user record
            }

            $profile = array(
                'id'            => $user->id,
                'auth'          => $user->auth,
                'mnethostid'    => $user->id,
                'username'      => $user->username,
                'firstname'     => $user->firstname,
                'lastname'      => $user->lastname,
                'email'         => $user->email,
                'city'          => $user->city,
                'country'       => $user->country,
                'lang'          => $user->lang,
                'timezone'      => $user->timezone,
                'lastaccess'    => $user->lastaccess,
                'lastlogin'     => $user->lastlogin,
                'description'   => $user->description,
                'screenreader'  => $user->screenreader
            );

            if (has_capability('local/user:view_idnumber', $context)) {
                $profile['idnumber'] = $user->idnumber;
            }

            $out['users'][] = array($field => $id, 'profile' => $profile);
        }

        return $out;
    }



    /**
     * Returns description of method result value
     * @return external_description
     */
    protected static function create_from_xxx_returns($field) {
        return new external_single_structure(array(
            'moodle_ids'    =>  new external_multiple_structure(
                new external_single_structure(array(
                    $field         => new external_value(PARAM_TEXT, "provided {$field}"),
                    'id'         => new external_value(PARAM_INT, 'created Moodle User ID')
                ))
            ),
            'errors' => new external_multiple_structure(
                new external_single_structure(array(
                    $field        => new external_value(PARAM_TEXT, "submitted {$field} that resulted in error"),
                    'message'    => new external_value(PARAM_TEXT, 'error message')
            )))
        ));
    }


    /**
     * create Moodle user accounts from x500s, or emplIDs
     *
     * @param string $field
     * @param array $ids list of x500s or emplIDs
     * @return array, @see get_by_xxx_returns()
     */
    protected static function create_from_xxx($field, $ids) {
        global $DB;

        if (!in_array($field, array('x500', 'emplid')))
            throw new invalid_parameter_exception("local_user_external::create_from_xxx: unknown field '{$field}'");

        $user_creator = new local_user_creator();

        // get the user record from x500/emplid
        switch ($field) {
            case 'x500':
                $result = $user_creator->create_from_x500s($ids); // exceptions bubble up
                break;

            case 'emplid':
                $result = $user_creator->create_from_emplids($ids); // exceptions bubble up
                break;
        }

        // format output
        $out = array('moodle_ids'    => array(),
                     'errors'        => array());

        foreach ($result['moodle_ids'] as $id => $moodle_id)
            $out['moodle_ids'][] = array($field     => $id,
                                         'id'       => $moodle_id);

        foreach ($result['errors'] as $id => $msg)
            $out['errors'][] = array($field       => $id,
                                     'message'    => $msg);


        return $out;
    }
}
