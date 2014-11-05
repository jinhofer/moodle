<?php

# TODO: Might need something like an enrol_umnauto_course table that would
#       hold course-level settings (that is, auto_add, auto_drop, auto_withdraw).
#       Or, change the umnauto to apply at the course level rather than class
#       level.

require_once('locallib.php');
require_once("$CFG->dirroot/local/ppsft/lib.php");

class enrol_umnauto_plugin extends enrol_plugin {

    # TODO: Determine whether to return true or false for each of these.
    public function allow_enrol(stdClass $instance) { return true; }
    public function allow_unenrol(stdClass $instance) { return true; }
    public function allow_manage(stdClass $instance) { return true; }

    /**
     * Returns icons for the page with list of instances
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        if ($instance->enrol !== 'umnauto') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);

        $icons = array();

        if (has_capability('enrol/umnauto:manage', $context)) {
            $managelink = new moodle_url("/enrol/umnauto/manage.php", array('enrolid'=>$instance->id));
            //STRY0010218 20140319 mart0969 - Change icon to user enrolment icon in 2.6
            $icons[] = $OUTPUT->action_icon($managelink,
                                            new pix_icon('t/enrolusers',
                                                         get_string('enrolusers', 'enrol_umnauto'),
                                            'core',
                                            array('class'=>'iconsmall')));
        }

        #if (has_capability('enrol/manual:config', $context)) {
        #    $editlink = new moodle_url("/enrol/manual/edit.php", array('courseid'=>$instance->courseid));
        #    $icons[] = $OUTPUT->action_icon($editlink, new pix_icon('i/edit', get_string('edit'), 'core', array('class'=>'icon')));
        #}

        return $icons;
    }

    /**
     *
     */
    public function get_newinstance_link($courseid) {
        global $DB;

        $context = context_course::instance($courseid, MUST_EXIST);

        # TODO: ADD CAPABILITY enrol/umnauto:config.
        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/umnauto:config', $context)) {
            return NULL;
        }

        if ($DB->record_exists('enrol', array('courseid'=>$courseid, 'enrol'=>'umnauto'))) {
            return NULL;
        }

        return new moodle_url('/enrol/umnauto/addinstance.php',
                              array('sesskey' => sesskey(),
                                    'id' => $courseid));
    }

    /**
     * Core cron_run function in lib/cronlib.php invokes the method named
     * "cron" in all enrollment plugins (except that it skips any enrollment
     * plugins for which is_cron_required returns false).
     */
    public function cron() {
        # TODO: Can we find a way to use the lastcron config to check that
        #       slow_cron is actually getting executed periodically.
        #       See cronrun() in lib/cronlib.php.
        echo "INFO: Use other cron to execute enrol/umnauto updates through slow_cron()\n";
    }

    /**
     * The main moodle cron should not execute this because it does not
     * exactly match the convention. (See above cron() function.)  Instead,
     * we should use a separate cron due to the time required for this to
     * run.  See other cli directories under enrol for examples of
     * additional cron scripts.
     */
    public function slow_cron() {
        enrol_umnauto_sync();
    }

    /**
     * Overrides empty implementation in base (enrol_plugin) so that
     * we automatically get an instance for each new course if
     * defaultenrol is checked.
     */
    public function add_default_instance($course) {
        return $this->add_instance($course);
    }

    /**
     * Creates a new instance of this plugin for a course.
     * Overrides and uses implementation in base (enrol_plugin).
     */
    public function add_instance($course, array $fields = NULL) {
        global $DB;

        if (!isset($fields)) {
            $fields = array('roleid' => $this->get_config('roleid', 0));
        } else if (!array_key_exists('roleid', $fields)) {
            $fields['roleid'] = $this->get_config('roleid', 0);
        }

        if (!$DB->record_exists('enrol_umnauto_course', array('courseid' => $course->id))) {
            // Currently, relying on database defaults. Might need to make defaults configurable.
            $DB->insert_record('enrol_umnauto_course', array('courseid' => $course->id));
        }

        return parent::add_instance($course, $fields);
    }

    /**
     * Deletes an instance of this plugin.
     * Overrides and uses implementation in base (enrol_plugin).
     * Overridden to perform umnauto-specific clean-up.
     */
    public function delete_instance($instance) {
        global $DB;

        $DB->delete_records('enrol_umnauto_classes', array('enrolid' => $instance->id));

        return parent::delete_instance($instance);
    }

    /**
     * Adds navigation link in course settings block taking user to manage page.
     * Overrides empty implementation in base (enrol_plugin).
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
        if ($instance->enrol !== 'umnauto') {
             throw new coding_exception('Invalid enrol instance type!');
        }

        $context = context_course::instance($instance->courseid);
        if (has_capability('enrol/umnauto:config', $context)) {
            $managelink = new moodle_url("/enrol/umnauto/manage.php", array('enrolid'=>$instance->id));
            $instancesnode->add($this->get_instance_name($instance), $managelink, navigation_node::TYPE_SETTING);
        }
    }

    /**
     * Adds enrol instance UI to course edit form
     *
     * @param object $instance enrol instance or null if does not exist yet
     * @param MoodleQuickForm $mform
     * @param object $data
     * @param object $context context of existing course or parent category if course does not exist
     * @return void
     */
    public function course_edit_form($instance, MoodleQuickForm $mform, $data, $context) {
        global $DB;

        // When editing an existing course, this will not appear unless an instance
        // of umnauto exists for the course (that is, a PeopleSoft course is associated).
        // This is a consequence of the logic in enrol_course_edit_form in lib/enrollib.php.
        // For consistency, then, we will also not display for a new course (that is,
        // no $data->id is set).

        if (empty($data->id)) {
            return;
        }

        // We want to display only one time for each course edit page.  Will need to
        // revisit if we would ever need to display multiple for a single HTTP request.
        static $displayed = false;

        if (!$displayed) {
            $mform->addElement('header', 'enrol_umnauto_header', get_string('coursesettingsheader', 'enrol_umnauto'));

            $unenrol_options = array('E'   => get_string('autoenrolonly', 'enrol_umnauto'),
                                     'ED'  => get_string('autoenrolanddrop', 'enrol_umnauto'),
                                     'EDH' => get_string('autoenroldropandwithdraw', 'enrol_umnauto'));

            $mform->addElement('select',
                               'enrol_umnauto_unenrol_options',
                               get_string('unenroloptions', 'enrol_umnauto'),
                               $unenrol_options);
#            $mform->addHelpButton('enrol_umnauto_unenrol_options', 'unenroloptions', 'enrol_umnauto');
            if (!has_capability('enrol/umnauto:config', $context)) {
                $mform->hardFreeze('enrol_umnauto_unenrol_options');
            }

            // Because we already checked for $data->id, we know that it exists.
            // It should be the Moodle course id.
            $umnauto_course = $DB->get_record('enrol_umnauto_course',
                                              array('courseid' => $data->id));

            if ($umnauto_course) {
                $data->enrol_umnauto_unenrol_options = 'E';

                if ($umnauto_course->auto_drop) {
                    $data->enrol_umnauto_unenrol_options .= 'D';
                    if ($umnauto_course->auto_withdraw) {
                        $data->enrol_umnauto_unenrol_options .= 'H';
                    }
                }

            } else {
                $data->enrol_umnauto_unenrol_options = 'ED';
            }

            $displayed = true;
        }
    }

    /**
     * Called after updating/inserting course.
     *
     * @param bool $inserted true if course just inserted
     * @param object $course
     * @param object $data form data
     * @return void
     */
    public function course_updated($inserted, $course, $data) {
        global $DB;

        parent::course_updated($inserted, $course, $data);

        if (!empty($data->enrol_umnauto_unenrol_options)) {

            $auto_withdraw = 0;
            $auto_drop = 0;
            switch ($data->enrol_umnauto_unenrol_options) {
                case 'EDH': // Intentionally, no break.  Falling through.
                    $auto_withdraw = 1;
                case 'ED':
                    $auto_drop = 1;
            }

            $umnauto_course = $DB->get_record('enrol_umnauto_course',
                                              array('courseid' => $course->id));

            if (empty($umnauto_course)) {
                $umnauto_course = array('courseid'      => $course->id,
                                        'auto_drop'     => $auto_drop,
                                        'auto_withdraw' => $auto_withdraw);

                $DB->insert_record('enrol_umnauto_course', $umnauto_course);

            } else if ($umnauto_course->auto_drop     != $auto_drop ||
                       $umnauto_course->auto_withdraw != $auto_withdraw)
            {

                $umnauto_course->auto_drop     = $auto_drop;
                $umnauto_course->auto_withdraw = $auto_withdraw;
                $DB->update_record('enrol_umnauto_course', $umnauto_course);
            }
        }
    }

    /**
     * Associates PeopleSoft classes with an autoenrollment plugin
     * instance.  Has the effect of enabling the automatic enrollment
     * of students in the PeopleSoft class into the Moodle course
     * site that the enrollment plugin instance belongs to.
     */
    public function add_ppsft_class($instanceid, $ppsftclassid) {
        global $DB;

        $this->check_add_remove_permission($instanceid, $ppsftclassid);

        // Unique index should be in place to prevent dupes.
        $instance_class = $DB->insert_record('enrol_umnauto_classes',
                                             array('enrolid'      => $instanceid,
                                                   'ppsftclassid' => $ppsftclassid));

        return $instance_class;
    }

    /**
     * Returns true if the association is successfully removed.
     */
    public function remove_ppsft_class($instanceid, $ppsftclassid) {
        global $DB;

        $this->check_add_remove_permission($instanceid, $ppsftclassid);

        return $DB->delete_records('enrol_umnauto_classes',
                                   array('enrolid'      => $instanceid,
                                         'ppsftclassid' => $ppsftclassid));

    }

    /**
     * Throws exception if not the instructor for the PeopleSoft class and
     * does not have umnauto:enrolanystudent permission.
     */
    private function check_add_remove_permission($instanceid, $ppsftclassid) {
        if (! $this->has_add_remove_permission($instanceid, $ppsftclassid)) {
            throw new Exception("Not an instructor for ppsftclassid $ppsftclassid");
        }
    }

    /**
     *
     */
    public function has_add_remove_permission($instanceid, $ppsftclassid) {
        global $DB;
        global $USER;

        if (! $DB->record_exists('ppsft_class_instr',
                                 array('userid'      =>$USER->id,
                                       'ppsftclassid'=>$ppsftclassid)))
        {
            $courseid = $DB->get_field('enrol', 'courseid', array('id'=>$instanceid));
            $context = context_course::instance($courseid, MUST_EXIST);

            if (! has_capability('enrol/umnauto:enrolanystudent', $context)) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     */
    public function get_ppsft_classes($instanceid) {
        global $DB;

        $ppsftclassids = $DB->get_fieldset_select('enrol_umnauto_classes',
                                                  'ppsftclassid',
                                                  'enrolid = ?',
                                                  array($instanceid));
        return $ppsftclassids;
    }


    /**
     * Returns a map of term codes to term names. Includes
     * only enabled term if $enabled_only is set true.
     */
    public function get_term_map($enabled_only=false) {
        global $DB;

        if ($enabled_only) {
            $enabled_terms = $this->get_config('enabled_terms', '');

            if ($enabled_terms) {

                $termobjs = $DB->get_records_select('ppsft_terms',
                                                    "term in ($enabled_terms)",
                                                    null,
                                                    'term',
                                                    'term, term_name');
            } else {
                $termobjs = false;
            }
        } else {
            $termobjs = $DB->get_records('ppsft_terms',
                                         null,
                                         'term',
                                         'term, term_name');
        }

        if ($termobjs) {
            return array_map(function ($term) {
                                 return $term->term_name; },
                             $termobjs);
        }

        return array();
    }

    /**
     * Returns an array of the enabled term codes.
     */
    public function get_enabled_terms() {
        $enabled_terms = $this->get_config('enabled_terms', '');
        return explode(',', $enabled_terms);
    }

    /**
     * update the auto_group value for a course in
     * table enrol_umnauto_course
     *
     * @param int $course_id
     * @param array $new_values:
     *     'auto_update'        => 1|0
     *     'name_include_term'  => 1|0
     * @return void
     * @throws dml_missing_record_exception
     */
    public function set_autogroup_settings($course_id, $new_values) {
        global $DB;

        $course_record = $DB->get_record('enrol_umnauto_course', array('courseid' => $course_id));

        if (!$course_record) {
            throw new dml_missing_record_exception('enrol_umnauto_course');
        }

        if (isset($new_values['auto_update'])) {
            $course_record->auto_group = $new_values['auto_update'] == '1' ? '1' : '0';
        }

        if (isset($new_values['name_include_term'])) {
            $course_record->autogroup_option = $new_values['name_include_term'] == '1' ? '1' : '0';
        }

        $DB->update_record('enrol_umnauto_course', $course_record);
    }

    /**
     * Gets an array of the user enrolment actions. Overridden from base class.
     *
     * @param course_enrolment_manager $manager
     * @param stdClass $ue A user enrolment object
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {
        global $DB;

        $actions = array();

        $sql =<<<SQL
select 1
from mdl_ppsft_class_enrol ce
 join mdl_enrol_umnauto_classes uc on uc.ppsftclassid=ce.ppsftclassid
where ce.status='E' and ce.userid=:userid and uc.enrolid=:enrolid
SQL;

        // Only display the enrollment deletion link if user does not have a
        // corresponding ppsft enrollment record.
        if ($DB->record_exists_sql($sql, array('userid' => $ue->userid, 'enrolid' => $ue->enrolid))) {
            return $actions;
        }

        $context = $manager->get_context();
        $instance = $ue->enrolmentinstance;
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;
        if ($this->allow_unenrol_user($instance, $ue) && has_capability("enrol/umnauto:unenrol", $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/delete', ''),
                                                   get_string('unenrol', 'enrol'),
                                                   $url,
                                                   array('class'=>'unenrollink', 'rel'=>$ue->id));
        }
        return $actions;
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/umnauto:config', $context);
    }

}

/**
 * Indicates API features that the enrol plugin supports.  Probably
 * called from lib/moodlelib.php function plugin_supports.
 *
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function enrol_umnauto_supports($feature) {

    # TODO: Currently, in backup/moodle2/restore_stepslib.php, ENROL_RESTORE_TYPE
    #       values other than ENROL_RESTORE_EXACT and ENROL_RESTORE_NOUSERS are
    #       not supported.  Eventually, restore is supposed to support ENROL_RESTORE_CLASS
    #       to allow for custom restore capability.  EXACT handles only the enrol
    #       table and user_enrolments records.

    switch($feature) {
        case ENROL_RESTORE_TYPE: return ENROL_RESTORE_EXACT;

        default: return null;
    }
}

/**
 * get the associated PeopleSoft classes for a Moodle course
 * @param int $course_id
 * @return array of ppsft_classes records
 */
function enrol_umnauto_get_course_ppsft_classes($course_id) {
    global $DB;

    $query = 'SELECT ppsft_classes.*
              FROM {enrol} enrol
                   INNER JOIN {enrol_umnauto_classes} enrol_umnauto_classes
                          ON enrol_umnauto_classes.enrolid = enrol.id
                   INNER JOIN {ppsft_classes} ppsft_classes
                          ON enrol_umnauto_classes.ppsftclassid = ppsft_classes.id
              WHERE enrol.courseid = :course_id';

    return $DB->get_records_sql($query, array('course_id' => $course_id));
}

