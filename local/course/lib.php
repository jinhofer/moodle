<?php

/**
 * This file contains functions that could be called by pages, forms,
 * formhandlers, and cli scripts to access functionality in the
 * plugin.
 *
 * One goal is to keep the vast majority of the implementation
 * out of the files that include this lib and in the implementation
 * files to which this lib provides access.
 *
 * Another goal is avoid any circular dependencies involving this lib.
 */

require_once("$CFG->dirroot/local/ppsft/lib.php");
require_once("$CFG->dirroot/local/user/lib.php");
require_once("$CFG->dirroot/course/lib.php");
require_once("$CFG->dirroot/local/course/migration_responder.class.php");
require_once("$CFG->dirroot/local/course/shared_fs_course_xfer.php");
require_once("$CFG->dirroot/local/course/course_backup_wrapper.class.php");
require_once("$CFG->dirroot/local/course/course_restore_wrapper.class.php");
require_once("$CFG->dirroot/local/course/course_creator.class.php");
require_once("$CFG->dirroot/backup/util/includes/backup_includes.php");
require_once("$CFG->dirroot/backup/util/includes/restore_includes.php");
require_once("$CFG->dirroot/local/course/course_request_manager.class.php");
require_once("$CFG->dirroot/local/course/helpers.php");
require_once("$CFG->dirroot/local/course/constants.php");
require_once("$CFG->libdir/adminlib.php");
require_once("$CFG->libdir/coursecatlib.php");

function local_course_extends_settings_navigation($settingsnav, $context) {
    global $CFG, $COURSE, $PAGE;

    // STRY0010024 20140807 dhanzely - Add 'Category course settings' link to category admin block
    if ($context->contextlevel == CONTEXT_COURSECAT) {
        if (has_capability('local/course:managecoursesettings', $context) && $categorynode = $settingsnav->find('categorysettings', navigation_node::TYPE_UNKNOWN)) {
            $editurl = new moodle_url('/local/course/editcategorycoursesettings.php', array('categoryid' => $context->instanceid));
            $node = navigation_node::create(get_string('editcategorycoursesettingsthis', 'local_course'), $editurl, navigation_node::TYPE_SETTING, null, 'editcoursesettings', new pix_icon('i/settings', ''));
            $categorynode->add_node($node, 'roles');
        }
    }

    if ($context->contextlevel == CONTEXT_COURSE) {

        if (has_capability('moodle/course:visibility', $context) && $courseadminnode = $settingsnav->find('courseadmin', navigation_node::TYPE_UNKNOWN)) {

            if ($COURSE->visible) {

                $editurl = new moodle_url('/local/course/course_visibility.php',
                                          array('id' => $context->instanceid, 'courseshow' => 0));
                $node = navigation_node::create(get_string('coursehide', 'local_course'),
                                                $editurl,
                                                navigation_node::TYPE_SETTING,
                                                null,
                                                'showhidecoursesettings',
                                                new pix_icon('i/hide', ''));
                $courseadminnode->add_node($node, 'editsettings');
            } else {

                $editurl = new moodle_url('/local/course/course_visibility.php',
                                          array('id' => $context->instanceid, 'courseshow' => 1));
                $node = navigation_node::create(get_string('courseshow', 'local_course'),
                                                $editurl,
                                                navigation_node::TYPE_SETTING,
                                                null,
                                                'showhidecoursesettings',
                                                new pix_icon('i/show', ''));
                $courseadminnode->add_node($node, 'editsettings');
            }
        }
    }
}

/**
 * Admin setting that allows a user to pick appropriate roles for something.
 * Limits roles to only those with course context level.
 * Intended for use by course request settings page courserequestadditionalroles.
 * Consider making more general by making contextlevel a constructor parameter.
 */
class local_course_setting_pickcourseroles extends admin_setting_pickroles {

    public function load_choices() {
        global $DB;
        if (during_initial_install()) {
            return false;
        }
        if (is_array($this->choices)) {
            return true;
        }

        $sql =<<<SQL
select r.*
from {role} r
join {role_context_levels} rc on rc.roleid=r.id
where rc.contextlevel=:contextlevel
order by r.sortorder
SQL;

        if ($roles = $DB->get_records_sql($sql, array('contextlevel'=>CONTEXT_COURSE))) {
            $this->choices = role_fix_names($roles, null, ROLENAME_ORIGINAL, true);
            return true;
        } else {
            return false;
        }
    }

}

/**
 * STRY0010024 20140807 dhanzely
 *
 * Returns ID of course to use as a template. Defaults to $CFG->defaultsourcecourseid
 *
 * @param int $categoryid
 * @return int
 */
function get_category_course_template_id($categoryid) {
    global $CFG;

    $category = coursecat::get($categoryid);

    if (empty($category)) {
        return null;
    }

    $parents = $category->get_parents();
    $parents[] = $categoryid;
    foreach (array_reverse($parents) as $parent) {
        $sourcecourseid = get_category_course_setting($parent, 'defaultsourcecourseid');
        if ($sourcecourseid) return $sourcecourseid;
    }

    return $CFG->defaultsourcecourseid;
}

/**
 * STRY0010024 20140807 dhanzely
 *
 * Returns Category Course Theme if one is configured, otherwise null.
 *
 * @param int $categoryid
 * @return string|null
 */
function get_category_course_theme($categoryid) {
    global $CFG;

    $category = coursecat::get($categoryid);

    if (empty($category)) {
        return null;
    }

    $parents = $category->get_parents();
    $parents[] = $categoryid;
    foreach (array_reverse($parents) as $parent) {
        $theme = get_category_course_setting($parent, 'theme');
        if ($theme) return $theme;
    }

    return null;
}

/**
 * STRY0010024 20140807 dhanzely
 *
 * Returns an array of all Category Course Settings.
 *
 * @param int $categoryid
 * @return array
 */
function get_category_course_settings($categoryid) {
    global $DB;

    $settings_array = array();

    $settings = $DB->get_records('course_request_category_conf', array('categoryid' => $categoryid));
    if (empty($settings)) return $settings_array;

    foreach ($settings as $setting) {
        $settings_array[$setting->name] = $setting->value;
    }

    return $settings_array;
}

/**
 * STRY0010024 20140807 dhanzely
 *
 * Returns the value of a specific Category Course Setting.
 *
 * @param int $categoryid
 * @param string $settingname
 * @return string|null
 */
function get_category_course_setting($categoryid, $settingname) {
    global $DB;

    $data = $DB->get_record('course_request_category_conf', array('categoryid' => $categoryid, 'name' => $settingname));

    if (empty($data)) return null;

    return isset($data->value) ? $data->value :  null;
}

/**
 * STRY0010024 20140807 dhanzely
 *
 * Saves a Category Course Setting. If the $newvalue is null, the setting will be
 * deleted from the DB if it exists.
 *
 * @param int $categoryid
 * @param string $settingname
 * @param string|int|null $newvalue
 * @return bool
 */
function save_category_course_setting($categoryid, $settingname, $newvalue) {
    global $DB;

    $setting = $DB->get_record('course_request_category_conf', array('categoryid' => $categoryid, 'name' => $settingname));

    // Do nothing if setting has not been previously saved and attempting to save an empty value,
    // OR if previously saved value is the same as the new value.
    if ((empty($setting) && empty($newvalue)) || (isset($setting->value) && $setting->value == $newvalue)) {
        return true;
    }

    // Initialize setting if needed
    if (empty($setting)) {
        $setting = new stdClass();
        $setting->categoryid = $categoryid;
        $setting->name = $settingname;
        $setting->value = null;
    }

    // Preserve previous value and update setting with new value
    $oldvalue = $setting->value;
    $setting->value = $newvalue;

    // Save or delete setting in DB
    if (isset($setting->id)) {
        if (empty($setting->value)) {
            $success = $DB->delete_records('course_request_category_conf', array('id' => $setting->id));
        } else {
            $success = $DB->update_record('course_request_category_conf', $setting);
        }
    } else {
        $success = $DB->insert_record('course_request_category_conf', $setting);
    }

    // Record changes to the config log
    add_to_config_log(
        'category_course_setting',
        json_encode(array(
            'categoryid' => $categoryid,
            $settingname => $oldvalue,
        )),
        json_encode(array(
            'categoryid' => $categoryid,
            $settingname => $newvalue,
        )),
        'local_course'
    );

    return $success ? true : false;
}

function get_course_request_assignable_roles() {
    global $DB;

    static $rolemenu;

    if ( empty($rolemenu) ) {
        $rolesstring = get_config(null, 'courserequestadditionalroles');
        if (! $rolesstring) return;

        // TODO: Overkill?
        $roleids = explode(',', $rolesstring);
        $roleids = array_filter($roleids, function($a) { return ctype_digit($a); });
        if (0 === count($roleids)) return;
        $rolestring = implode(',', $roleids);

        $roles = $DB->get_records_select('role',
                                         "id in ($rolestring)",
                                         null,
                                         'sortorder');

        $rolemenu = role_fix_names($roles, null, ROLENAME_ORIGINAL, true);
    }
    return $rolemenu;
}

/**
 *
 */
function get_course_request_category_tree($parentid) {
    global $DB;

    $sql =<<<SQL
select cc.id, cc.name, cc.sortorder
from {course_categories} cc
  join {course_request_category_map} cm on cm.categoryid = cc.id
where cm.display = 1 and cc.parent = :parentid
order by cc.sortorder
SQL;

    $subcats = $DB->get_records_sql($sql, array('parentid'=>$parentid));

    if (empty($subcats)) return null;

    $tree = array();
    foreach ($subcats as $subcatid => $category) {
        $category->children = get_course_request_category_tree($subcatid);
        // We do not use the id as the key because we want to maintain the
        // same order after this gets sent to the Javascript module.
        $tree[] = (array) $category;
    }

    return $tree;
}


/**
 * This is an event handler configured in local/course/db/events.php
 * to be called when a course is deleted. We are simply logging
 * the event to the error log. The primary motivation for this
 * is to be able to better monitor local/course/bulk_delete.php.
 */
function local_course_course_deleted($course) {
    error_log("Deleted course $course->id: $course->fullname");
    return true;
}

/**
 * This is an event handler configured in local/course/db/events.php
 * to be called when a Moodle instance is deleted from mdl_moodle_instances.
 * Here, we delete any rows in mdl_course_request_servers that depend on the
 * deleted instance.
 */
function local_course_moodleinstance_deleted(\local_instances\event\instance_deleted $event) {
    global $DB;

    $deletedinstanceid = $event->objectid;

    $DB->delete_records_select('course_request_servers',
                               'requestinginstanceid = :id1 or sourceinstanceid = :id2',
                               array('id1' => $deletedinstanceid,
                                     'id2' => $deletedinstanceid));
    return true;
}

function get_migration_responder() {
    return new migration_responder(get_course_xfer_server(),
                                   get_course_backup_wrapper());
}

function get_course_restore_wrapper() {
    return new course_restore_wrapper(new restore_controller_factory());
}

function get_course_backup_wrapper() {
    return new course_backup_wrapper(new backup_controller_factory());
}

function get_course_xfer_server() {
    global $CFG;

    $base_dir = $CFG->migration_base_dir;
    $clients = local_course_get_migration_client_names();
    $server_dirname = local_course_this_instancename();

    return new course_xfer_server($base_dir, $server_dirname, $clients);
}


function get_course_xfer_client() {
    global $CFG;

    $base_dir = $CFG->migration_base_dir;
    $server_map = local_course_get_migration_server_map();

    $client_dirname = local_course_this_instancename();

    return new course_xfer_client($base_dir, $client_dirname, $server_map);
}

class base_backup_restore_controller_factory {


    private $userdata_settings = array(
                               'users'            => true,
                               'role_assignments' => true,
                               'activities'       => true,
                               'blocks'           => true,
                               'filters'          => true,
                               'comments'         => true,
                               'userscompletion'  => true,
                               'logs'             => false,
                               'grade_histories'  => true);

    private $nonuserdata_settings = array(
                                  'users'            => false,
                                  'activities'       => true,
                                  'blocks'           => true,
                                  'filters'          => true);

    # TODO: Confirm that assigning the return value creates new copy of array and
    #       not just a reference that might cause the underlying array to change.
    protected function get_settings($include_user_data) {
        $settings = $include_user_data ? $this->userdata_settings
                                       : $this->nonuserdata_settings;

        return $settings;
    }

    /**
     * In backup/util/checks/backup[restore]_check.class.php, check_security
     * forces userdata to false.
     * That happens when the controller constructor runs, so we can fix that setting
     * and any others here.
     */
    protected function initialize_controller_settings($controller, $include_user_data) {

        $settings = $this->get_settings($include_user_data);

        foreach ($settings as $name => $value) {
            $setting = $controller->get_plan()->get_setting($name);
            $setting->set_status(base_setting::NOT_LOCKED);
            $setting->set_value($value);
            $setting->set_status(base_setting::LOCKED_BY_CONFIG);
        }
    }
}


/**
 * Factory class required to support mocking of backup_controller.
 */
class backup_controller_factory extends base_backup_restore_controller_factory {

    // Backup has one more setting, 'anonymize', than restore.
    private $more_userdata_settings = array('anonymize' => false);

    protected function get_settings($include_user_data) {
        $settings = parent::get_settings($include_user_data);
        if ($include_user_data) {
            $settings = array_merge($settings, $this->more_userdata_settings);
        }
        return $settings;
    }

    /**
     * Use backup::MODE_IMPORT if intending to immediately import.
     */
    public function create_backup_controller($courseid,
                                             $include_user_data=false,
                                             $backupmode = backup::MODE_GENERAL)
    {
        global $USER;

        $backup_controller = new backup_controller(backup::TYPE_1COURSE,
                                                   $courseid,
                                                   backup::FORMAT_MOODLE,
                                                   backup::INTERACTIVE_NO,
                                                   $backupmode,
                                                   $USER->id);

        $this->initialize_controller_settings($backup_controller, $include_user_data);

        return $backup_controller;

        // See function store_backup_file in backup/util/helper/backup_helper.class.php
        // for details on where Moodle puts the backup files.  We must move from
        // there to a filesystem location if we need to pass to another server.
    }
}

// This allows for unit testing with Mock. Also, simplifies
// logic slightly.
class restore_controller_factory extends base_backup_restore_controller_factory {

    public function create_restore_controller($courseid,
                                              $tempdirname,
                                              $include_user_data=false) {
        global $USER;

        // Can't use backup::MODE_IMPORT unless we later force the userdata
        // setting to true, as necessary.
        $restore_controller = new restore_controller($tempdirname,
                                                     $courseid,
                                                     backup::INTERACTIVE_NO,
                                                     backup::MODE_GENERAL,
                                                     $USER->id,
                                                     backup::TARGET_NEW_COURSE);

        $this->initialize_controller_settings($restore_controller, $include_user_data);

        return $restore_controller;
    }
}

/**
 *
 */
function get_course_request_manager() {

    $user_creator = new local_user_creator();
    $course_creator = get_course_creator();
    $course_xfer_client = get_course_xfer_client();

    $course_request_manager =
        new course_request_manager($user_creator, $course_creator, $course_xfer_client);

    return $course_request_manager;
}

/**
 *
 */
function get_course_creator() {
    $course_creator = new course_creator();
    $course_creator->set_course_backup_wrapper(get_course_backup_wrapper());
    $course_creator->set_course_restore_wrapper(get_course_restore_wrapper());
    return $course_creator;
}

    /**
     * Deletes the course request. FROM OLD CLASS. SAVING TEMPORARILY AS REMINDER TO CONSIDER.
     * MIGHT NOT WANT TO USE THIS ANYMORE.
     */
#    public function delete() {
#        global $DB;
#
#        $DB->delete_records('course_request_users'  , array('courserequestid' => $this->properties->id));
#        $DB->delete_records('course_request_classes', array('courserequestid' => $this->properties->id));
#        $DB->delete_records('course_request_u'      , array('id'              => $this->properties->id));
#    }




