<?php

require_once '../../config.php';

require_once($CFG->dirroot.'/backup/util/includes/restore_includes.php');
require_once('backup_extension/activityclipboard_restore_controller.class.php');
require_once('backup_extension/activityclipboard_restore_plan.class.php');
require_once('backup_extension/activityclipboard_restore_task.class.php');
require_once('backup_extension/activityclipboard_restore_coursefiles_step.class.php');
require_once('backup_extension/activityclipboard_restore_root_task.class.php');

require_once 'activityclipboard_table.php';

// 20140227 mart0969 - Must include this file here to check whether file is
// compressed as tar.gz instead of .zip
require_once($CFG->libdir.'/filestorage/tgz_packer.php');

$sc_id      = required_param('id', PARAM_INT);
$course_id  = required_param('course', PARAM_INT);
$section_number = required_param('section', PARAM_INT);
$return_to  = required_param('return', PARAM_LOCALURL);

$context = context_course::instance($course_id);
require_capability('moodle/restore:restoreactivity', $context);
require_capability('moodle/course:manageactivities', $context);

$activityclipboard = activityclipboard_table::get_record_by_id($sc_id)
    or print_error('err_shared_id', 'block_activityclipboard', $return_to);

$activityclipboard->userid == $USER->id
    or print_error('err_capability', 'block_activityclipboard', $return_to);

$fs = get_file_storage();
$file = $fs->get_file_by_id($activityclipboard->fileid);
// 20140227 mart0969 - Check if file is compressed as .tar.gz or .zip
// and get appropriate file packer
if (tgz_packer::is_tgz_file($file)) {
    $packer = get_file_packer('application/x-gzip');
} else {
    $packer = get_file_packer('application/zip');
}
$packer->extract_to_pathname($file, $CFG->dataroot."/temp/backup/activityclipboard");

$rc = new activityclipboard_restore_controller("activityclipboard", $course_id, backup::INTERACTIVE_NO,
                 backup::MODE_IMPORT, $USER->id, backup::TARGET_CURRENT_ADDING);

// Get the target course_section id given the course and the section number that the
// user has selected as the target location of the copied activity.
$sqlparams = array(
    'course' => $course_id,
    'section' => $section_number);
$coursesectionid = $DB->get_field('course_sections', 'id', $sqlparams);

// Set the target section id in the restore controller so that the copied activity
// ends up in the right section.
$rc->set_newsectionid($coursesectionid);

$rc->execute_precheck(true);
// $rc->get_precheck_results();
$rc->set_status(backup::STATUS_AWAITING);
$rc->execute_plan();
redirect($return_to);

