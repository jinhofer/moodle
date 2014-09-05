<?php

require('../../config.php');
require_once($CFG->dirroot.'/local/user/localuser_form.php');
require_once($CFG->dirroot.'/local/user/lib.php');
require_once($CFG->dirroot.'/local/ldap/lib.php');

$site = get_site();
require_login();

$PAGE->set_context(null); // hack - set context to something, by default to system context
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Bulk user creation");
$PAGE->set_heading($SITE->fullname);

$return_url = new moodle_url('/local/user/bulk_creation.php');
$PAGE->set_url($return_url);

$sitecontext = context_system::instance();

if (!has_capability('moodle/user:create', $sitecontext)) {
    print_error('nopermissions', 'error', '', 'create users');
}

if (!has_capability('local/user:usebulk', $sitecontext)) {
    print_error('nopermissions', 'error', '', 'bulk create users');
}


$x500_form = new local_user_bulk_create_form();

if ($form_data = $x500_form->get_data()) {
    //========= PROCESS SUBMITTED FORM =========
    if (empty($form_data->x500s)) {
        echo get_string('e_empty_input', 'local_user', $returnurl);
        $x500_form->display();
    }
    else {
        // get the bulk limit
        $bulk_limit = get_config('local/user', 'bulk_limit');    // how many usernames can be submitted at once

        if (!$bulk_limit)
            $bulk_limit = 1000;    // fall-back default value if no config found

        $user_creator = new local_user_creator();

        // parse the x500s
        $x500s = preg_split("/[\s,;]+/",
                            trim(str_replace(array("\t","\r","\n",'"',"'"), ' ', $form_data->x500s)));

        // reduce to unique values
        $x500s = array_unique($x500s);

        // apply the limit
        $allowed_x500s = array_slice($x500s, 0, $bulk_limit, false);

        // map the x500 into Moodle username
        $username_map = array();
        foreach ($allowed_x500s as $x500) {
            try {
                $username_map[umn_ldap_person_accessor::uid_to_moodle_username($x500)] = $x500;
            }
            catch(Exception $e) {
                // ignore error
            }
        }

        // check for existing records
        $rs = $DB->get_recordset_select(
            'user',
            'username IN ('.implode(',', array_fill(0, count($username_map), '?')).')',
            array_keys($username_map)
        );

        $existing_x500s = array();
        foreach ($rs as $row) {
            $existing_x500s[] = $username_map[$row->username];
        }
        $rs->close();

        // only submit non-existing x500s
        $candidate_x500s = array_diff($allowed_x500s, $existing_x500s);

        $result = array();
        if (count($candidate_x500s) > 0) {
            $result = $user_creator->create_from_x500s($candidate_x500s); // exception bubbles up
        }

        $SESSION->__POST_RESULT_local_user = array(
            'submitted_count' => count($x500s),
            'existing_x500s'  => $existing_x500s,
            'skipped_x500s'   => array_slice($x500s, $bulk_limit),
            'processed_x500s' => $allowed_x500s,
            'result'          => $result
        );
    }

    // display result
    redirect($return_url);
}
else {
    //========= DISPLAY PAGE =========
    echo $OUTPUT->header();

    if (isset($SESSION->__POST_RESULT_local_user)) {
        // cache resulted available, display and clear

        $existing_x500s  = $SESSION->__POST_RESULT_local_user['existing_x500s'];
        $skipped_x500s   = $SESSION->__POST_RESULT_local_user['skipped_x500s'];
        $submitted_count = $SESSION->__POST_RESULT_local_user['submitted_count'];
        $processed_x500s = $SESSION->__POST_RESULT_local_user['processed_x500s'];
        $result          = $SESSION->__POST_RESULT_local_user['result'];

        echo $OUTPUT->box_start('result summary');
        echo '<h2>', get_string('input_header', 'local_user'), '</h2>';
        echo '<h4>', get_string('result_summary', 'local_user'), '</h4>';
        echo 'Submitted (unique): ', $submitted_count, '<br/>';
        echo 'Processed: ', count($processed_x500s), '<br/>';
        echo 'Created: ', isset($result['moodle_ids']) ? count($result['moodle_ids']) : 0, '<br/>';
        echo 'Error: ', isset($result['errors']) ? count($result['errors']) : 0, '<br/><br/>';
        echo $OUTPUT->box_end();

        if (isset($result['moodle_ids']) && count($result['moodle_ids']) > 0) {
            echo $OUTPUT->box_start('result success');
            echo '<h3>', get_string('result_created', 'local_user'), '</h3>';

            foreach ($result['moodle_ids'] as $x500 => $mid) {
                $link = new moodle_url('/user/profile.php', array('id' => $mid));
                echo "{$x500}: ", get_string('result_status_success', 'local_user'),
                     " <a href=\"{$link}\">{$mid}</a><br/>";
            }

            echo $OUTPUT->box_end();
            echo '<br/><br/>';
        }

        // print existing users
        if (count($existing_x500s) > 0) {
            echo $OUTPUT->box_start('result error');
            echo '<h3>', get_string('result_existed', 'local_user'), '</h3>';
            echo implode(', ', $existing_x500s);
            echo $OUTPUT->box_end();
            echo '<br/><br/>';
        }

        // print skipped users
        if (count($skipped_x500s) > 0) {
            echo $OUTPUT->box_start('result error');
            echo '<h3>', get_string('result_skipped', 'local_user'), '</h3>';
            echo implode(', ', $skipped_x500s);
            echo $OUTPUT->box_end();
            echo '<br/><br/>';
        }


        // print errors
        if (isset($result['errors']) && count($result['errors']) > 0) {
            echo $OUTPUT->box_start('result error');
            echo '<h3>', get_string('result_error', 'local_user'), '</h3>';

            foreach ($result['errors'] as $x500 => $msg) {
                echo "{$x500}: {$msg}<br/>";
            }

            echo $OUTPUT->box_end();
        }

        // clear cached result
        unset($SESSION->__POST_RESULT_local_user);
    }
    else {
        // no cached result, display the form
        $x500_form->display();
    }

    echo $OUTPUT->footer();

    die;
}