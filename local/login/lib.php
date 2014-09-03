<?php

namespace local_login;

defined('MOODLE_INTERNAL') || die();

/** 
 * Check to determine whether the URL that the user is trying to reach is
 * something that a guest user might have access to.  If any doubt, we assume
 * that the guest does have access.  The intent is that the login logic will
 * try passive shib login unless it is certain that guests are not
 * allowed access to wantsurl.
 */
function is_wantsurl_possibly_ok_for_guest() {
    global $SESSION, $CFG;

    if (! isset($SESSION->wantsurl))
        return false;

    if (strpos($SESSION->wantsurl, $CFG->wwwroot) !== 0)
        return false;

    // This check assumes that any landing page for guest will have
    // a query parameter (such as a course id) in the URL.
    if (strpos($SESSION->wantsurl, '?') === false)
        return false;

    // If we can get a course id from the URL and
    // the associated course does not have guest enrollment
    // enabled then it should be safe to assume that guests
    // cannot access the wantsurl destination.
    $courseid = get_url_courseid($SESSION->wantsurl);
    if ($courseid and ! has_possible_guest_enrol($courseid))
        return false;

    return true;
}

/**
 * Attempts to extract and return a course id from $url.  Returns null
 * if unable to do so.
 */
function get_url_courseid($url) {

    $courseid = null;

    $parsedurl = parse_url($url);
    if (strpos($parsedurl['path'], 'course/view.php') !== false) {
        $querystring = $parsedurl['query'];
        parse_str($querystring, $query);
        if (isset($query['id'])) {
            $courseid = $query['id'];
        }
    }
    return $courseid;
}

/**
 * Returns true if the course allows guest enrollment but requires a
 * password for guests.  Intended for use by require_login to determine
 * whether to send access-denied guest users to the login page or to
 * enrol/index.php (which would present the password entry form).
 */
function course_allows_guest_with_password($courseid) {
    global $DB;

    // This assumes that the password for guest enrol is always non-null
    // and is set to '' (an empty string) if no password is required.

$sql =<<<SQL
select id from {enrol}
where courseid=:courseid and
      enrol='guest' and
      status=:status and
      password <> ''
SQL;

    $rv = $DB->record_exists_sql($sql,
                                 array('courseid' => $courseid,
                                       'status'   => ENROL_INSTANCE_ENABLED));

    return $rv;
}

/**
 * Checks $courseid course for enabled guest enrollment.
 */
function has_possible_guest_enrol($courseid) {
    global $DB;

    return $DB->record_exists('enrol',
                              array('courseid' => $courseid,
                                    'enrol'    => 'guest',
                                    'status'   => ENROL_INSTANCE_ENABLED));
}

