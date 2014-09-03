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
 * the library of helper functions providing functionality to interact with
 * UMN LDAP system
 *
 * @package   local
 * @subpackage ldap
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * specific exception used by ldap package
 */
class ldap_accessor_exception extends Exception {}


/**
 * Standard base class for accessing LDAP.
 *
 * @package   local
 * @subpackage    ldap
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ldap_accessor {

    /**
     * hold the connection/link resource to the LDAP server
     * (once connected)
     * @var int
     */
    protected $link;

    /**
     * the default base_db for the connection
     * @var string
     */
    protected $base_dn;

    /**
     * the variable for the bind_password
     * @var string
     */
    private $bind_password;

    /**
     * the variable for the bind_dn
     * @var string
     */
    private $bind_dn;

    /**
     * the variable for the ldap host_url
     * @var string
     */
    private $host_url;

    /**
     *
     * @param string $host_url address of the host (ldap:// or ldaps://)
     * @param string $bind_dn
     * @param string $bind_password
     * @param string $base_dn
     * @throws ldap_accessor_exception
     */
    public function __construct($host_url, $bind_dn, $bind_password, $base_dn) {
        $this->base_dn       = $base_dn;
        $this->host_url      = $host_url;
        $this->bind_password = $bind_password;
        $this->bind_dn       = $bind_dn;
    }

    /**
     * return a bound ldap connection
     * @return resource see php::ldap_connect
     */
    protected function get_link() {

        if (! $this->link) {
            $this->link = ldap_connect($this->host_url);

            if (! $this->link) {
                throw new ldap_accessor_exception("ldap_connect failure attempting to connect to {$this->host_url}");
            }

            ldap_set_option($this->link, LDAP_OPT_PROTOCOL_VERSION, 3);

            if (! ldap_bind($this->link, $this->bind_dn, $this->bind_password)) {
                throw new ldap_accessor_exception('ldap_bind failure: ' . ldap_error($this->link));
            }
        }

        return $this->link;
    }


    /**
     * search for a resource on the connected LDAP server
     * @param string $filter see php::ldap_search()
     * @param array $attributes see php::ldap_search()
     * @return array see php::ldap_get_entries()
     */
    public function search($filter, $attributes) {
        $search_results = ldap_search($this->get_link(), $this->base_dn, $filter, $attributes);

        if (! $search_results) {
            throw new ldap_accessor_exception('ldap_search failure: ' . ldap_error($this->get_link()));
        }

        $entries = ldap_get_entries($this->get_link(), $search_results);

        if (! $entries) {
            throw new ldap_accessor_exception('ldap_get_entries failure: ' . ldap_error($this->get_link()));
        }

        return $entries;
    }
}



/**
 * specific LDAP accessor to get UMN persons
 *
 */
class umn_ldap_person_accessor extends ldap_accessor {

    /**
     * list of attributes to retrieve from UMN LDAP
     *
     * We don't need to specify the dn because it is always returned and
     * has a unique role in ldap, anyway.
     *
     * @var array
     */
    protected static $ldap_attributes = array(
            'uid',
            'umndisplaymail',
            'umnemplid',
            'sn',
            'givenname',
            'preferredrfc822recipient');


    /**
     * automatically connect and bind to UMN LDAP on creation
     */
    public function __construct() {
        // use the same settings in LDAP authentication module
        $ldap_configs = get_config('auth/ldap');

        parent::__construct(
            $ldap_configs->host_url,
            $ldap_configs->bind_dn,
            $ldap_configs->bind_pw,
            $ldap_configs->contexts
        );
    }


    /**
     * generic function to get people by LDAP ID attribute,
     * and map them back to Moodle attribute (by Moodle ID attribute)
     *
     * Note: the IDM team suggested sending individual requests
     * (instead of single combined request) as the LDAP server
     * is not optimized for combined filter.
     *
     * A rough test showed for 300 ids:
     *     - single combined request: 58.6 seconds
     *     - multiple individual requests: 2.5 seconds
     *
     * @param string $mdl_id_attr the Moodle ID attribute to map to (e.g. username)
     * @param string $ldap_id_attr the LDAP ID attribute to search for (e.g. uid)
     * @param array $ids list of ID values to search for
     * @return array
     *         <id as passed in>    => array, @see return by process_person()
     * @throws ldap_accessor_exception
     */
    public function get_people_by_unique_ids($mdl_id_attr, $ldap_id_attr, $ids) {
        if (!is_array($ids))
            $ids = array($ids);

        $people = array();
        foreach ($ids as $id) {
            // skip if this ID would cause error response "server unwilling to perform"
            if (trim($id) == '') {
                continue;
            }

            $search_str = "({$ldap_id_attr}={$id})";

            $ldap_person = $this->search($search_str, self::$ldap_attributes);

            if ($ldap_person['count'] > 0) {
                $person = $this->process_person($ldap_person[0]);
                $people[$ldap_person[0][$ldap_id_attr][0]] = $person;
            }
        }

        return $people;
    }


    /**
     * wrapper function to search for people by UID
     * @param array $ids
     * @return array
     */
    public function get_people_by_uids($ids) {
        return $this->get_people_by_unique_ids('username', 'uid', $ids);
    }


    //

    /**
     * The UMN Moodle implementation stores the emplid as the Moodle idnumber,
     * so this wrapper returns the emplid as 'idnumber' in the returned attributes.
     *
     * @param array $ids
     * @return array
     */
    public function get_people_by_emplids($ids) {
        return $this->get_people_by_unique_ids('idnumber', 'umnemplid', $ids);
    }


    /**
     * map a person record from LDAP to Moodle format
     *
     * @param array $person_entry
     * @return array
     *         'username'        => string, Moodle username (x500@umn.edu)
     *         'firstname'        => string
     *         'lastname'        => string
     *         'idnumber'         => string
     *         'email'            => string
     */
    protected function process_person($person_entry) {
        // dn is accessed more directly than other LDAP
        // attributes and can have only a single value.

        // set required default value in case they are missing in LDAP
        $attributes = array('firstname'     => '',
                            'lastname'      => '');

        // See also ldap_uids_to_moodle_username.
        $attributes['username']  = $this->uid_to_moodle_username($person_entry['uid'][0]);

        if (isset($person_entry['givenname'])) {
            $attributes['firstname'] = $person_entry['givenname'][0];
        }

        if (isset($person_entry['sn'])) {
            $attributes['lastname']  = $person_entry['sn'][0];
        }

        if (isset($person_entry['umnemplid'])) {
            $attributes['idnumber']  = $person_entry['umnemplid'][0];
        }

        // If user is a guest, we use the preferredrfc822recipient email
        // since that is their actual email address of record.

        $dn = $person_entry['dn'];

        if (strpos($dn, ',ou=Guests,') === false) {
            if (isset($person_entry['umndisplaymail'])) {
                $attributes['email'] = $person_entry['umndisplaymail'][0];
            }
            else {
                $attributes['email'] = '';
            }
        }
        else {
            $attributes['email'] = $person_entry['preferredrfc822recipient'][0];
        }

        return $attributes;
    }


    /**
     * convert (list of) UIDs into Moodle usernames
     *
     * @param mixed $uids string OR array of strings
     * @return mixed string OR array
     * @throws ldap_accessor_exception if one of the UIDs is invalid
     *            (e.g. contains '@')
     */
    public static function uid_to_moodle_username($uids) {
        if (!is_array($uids))
            $uids = array($uids);

        $out = array();

        foreach ($uids as $uid) {
            if (strstr($uid, '@') !== false)
                throw new ldap_accessor_exception("uid_to_moodle_username: invalid character in UID '{$uid}'");
            $out[] = $uid.'@umn.edu';
        }

        if (count($out) == 1)
            return $out[0];

        return $out;
    }


    /**
     * convert (list of) Moodle usernames into LDAP UIDs
     * (by removing '@umn.edu')
     *
     * @param mixed $moodle_usernames string OR array of strings
     * @return mixed string OR array
     * @throws ldap_accessor_exception if one of the usernames is not a valid UMN email
     */
    public static function moodle_username_to_uid($moodle_usernames) {
        if (!is_array($moodle_usernames))
            $moodle_usernames = array($moodle_usernames);

        foreach ($moodle_usernames as $uname) {
            if (strpos($uname, '@umn.edu') === false)
                throw new ldap_accessor_exception("moodle_username_to_uid: invalid username for UMN '{$uname}'");
        }

        $out =  str_replace('@umn.edu', '', $moodle_usernames);

        if (count($out) == 1)
            return $out[0];

        return $out;
    }

}
