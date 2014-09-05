<?PHP

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
 * local plugin to manipulate user accounts in various ways
 * (from LDAP, ...)
 *
 * @package   local
 * @subpackage user
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot .'/local/ldap/lib.php');

define('NO_MATCH_IN_LDAP_ERR_MSG', 'No match in LDAP');

function x500_to_moodle_username($x500) {
    return $x500 . '@umn.edu';
}

function moodle_username_to_x500($username) {
    if (strpos($username, '@umn.edu') === false) {
        throw new local_user_exception("Invalid username $username");
    }
    return str_replace('@umn.edu', '', $username);
}


/**
 * specific exception used by ldap package
 */
class local_user_exception extends Exception {}

class local_user_notinldap_exception extends Exception {}

class local_user_creator {

    /**
     * connector to LDAP
     * @var umn_ldap_person_accessor
     */
    protected $ldap_accessor;


    /**
     *
     * @param object $ldap_accessor, optional, default to umn_ldap_person_accessor
     */
    public function __construct($ldap_accessor = null) {
        if (is_null($ldap_accessor))
            $this->ldap_accessor = new umn_ldap_person_accessor();
        else
            $this->ldap_accessor = $ldap_accessor;
    }

    /**
     * The only fix we do currently is to fill in the idnumber for the user if
     * the idnumber is empty. We don't attempt to fix an idnumber (emplid)
     * mismatch since that would require some investigation.
     *
     * Returns the user's Moodle id if we fixed the user record. Returns false, otherwise.
     */
    public function attempt_user_fix($emplid) {
        global $DB;

        $ldap_people = $this->ldap_accessor->get_people_by_emplids(array($emplid));

        $user = $DB->get_record('user', array('username'=>$ldap_people[$emplid]['username']));

        if (!empty($user)) {
            if (empty($user->idnumber)) {
                // User's Moodle record does not have idnumber. We just found one in
                // LDAP for that user, so we can fix that.
                $user->idnumber = $ldap_people[$emplid]['idnumber'];
                $DB->update_record('user', $user);
                return $user->id;
            } else if ($user->idnumber <> $ldap_people[$emplid]['idnumber']) {
                error_log("Username $user->username has idnumber (emplid) mismatch.  "
                          . 'Moodle idnumber: ' . $user->idnumber
                          . '.  From LDAP: ' . print_r($ldap_people[$emplid], true));
            }
        }
        return false;
    }

    /**
     * create a Moodle user by UMN UID (x500)
     * with information from LDAP
     *
     * @param mixed $uids string OR array of UIDs
     * @return array @see return by create_from_xxx()
     */
    public function create_from_x500s($x500s) {
        return $this->create_from_ldap_field('x500', $x500s);
    }

    /**
     * create a Moodle user by UMN UID (x500)
     * with information from LDAP
     *
     * @param string $x500 string
     * @return moodle user id
     */
    public function create_from_x500($x500) {
        $ldap_people = $this->ldap_accessor->get_people_by_uids(array($x500));
        if (!array_key_exists($x500, $ldap_people)) {
            throw new local_user_notinldap_exception("x500 not found in LDAP: $x500");
        }
        $moodle_id = $this->create_from_ldap($ldap_people[$x500]);
        return $moodle_id;
    }

    /**
     * Parameter $emplids is an array of emplids.  This function will determine
     * which do not already have a corresponding user in Moodle and will create
     * Moodle users for them.
     */
    public function create_from_missing_emplids($emplids) {
        global $DB;

        $users = $DB->get_records_list('user', 'idnumber', $emplids, '', 'idnumber');
        $useremplids = array_map(function($a) { return $a->idnumber; }, $users);

        $missingemplids = array_diff($emplids, $useremplids);
        return $this->create_from_emplids($missingemplids);
    }

    /**
     * create a Moodle user by UMN employee ID
     * with information from LDAP
     *
     * @param mixed $emplids string OR array of emplids
     * @return array @see return by create_from_xxx()
     */
    public function create_from_emplids($emplids) {
        return $this->create_from_ldap_field('emplid', $emplids);
    }

    /**
     * create a Moodle user by UMN employee ID
     * with information from LDAP
     *
     * @param string $emplid emplid
     * @return moodle user id
     */
    public function create_from_emplid($emplid) {
        $ldap_people = $this->ldap_accessor->get_people_by_emplids(array($emplid));
        if (!array_key_exists($emplid, $ldap_people)) {

            throw new local_user_notinldap_exception("emplid not found in LDAP: $emplid");
        }
        $moodle_id = $this->create_from_ldap($ldap_people[$emplid]);
        return $moodle_id;
    }

    /**
     * helper function to create Moodle users by a specific field
     * with information from LDAP
     *
     * error/exception happen during the proces will be logged
     * while the process will continue. All the error messages
     * will be returned together with the list of created user IDs.
     *
     * Note: this function has been designed for mass-creation of user
     * records because the LDAP connection might be expensive.
     *
     * @param string $field 'emplid', 'x500'
     * @param mixed $ids string OR array of emplids or x500s
     * @return array
     *         'moodle_ids'    => array(<id> => <moodle_id>)
     *         'errors'        => array(<id> => string, error message)
     */
    protected function create_from_ldap_field($field, $ids) {
        $out = array('moodle_ids'    => array(),
                     'errors'        => array());

        // get information from LDAP
        if (!is_array($ids))
            $ids = array($ids);

        switch ($field) {
            case 'emplid':
                $ldap_people = $this->ldap_accessor->get_people_by_emplids($ids);
                break;

            case 'x500':
                $ldap_people = $this->ldap_accessor->get_people_by_uids($ids);
                break;

            default:
                throw new local_user_exception("Invalid param: unknown field '{$field}'");
        }

        // start creating
        foreach ($ids as $id) {
            if (!isset($ldap_people[$id]))
                $out['errors'][$id] = NO_MATCH_IN_LDAP_ERR_MSG;
            else {
                try {
                    $moodle_id = $this->create_from_ldap($ldap_people[$id]);
                    $out['moodle_ids'][$id] = $moodle_id;
                }
                catch(Exception $e) {
                    $out['errors'][$id] = "Error creating: {$e->getMessage()}";
                }
            }
        }

        return $out;
    }

    /**
     * create a Moodle user with information from LDAP
     * @param array $ldap_person
     */
    public function create_from_ldap($ldap_person) {
        global $DB;

        # TODO: Ensure that higher levels call check moodle/user:create
        #       capability since we removed the check here.  We did
        #       so because auto-enrollment must be able to create users.

        // merge the Moodle attributes that we get from LDAP into the user
        // with just the defaults.

        $default_user = $this->get_defaults();

        $user = array_merge($default_user, $ldap_person);

        $user_id = $DB->insert_record('user', $user); // exceptions (e.g. duplicate, ...) bubble up

        // add default messaging prefs
        $new_user = $DB->get_record('user', array('id' => $user_id));

        events_trigger('user_created', $new_user);

        return $user_id;
    }


    /**
     * return the default values for a new UMN user
     * @param $includes array fields to include, NULL for all
     * @param $excludes array fields to exclude, NULL for none
     * @return array
     */
    public static function get_defaults($includes = NULL, $excludes = NULL) {
        global $CFG;

        $defaults = array(
             'auth'                  => 'shibboleth',
             'mnethostid'            => $CFG->mnet_localhost_id,
             'confirmed'             => 1,
             'timecreated'           => time(),
             'lang'                  => 'en_us',
             'city'                  => empty($CFG->defaultcity) ? 'U of M' : $CFG->defaultcity,
             'country'               => empty($CFG->country) ? 'US' : $CFG->country,
             'timezone'              => 99,
             'descriptionformat'     => 1,
             'mailformat'            => 1,
             'maildigest'            => 1,
             'maildisplay'           => 2,
             'htmleditor'            => 1,
             'ajax'                  => 0,
             'autosubscribe'         => 0,
             'trackforums'           => 1,
             'screenreader'          => 0
        );

        // filter the excludes
        if (is_array($excludes)) {
            foreach ($excludes as $field) {
                if (isset($defaults[$field])) {
                    unset($defaults[$field]);
                }
            }
        }

        // filter the includes
        if (is_null($includes) || count($includes) == 0) {
            $out = $defaults;
        }
        else {
            foreach ($includes as $field) {
                if (isset($defaults[$field])) {
                    $out[$field] = $defaults[$field];
                }
            }
        }

        return $out;
    }



    /**
     * public function to perform additional operation when a user is created
     * @param mixed $event_data
     */
    public static function user_created_handler($user) {
        // Colin. Called this in 2.0. Commenting out initially in 2.2
        //        until we determine what we need to do. If uncommenting,
        //        check the new function name since 2.0.
        // message_set_default_message_preferences($user);

        return true;    // to clear the event from the queue
    }



    /**
     * search for accounts with missing email and backfill them with
     * new data (if available) from LDAP
     *
     * @return array of log data (see $logs)
     */
    public function backfill_missing_data() {
        global $DB;

        $logs = array('candidates'    => array(),
                      'updated'       => array(),
                      'no_update'     => array(),
                      'error'         => array());

        // search for accounts with empty email and Shib auth
        $user_records = $DB->get_records('user', array(
                'auth'    => 'shibboleth',
                'email'   => '',
                'deleted' => 0));

        if (count($user_records) == 0) {
            return $logs;
        }

        // build the list of x500s
        $uids   = array();
        $users  = array();

        foreach ($user_records as $user) {
            try {
                $uids[] = umn_ldap_person_accessor::moodle_username_to_uid($user->username);
                $users[$user->username] = $user;
                $logs['candidates'][] = $user->username;
            }
            catch(Exception $e) {
                $logs['error'][] = $e->getMessage();
            }
        }


        // query LDAP
        $ldap_users = $this->ldap_accessor->get_people_by_uids($uids);

        // update DB if new information is available
        foreach ($ldap_users as $ldap_user) {
            if (!empty($ldap_user['email'])) {
                $user = $users[$ldap_user['username']];
                $user->email = $ldap_user['email'];

                $DB->update_record('user', $user);
                $logs['updated'][] = $user->username;
            }
            else {
                $logs['no_update'][] = $ldap_user['username'];
            }
        }

        return $logs;
    }
}
