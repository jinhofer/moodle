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
 * Quiz API
 *
 * @package    local_mod_quiz
 * @subpackage webservice
 * @copyright  University of Minnesota 2013
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/externallib.php");


/**
 * Class for the external quiz API
 */
class local_mod_quiz_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_attempts_parameters() {
        return new external_function_parameters(array(
            'quizzes'  => new external_value(PARAM_TEXT, 'comma-separated quiz IDs', VALUE_REQUIRED),
            'user_id_type'  => new external_value(PARAM_TEXT, 'the user ID type to be supplied and returned, '.
                    'x500 (default) OR emplid (requires special capability) ' .
                    'OR username (to include non-UMN users)',
                    VALUE_DEFAULT, 'x500'),
            'users'  => new external_value(PARAM_TEXT, 'comma-separated x500 or emplid (depending on ' .
                    'user_id_type; if not provided, all users in quizzes will be queried',
                    VALUE_DEFAULT, null)
        ));
    }


   /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_attempts_returns() {
        return new external_single_structure(array(
            'quizzes'  => new external_multiple_structure(
                new external_single_structure(array(
                    'cm_id'    => new external_value(PARAM_INT, 'Course-module ID of the quiz'),
                    'users'    => new external_multiple_structure(
                        new external_single_structure(array(
                            'user_id'    => new external_value(PARAM_TEXT, 'ID of the user, x500 or emplid'),
                            'attempts'   => new external_multiple_structure(
                                new external_single_structure(array(
                                    'number'      => new external_value(PARAM_INT, 'the 1st, 2nd, 3rd, ... attempt'),
                                    'timestart'   => new external_value(PARAM_INT, 'start timestamp'),
                                    'timefinish'  => new external_value(PARAM_INT, 'finish timestamp'),
                                    'state'       => new external_value(PARAM_TEXT, 'inprogress, finished, ...'),
                                    'sumgrades'   => new external_value(PARAM_RAW, 'grade of the attempt')
                        ))))
            ))))),
            'errors'   => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'error message'),
                '', VALUE_OPTIONAL)
        ));
    }


    /**
     * Get quiz attempts of specified quizzes and students
     * @param string $quizzes      A single quiz ID number or a comma separate list of quiz IDs
     * @param string $user_id_type Either x500 or emplid.  Optional.  x500 is the implied default.
     * @param string $users        A single or comma separated list of x500s or emplids.
     * @return array
     */
    public static function get_attempts($quizzes, $user_id_type, $users) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::get_attempts_parameters(), array(
            'quizzes'        => $quizzes,
            'users'          => $users,
            'user_id_type'   => strtolower($user_id_type)
        ));

        // validate user_id_type
        if (! in_array($params['user_id_type'], array('x500', 'emplid', 'username'))) {
            throw new moodle_exception(get_string('errorinvalidparam', 'webservice', 'user_id_type'));
        }

        $out = array('quizzes' => array(),
                     'errors'  => array());

        // get the context and verify capability to view grades for the quizzes
        $cm_ids = preg_split('/[\s,;]+/', $params['quizzes'], null, PREG_SPLIT_NO_EMPTY);

        $quiz_cm_map  = array();    // map from quiz_id to cm_id

        foreach ($cm_ids as $cm_id) {
            $cm = get_coursemodule_from_id('quiz', $cm_id);
            if (!$cm) {
                $out['errors'][] = "Invalid quiz course module id: $cm_id";
                continue;
            }
            $context = context_module::instance($cm->id);

            if (has_capability('moodle/grade:viewall', $context)) {
                if ($params['user_id_type'] != 'emplid' || has_capability('local/user:view_idnumber', $context)) {
                    $quiz_cm_map[$cm->instance] = $cm->id;
                }
                else {
                    $out['errors'][] = 'Capability local/user:view_idnumber is required for quiz '.$cm_id;
                }
            }
            else {
                $out['errors'][] = 'Capability moodle/grade:viewall is required for quiz '.$cm_id;
            }
        }

        if (count($quiz_cm_map) == 0) {
            $out['errors'][] = 'No valid quiz to get attempts from';
            return $out;
        }

        $user_ids = preg_split('/[\s,;]+/', $params['users'], null, PREG_SPLIT_NO_EMPTY);

        $query_params = array_keys($quiz_cm_map);

        // get all the attempts for the quizzes and users
        $query = 'SELECT quiz_attempts.id AS qa__id,
                         quiz_attempts.quiz AS qa__quiz,
                         quiz_attempts.attempt AS qa__attempt,
                         quiz_attempts.timestart AS qa__timestart,
                         quiz_attempts.timefinish AS qa__timefinish,
                         quiz_attempts.state AS qa__state,
                         quiz_attempts.sumgrades AS qa__sumgrades,
                         user.id AS user__id,
                         user.username AS user__username,
                         user.idnumber AS user__idnumber
                  FROM {quiz_attempts} quiz_attempts
                       INNER JOIN {user} user ON user.id = quiz_attempts.userid
                  WHERE quiz IN ('.implode(',', array_fill(0, count($query_params), '?')).")";

        if (count($user_ids) > 0) {
            switch ($params['user_id_type']) {
                case 'x500':
                    // convert to username
                    foreach ($user_ids as $ind => $uid) {
                        try {
                            $user_ids[$ind] = umn_ldap_person_accessor::uid_to_moodle_username($uid);
                        }
                        catch (ldap_accessor_exception $e) {
                            unset($user_ids[$ind]);
                            $out['errors'][] = 'Invalid x500: '.$uid;
                        }
                    }
                    // no break, fall through to "username"

                case 'username':
                    $field = 'username';
                    break;

                case 'emplid':
                    $field = 'idnumber';
                    break;
            }

            if (count($user_ids) > 0) {
            // query for user records
                $query .= " AND {$field} IN (".implode(',', array_fill(0, count($user_ids), '?')).')';
                $query_params = array_merge($query_params, $user_ids);
            }
        }

        $attempts = $DB->get_records_sql($query, $query_params);

        // iterate through the records, prep for output
        foreach ($attempts as $attempt) {
            $cm_id = $quiz_cm_map[$attempt->qa__quiz];
            $mid   = $attempt->user__id;

            // determine the user_id to return (x500|username|emplid)
            switch ($params['user_id_type']) {
                case 'x500':    // convert username back to x500
                    try {
                        $user_id = umn_ldap_person_accessor::moodle_username_to_uid($attempt->user__username);
                    }
                    catch (ldap_accessor_exception $e) {
                        // ignore non-umn user
                    }
                    break;

                case 'username':
                    $user_id = $attempt->user__username;
                    break;

                case 'emplid':
                    $user_id = $attempt->user__idnumber;
                    break;
            }

            if (! isset($out['quizzes'][$cm_id])) {
                $out['quizzes'][$cm_id] = array('cm_id'   => $cm_id,
                                                'users'   => array());
            }

            if (! isset($out['quizzes'][$cm_id]['users'][$mid])) {
                $out['quizzes'][$cm_id]['users'][$mid] = array('user_id'  => $user_id,
                                                               'attempts' => array());
            }

            $out['quizzes'][$cm_id]['users'][$mid]['attempts'][] = array(
                    'number'         => $attempt->qa__attempt,
                    'timestart'     => $attempt->qa__timestart,
                    'timefinish'    => $attempt->qa__timefinish,
                    'state'         => $attempt->qa__state,
                    'sumgrades'     => $attempt->qa__sumgrades);
        }

        return $out;
    }




    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function delete_attempts_parameters() {
        return new external_function_parameters(array(
                'quizzes'  => new external_value(PARAM_TEXT, 'comma-separated course-module IDs for quizzes', VALUE_REQUIRED),
                'user_id_type'  => new external_value(PARAM_TEXT, 'the user ID type to be supplied and returned, '.
                        'x500 (default) OR emplid (requires special capability) ' .
                        'OR username (to include non-UMN users)',
                        VALUE_DEFAULT, 'x500'),
                'users'  => new external_value(PARAM_TEXT, 'comma-separated x500 or emplid (depending on ' .
                        'user_id_type; if not provided, all users in quizzes will be queried', VALUE_REQUIRED)
        ));
    }


    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function delete_attempts_returns() {
        return new external_single_structure(array(
                'count'    => new external_value(PARAM_INT, 'number of attempts deleted'),
                'errors'   => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'error message')),
                    '', VALUE_OPTIONAL));
    }


    /**
     * Delete quiz attempts of specified quizzes and students
     * @param string $quizzes      A single quiz ID number or a comma separate list of quiz IDs
     * @param string $user_id_type Either x500 or emplid.  Optional.  x500 is the implied default.
     * @param string $users        A single or comma separated list of x500s or emplids.
     * @return array
     */
    public static function delete_attempts($quizzes, $user_id_type, $users) {
        global $DB;

        $params = self::validate_parameters(self::get_attempts_parameters(), array(
                'quizzes'        => $quizzes,
                'users'          => $users,
                'user_id_type'   => strtolower($user_id_type)
        ));

        // validate user_id_type
        if (! in_array($params['user_id_type'], array('x500', 'emplid', 'username'))) {
            throw new moodle_exception(get_string('errorinvalidparam', 'webservice', 'user_id_type'));
        }

        $out = array('count'   => 0,
                     'errors'  => array());

        // get the context and verify capability to view grades for the quizzes
        $cm_ids = preg_split('/[\s,;]+/', $params['quizzes'], null, PREG_SPLIT_NO_EMPTY);

        $quiz_ids = array();

        foreach ($cm_ids as $cm_id) {
            $cm = get_coursemodule_from_id('quiz', $cm_id);
            $context = context_module::instance($cm->id);

            if (has_capability('mod/quiz:deleteattempts', $context)) {
                if ($params['user_id_type'] != 'emplid' || has_capability('local/user:view_idnumber', $context)) {
                    $quiz_ids[] = $cm->instance;
                }
                else {
                    $out['errors'][] = 'Capability local/user:view_idnumber is required for quiz '.$cm_id;
                }
            }
            else {
                $out['errors'][] = 'Capability mod/quiz:deleteattempts is required for quiz '.$cm_id;
            }

        }

        if (count($quiz_ids) == 0) {
            $out['errors'][] = 'No valid quiz to delete';
            return $out;
        }

        // get all the attempts for the quizzes and users
        $query = 'SELECT quiz_attempts.id,
                         quiz_attempts.userid
                  FROM {quiz_attempts} quiz_attempts
                       INNER JOIN {user} user ON user.id = quiz_attempts.userid
                  WHERE quiz IN ('.implode(',', array_fill(0, count($quiz_ids), '?')).")";

        $query_params = $quiz_ids;

        $user_ids = preg_split('/[\s,;]+/', $params['users'], null, PREG_SPLIT_NO_EMPTY);

        switch ($params['user_id_type']) {
            case 'x500':
                // convert to username
                foreach ($user_ids as $ind => $uid) {
                    try {
                        $user_ids[$ind] = umn_ldap_person_accessor::uid_to_moodle_username($uid);
                    }
                    catch (ldap_accessor_exception $e) {
                        unset($user_ids[$ind]);
                        $out['errors'][] = 'Invalid x500: '.$uid;
                    }
                }
                // no break, fall through to "username"

            case 'username':
                $field = 'username';
                break;

            case 'emplid':
                $field = 'idnumber';
                break;
        }

        if (count($user_ids) == 0) {
            $out['errors'][] = 'No valid user.';
            return $out;
        }

        // query for user records
        $query .= " AND {$field} IN (".implode(',', array_fill(0, count($user_ids), '?')).')';
        $query_params = array_merge($query_params, $user_ids);

        $attempts = $DB->get_records_sql($query, $query_params);

        // get the list of attempt IDs
        $attempt_ids = array();
        foreach ($attempts as $attempt) {
            $attempts_ids[] = $attempt->id;
        }

        $out['count'] = count($attempts_ids);

        if (count($attempts_ids) == 0) {
            $out['errors'][] = 'No matching record to delete.';
            return $out;
        }

        // perform deletion
        $DB->delete_records_select('quiz_attempts',
                                   'id IN ('.implode(',', array_fill(0, count($attempts_ids), '?')).')',
                                   $attempts_ids);

        return $out;
    }
}
