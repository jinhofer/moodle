<?php

require_once '../../config.php';

require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php');
require_once('backup_extension/activityclipboard_backup_controller.class.php');
require_once('backup_extension/activityclipboard_backup_plan.class.php');
require_once('backup_extension/activityclipboard_backup_task.class.php');
require_once('backup_extension/activityclipboard_backup_coursefiles_step.class.php');

require_once 'lib.php';
require_once 'activityclipboard_table.php';

function prepare_backup_controller($cm_id) {
    global $USER;

    $bc = new activityclipboard_backup_controller(backup::TYPE_1ACTIVITY,
                                $cm_id,
                                backup::FORMAT_MOODLE,
                                backup::INTERACTIVE_NO,
                                backup::MODE_GENERAL,
                                $USER->id);

    // We don't include user data.
    $users_setting = $bc->get_plan()->get_setting('users');
    if ($users_setting->get_value() == true) {
        $users_setting->set_value(false);
    }

    // Set filename
    $format = $bc->get_format();
    $type = $bc->get_type();
    $id = $bc->get_id();
    $users = $bc->get_plan()->get_setting('users')->get_value();
    $anonymized = $bc->get_plan()->get_setting('anonymize')->get_value();
    $filename = backup_plan_dbops::get_default_backup_filename($format,
                                                               $type,
                                                               $id,
                                                               $users,
                                                               $anonymized);

    $bc->get_plan()->get_setting('filename')->set_value($filename);

    // Must call this for setting changes to take effect before executing.
    $bc->set_status(backup::STATUS_AWAITING);
    return $bc;
}


$course_id  = required_param('course', PARAM_INT);
$cm_id      = required_param('module', PARAM_INT);
$return_to  = required_param('return', PARAM_LOCALURL);

// Check login and permissions.
require_login($course_id);
$context = context_course::instance($course_id);
require_capability('moodle/backup:backupactivity', $context);

try {
    define('BACKUP_SILENTLY', TRUE);

    $course = $DB->get_record('course', array('id' => $course_id));
    if (!$course)
            throw new activityclipboard_CourseException('Invalid course');

    $modinfo = get_fast_modinfo($course) and isset($modinfo->cms[$cm_id])
        or print_error('err_module_id', 'block_activityclipboard', $return_to);
    $cm = $modinfo->cms[$cm_id];

    $backup_controller = prepare_backup_controller($cm_id);

    $backup_controller->execute_plan();
    $results = $backup_controller->get_results();
    $file = $results["backup_destination"];
    $backup_controller->destroy();

    $activityclipboard       = new stdClass;
    $activityclipboard->userid = $USER->id;
    $activityclipboard->name = $cm->modname;
    $activityclipboard->icon = $cm->icon;
    $activityclipboard->text = $cm->name;
    $activityclipboard->time = time();
    $activityclipboard->contextid = $file->get_contextid();
    $activityclipboard->fileid = $file->get_id();
    $activityclipboard->sort = 0;
    activityclipboard_table::insert_record($activityclipboard);

    redirect($return_to);

} catch (activityclipboard_Exception $e) {
    error((string)$e);
}

