<?php

class local_course_url_parse_exception extends Exception {}
class local_course_wwwroot_parse_exception extends Exception {}

class local_course_invalidcourseid_exception extends Exception {
    public $courseid;
    public function __construct($message, $courseid) {
        $this->courseid = $courseid;
        parent::__construct($message, 0);
    }
}

class local_course_invalidinstancename_exception extends Exception {
    public $instancename;
    public function __construct($message, $instancename) {
        $this->instancename = $instancename;
        parent::__construct($message, 0);
    }
}

/**
 * Used to get the requestid for filenames involved in migrations
 * between servers.
 */
function local_course_requestid_from_filename($filename) {
    // filename is of the form "base.requestid.ext" or
    // "requestid.ext".
    // First use PATHINFO_FILENAME to get
    // "base.requestid" or "requestid", and then PATHINFO_EXTENSION.
    // If the latter returns null, send the result of the first.
    $base_requestid = pathinfo($filename, PATHINFO_FILENAME);
    $requestid_as_ext = pathinfo($base_requestid, PATHINFO_EXTENSION);
    $requestid = $requestid_as_ext ?: $base_requestid;
    return $requestid;
}

/**
 * Returns a preg_match matches array appended with the "instance name".
 * This instance name is the instance part of the URL with all '/' characters
 * replaced by '_' characters. The indexes point to the following:
 *  0 - the matched string, which can only be $course_url
 *  1 - the wwwroot for the instance hosting the course
 *  2 - the instance part of the URL without the 'https://' prefix
 *  3 - the course id
 *  4 - the instance name (same as 2 with '_' replacing '/')
 */
function local_course_parse_courseurl($course_url) {
    if (preg_match('|^(https://([\w\./-]+))/course/view.php\?id=(\d+)$|',
                   $course_url,
                   $matches))
    {
        $instance_name = str_replace('/', '_', $matches[2]);
        $matches[] = $instance_name;
        return $matches;
    } else {
        throw new local_course_url_parse_exception("Invalid course URL: $course_url");
    }
}

function local_course_parse_courseurl_instancename($course_url) {
    $matches = local_course_parse_courseurl($course_url);
    return $matches[4];
}

/**
 *
 */
function local_course_parse_courseurl_courseid($course_url, $requiredinstance=null) {
    $matches = local_course_parse_courseurl($course_url);
    if ($requiredinstance and $matches[4] != $requiredinstance) {
        throw new Exception("$course_url is not in instance $requiredinstance");
    }
    return $matches[3];
}

/**
 * Returns a preg_match matches array appended with the "instance name". 
 * This instance name is the instance part of the URL with all '/' characters
 * replaced by '_' characters. The indexes point to the following:
 *  0 - the matched string, which can only be $wwwroot
 *  1 - the instance part of the URL without the 'https://' prefix
 *  2 - the instance name (same as 1 with '_' replacing '/')
 */
function local_course_parse_wwwroot($wwwroot) {
    if (preg_match('|^https://([\w\./-]+[\w])$|', $wwwroot, $matches)) {
        $instance_name = str_replace('/', '_', $matches[1]);
        $matches[] = $instance_name;
        return $matches;
    } else {
        throw new local_course_wwwroot_parse_exception("Invalid wwwroot string: $wwwroot");
    }
}

// The instance name is wwwroot without "https://" and with '/' replace with '_'.
function local_course_this_instancename() {
    global $CFG;
    $wwwroot = $CFG->wwwroot;
    $matches = local_course_parse_wwwroot($wwwroot);
    return $matches[2];
}

/**
 *
 */
function local_course_get_migration_servers() {
    global $DB, $CFG;

# TODO: Need to do something to indicate whether the upgradeinstanceid
#       represents a server that is flagged as an upgrade server in
#       mdl_instances.

    $sql = "
select rs.*, src.wwwroot as migrationserverwwwroot, upg.wwwroot as upgradeserverwwwroot
from {course_request_servers} rs
  join {moodle_instances} src on src.id=rs.sourceinstanceid
  join {moodle_instances} req on req.id=rs.requestinginstanceid
  left join {moodle_instances} upg on upg.id=rs.upgradeinstanceid
where req.wwwroot=:wwwroot
order by src.name
";

    return $DB->get_records_sql($sql, array('wwwroot'=>$CFG->wwwroot));
}


/**
 * For the migration server map, we want the names of the servers
 * (or "sources") and the upgrade server, if any, that we need
 * to go through. (If the server is a 1.9 instance, we need to go
 * through an upgrade server.) We do not select any rows specifying
 * an upgrade server in mdl_course_request_servers that is not flagged
 * as an upgrade server in mdl_instances.
 */
function local_course_get_migration_server_map() {
    global $DB, $CFG;

    $sql = "
select src.name source, ifnull(upg.name,'') upgrader
from {course_request_servers} rs
  join {moodle_instances} src on src.id=rs.sourceinstanceid
  join {moodle_instances} req on req.id=rs.requestinginstanceid
  left join {moodle_instances} upg on upg.id=rs.upgradeinstanceid
where rs.enabled
  and req.wwwroot=:wwwroot
  and upg.isupgradeserver is null or upg.isupgradeserver<>0
order by src.name
";

    return $DB->get_records_sql_menu($sql, array('wwwroot'=>$CFG->wwwroot));
}

/**
 * The migration client list should include only the Moodle instances,
 * including upgrade servers, that can send requests directly to this
 * instance.
 */
function local_course_get_migration_clients() {
    global $DB, $CFG;

    $sql = "
select rs.*, req.wwwroot as migrationclientwwwroot
from {course_request_servers} rs
  join {moodle_instances} src on src.id=rs.sourceinstanceid
  join {moodle_instances} req on req.id=rs.requestinginstanceid
where src.wwwroot=:wwwroot
order by req.name
";

    return $DB->get_records_sql($sql, array('wwwroot'=>$CFG->wwwroot));
}

/**
 * The migration client list should include only the Moodle instances,
 * including upgrade servers, that can send requests directly to this
 * instance.
 */
function local_course_get_migration_client_names() {
    global $DB, $CFG;

    $sql = "
select req.name requester
from {course_request_servers} rs
  join {moodle_instances} src on src.id=rs.sourceinstanceid
  join {moodle_instances} req on req.id=rs.requestinginstanceid
where rs.enabled
  and src.wwwroot=:wwwroot
  and (rs.upgradeinstanceid is null or rs.upgradeinstanceid=0)
order by req.name
";

    return $DB->get_fieldset_sql($sql, array('wwwroot'=>$CFG->wwwroot));
}

/**
 * The $sourceinstancename must be listed in mdl_course_request_servers
 * as a source instance for the current instance.
 */
function is_valid_remote_instancename($sourceinstancename) {
    global $CFG;

    $map = local_course_get_migration_server_map();
    return array_key_exists($sourceinstancename, $map);
}

/**
 *
 */
function is_valid_remote_instance($sourcecourseurl) {
    if (empty($sourcecourseurl)) return false;

    $instancename = local_course_parse_courseurl_instancename($sourcecourseurl);
    return is_valid_remote_instancename($instancename);
}

/**
 * Runs checks on the sourcecourseurl and throws exception
 * if problem found.
 */
function local_course_validate_sourcecourseurl($sourcecourseurl) {
    global $DB;

    $matches = local_course_parse_courseurl($sourcecourseurl);
    $courseid     = $matches[3];
    $instancename = $matches[4];

    $thisinstance = local_course_this_instancename();

    if ($instancename == $thisinstance) {
        if (!$DB->record_exists('course', array('id'=> $courseid))) {
            throw new local_course_invalidcourseid_exception(
                        "$courseid is not valid for instance $instancename",
                        $courseid);
        }
    } else if (!is_valid_remote_instancename($instancename)) {
        throw new local_course_invalidinstancename_exception(
                        "$instancename is not a valid source for courses",
                        $instancename);
    }
}

/**
 * General helper to return a mapped value or the key if none.
 */
function attempt_mapping($key, $array) {
    return array_key_exists($key, $array) ? $array[$key] : $key;
}

/**
 * Splits x500 text into an array.  Intended for use with the instructor
 * and designer fields on the course request form.
 * Returns array of unique x500s.
 */
function split_x500_text_list($text) {
    $x500s = preg_split("/[\s,\/\\+;'\"&]+/", $text, null, PREG_SPLIT_NO_EMPTY);
    return array_unique($x500s);
}

/**
 * Throws an exception if the last JSON operation had an error.
 */
function check_json_status() {
    if (json_last_error() == JSON_ERROR_NONE) {
        return true;
    }

    switch (json_last_error()) {
        case JSON_ERROR_DEPTH:          $val = 'JSON_ERROR_DEPTH';          break;
        case JSON_ERROR_CTRL_CHAR:      $val = 'JSON_ERROR_CTRL_CHAR';      break;
        case JSON_ERROR_STATE_MISMATCH: $val = 'JSON_ERROR_STATE_MISMATCH'; break;
        case JSON_ERROR_SYNTAX:         $val = 'JSON_ERROR_SYNTAX';         break;
        case JSON_ERROR_UTF8:           $val = 'JSON_ERROR_UTF8';           break;
        default:                        $val = 'Unknown';                   break;
    }
    throw new Exception("JSON error: $val");
}

function local_course_build_category_string($categoryid) {
    global $DB;

    $sql =<<<SQL
select c.name, c2.name as parentname
from {course_categories} c
  left join {course_categories} c2 on c2.id=c.parent
where c.id=:categoryid
SQL;
    
    $category = $DB->get_record_sql($sql, array('categoryid' => $categoryid));

    return (empty($category->parentname) ? '' : $category->parentname . ' | ') . $category->name;
}

