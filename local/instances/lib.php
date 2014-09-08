<?php

function get_this_moodle_instanceid() {
    global $DB, $CFG;

    return $DB->get_field('moodle_instances',
                          'id',
                          array('wwwroot' => $CFG->wwwroot),
                          MUST_EXIST);
}

function get_instancename_from_wwwroot($wwwroot) {
    global $CFG;

    // TODO: This duplicates some of the wwwroot logic in local/course/helpers and
    //       local/instances/edit_form.php.  Ideally,
    //       we should have the logic in one place--probably in a local/instances/lib.php.
    // The cfgwwwrootregex check is to accommodate Moodle PHPUnit initialization, which sets
    // up a non-https wwwroot.  Might be useful in other dev scenarios, too.
    $cfgwwwrootregex = str_replace('://', '://(', str_replace('.', '\.', $CFG->wwwroot)) . ')';

    if (preg_match('|^https://([\w\./-]+[\w])$|', $wwwroot, $matches)
        or preg_match("|^$cfgwwwrootregex$|", $wwwroot, $matches))
    {
        return str_replace('/', '_', $matches[1]);
    } else {
        throw new Exception("Invalid wwwroot: $wwwroot");
    }
}

function add_moodle_instance($data) {
    global $DB;

    $data->name = get_instancename_from_wwwroot($data->wwwroot);

    $id = $DB->insert_record('moodle_instances', $data);
    return $id;
}

function add_this_moodle_instance() {
    global $CFG;

    $instancedata = new stdClass;
    $instancedata->wwwroot = $CFG->wwwroot;
    return add_moodle_instance($instancedata);
}

function update_moodle_instance($data) {
    global $DB;

    $data->name = get_instancename_from_wwwroot($data->wwwroot);

    $DB->update_record('moodle_instances', $data);
}

