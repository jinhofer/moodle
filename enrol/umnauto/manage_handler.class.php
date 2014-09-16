<?php

// This holds state for a specific request; create anew for each.

# TODO: Dependency on ppsft updater crept in here. Need to refactor
#       to use dependency injection, but also don't want to unnecessarily
#       create database connections.  Might be time to refactor
#       ppsft adapter to open connections lazily.

class enrol_umnauto_manage_handler {

    public $errors = array();

    private $instance;

    private $enrollment_syncer;

    /**
     *
     */
    public function __construct($instance) {
        $this->instance = $instance;
    }

    /**
     *
     */
    public function no_valid_action($data) {
        $this->errors['action'] = 'No valid action requested';
    }


    /**
     *
     */
    public function update_course_enrollment() {
        // First, update local PeopleSoft enrollment data.
        // Then, update corresponding Moodle enrollments.
        // Updating ppsft class enrollments would be over-kill except that it gives
        // users a way to force a full update for the class. 

        # TODO: Make configurable?
        $update_ppsft_data = true;
        if ($update_ppsft_data) {
            $enrol_plugin = enrol_get_plugin('umnauto');
            $ppsftclassids = $enrol_plugin->get_ppsft_classes($this->instance->id);

            if (empty($ppsftclassids)) {
                $this->errors['action'] = get_string('noclassesassociated', 'enrol_umnauto');
                return;
            }

            $ppsft_updater = ppsft_get_updater();
            foreach ($ppsftclassids as $ppsftclassid) {
                $ppsft_updater->update_class_enrollment($ppsftclassid);
            }
        }

        if (! $this->enrollment_syncer) {
            throw new Exception('enrol_umnauto_manage_handler requires enrollment_syncer in update_course_enrollment');
        }

        $this->enrollment_syncer->sync();
    }

    /**
     *
     */
    public function set_enrollment_syncer($enrollment_syncer) {
        $this->enrollment_syncer = $enrollment_syncer;
    }

    /**
     *
     */
    public function refresh_instructor_classes() {
        global $USER;
        $ppsft_updater = ppsft_get_updater();
        $ppsft_updater->update_instructor_classes($USER);
    }

    /**
     *
     */
    private function add_ppsft_class($ppsftclassid) {

        $enrol_plugin = enrol_get_plugin('umnauto');

        if (! $enrol_plugin->has_add_remove_permission($this->instance->id,
                                                       $ppsftclassid))
        {
            $this->refresh_instructor_classes();
        }

        $enrol_plugin->add_ppsft_class($this->instance->id,
                                       $ppsftclassid);

        // 20110915 Colin. Removed call to ppsft_updater->update_class_enrollment
        //          because ppsft_updater->add_class_by_triplet now does that.
    }

    /**
     *
     */
    public function remove_ppsft_class($ppsftclassid) {

        $enrol_plugin = enrol_get_plugin('umnauto');

        if (! $enrol_plugin->has_add_remove_permission($this->instance->id,
                                                             $ppsftclassid))
        {
            $this->refresh_instructor_classes();
        }

        $enrol_plugin->remove_ppsft_class($this->instance->id,
                                          $ppsftclassid);
    }

    /**
     *
     */
    public function add_ppsft_class_by_triplet_data($data) {

        // Allowing exceptions for these since the UI should
        // constrain entries.

        $institution = validate_param($data->institution,
                                      PARAM_ALPHANUM,
                                      NULL_NOT_ALLOWED,
                                      'invalid institution');

        $term = validate_param($data->term,
                               PARAM_INT,
                               NULL_NOT_ALLOWED,
                               'invalid term');

        if (empty($data->classnbr) or
            (string) $data->classnbr !== (string) clean_param($data->classnbr, PARAM_INT))
        {
            $this->errors['classnbr'] = get_string('invalidclassnbr', 'enrol_umnauto');
            return;
        }

        $this->add_ppsft_class_by_triplet($term, $institution, $data->classnbr);
    }

    /**
     * This method does not check input parameters. Assumes that caller
     * has done that.
     */
    public function add_ppsft_class_by_triplet($term, $institution, $classnbr) {

        try {
            $ppsftclassid = $this->find_ppsft_class($term,
                                                    $institution,
                                                    $classnbr);
        } catch (ppsft_no_such_class_exception $ex) {
            $this->errors['classnbr'] =
               "$classnbr is not a valid PeopleSoft class number for term $term at $institution";
            return;
        }

        try {
            $this->add_ppsft_class($ppsftclassid);
        } catch (dml_write_exception $ex) {
            if (0 === strpos($ex->error, 'Duplicate entry ')) {
                $this->errors['classnbr'] = "$term $institution $classnbr is already linked.";
            } else {
                throw $ex;
            }
        }
    }

    /**
     *
     */
    # TODO: Change to using the method of the same name in local/ppsft/ppsft_data_updater.class.php.
    private function find_ppsft_class($term, $institution, $classnbr) {
        global $DB;

        $ppsftclassid = $DB->get_field('ppsft_classes',
                                       'id',
                                       array('term'        => $term,
                                             'institution' => $institution,
                                             'class_nbr'   => $classnbr));

        if (! $ppsftclassid) {
            $ppsft_updater = ppsft_get_updater();

            $ppsftclassid = $ppsft_updater->add_class_by_triplet($term,
                                                                 $institution,
                                                                 $classnbr);
        }

        return $ppsftclassid;
    }

}

