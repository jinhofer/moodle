<?php

class local_moodlekiosk {

    /**
     * get the data and print them as CSV
     */
    public function get_data($table) {
        global $CFG, $DB;

        $fields = array(
            'user'              => array('username', 'firstname', 'lastname'),
            'course_categories' => array('id', 'name', 'parent'),
            'ppsft_terms'       => array('term', 'term_name'),
            'ppsft_classes'     => array('term', 'institution', 'class_nbr', 'subject', 'catalog_nbr', 'section', 'descr')
        );

        ob_end_flush();
        $out = fopen('php://output', 'w');

        $rs = $DB->get_recordset($table, null, '', implode(',', $fields[$table]));

        foreach ($rs as $r) {
            switch ($table) {
                case 'user':
                    fputcsv($out, array(
                        $r->username,
                        $r->firstname,
                        $r->lastname));
                    break;

                case 'course_categories':
                    fputcsv($out, array($r->id, $r->name, $r->parent));
                    break;

                case 'ppsft_terms':
                    fputcsv($out, array($r->term, $r->term_name));
                    break;

                case 'ppsft_classes':
                    fputcsv($out, array(
                        $r->term,
                        $r->institution,
                        $r->class_nbr,
                        $r->subject,
                        $r->catalog_nbr,
                        $r->section,
                        $r->descr));
                    break;

                default:
                    break 2;
            }
        }

        // close DB resource;
        $rs->close();
        fclose($out);
    }

    /**
     * get course-level data and print as CSV
     */
    public function get_course_data() {
        global $CFG, $DB;

        ob_end_flush();
        $out = fopen('php://output', 'w');

        $sql =<<<SQL
select c.id, c.category, c.fullname, c.shortname, c.visible, mu.skip_portal
from {course} c
left join {myu_course} mu on mu.courseid=c.id
SQL;

        $rs = $DB->get_recordset_sql($sql);

        foreach ($rs as $r) {
            fputcsv($out, array(
                $r->id,
                $r->category,
                $r->fullname,
                $r->shortname,
                $r->visible,
                $CFG->wwwroot.'/course/view.php?id='.$r->id,
                $r->skip_portal));
        }

        // close DB resource;
        $rs->close();
        fclose($out);
    }

    /**
     * get the role assignment at course level,
     * print them as CSV
     */
    public function get_course_enrol() {
        global $CFG, $DB;

        ob_end_flush();
        $out = fopen('php://output', 'w');

        $sql = 'SELECT user.username AS username,
                       role.shortname AS role,
                       context.instanceid AS course,
                       user_lastaccess.timeaccess AS last_access
                FROM {role_assignments} role_assignments
                     INNER JOIN {role} role ON role.id = role_assignments.roleid
                     INNER JOIN {context} context ON context.id = role_assignments.contextid AND
                                                     context.contextlevel = :context_course
                     INNER JOIN {user} user ON user.id = role_assignments.userid
                     LEFT JOIN {user_lastaccess} user_lastaccess ON
                           user_lastaccess.userid = user.id AND
                           user_lastaccess.courseid = context.instanceid';

        $rs = $DB->get_recordset_sql($sql, array('context_course' => CONTEXT_COURSE));

        foreach ($rs as $r) {
            fputcsv($out, array(
                $r->username,
                $r->course,
                $r->role,
                $r->last_access));
        }

        // close DB resource;
        $rs->close();
        fclose($out);
    }


    /**
     * get the role assignment at site and category level,
     * print them as CSV
     */
    public function get_support() {
        global $CFG, $DB;

        ob_end_flush();
        $out = fopen('php://output', 'w');

        $sql = 'SELECT user.username AS username,
                       role.shortname AS role,
                       context.instanceid AS instanceid,
                       context.contextlevel AS level
                FROM {role_assignments} role_assignments
                     INNER JOIN {role} role ON role.id = role_assignments.roleid
                     INNER JOIN {context} context ON context.id = role_assignments.contextid AND
                           (context.contextlevel = :context_coursecat || context.contextlevel = :context_system)
                     INNER JOIN {user} user ON user.id = role_assignments.userid';

        $rs = $DB->get_recordset_sql($sql, array('context_coursecat' => CONTEXT_COURSECAT,
                                                 'context_system'    => CONTEXT_SYSTEM));

        foreach ($rs as $r) {
            fputcsv($out, array(
                $r->username,
                $r->instanceid,
                $r->level,
                $r->role));
        }

        // also return the site-admins
        $admin_ids = explode(',', $CFG->siteadmins);
        $sql = 'SELECT user.username AS username
                FROM {user} user
                WHERE id IN ('.implode(',', array_fill(0, count($admin_ids), '?')).')';

        $rs = $DB->get_recordset_sql($sql, $admin_ids);

        foreach ($rs as $r) {
            fputcsv($out, array(
                $r->username,
                0,
                CONTEXT_SYSTEM,
                'siteadmin'));
        }

        // close DB resource;
        $rs->close();
        fclose($out);
    }



    /**
     * get the ppsft_class_enrol and ppsf_class_instr,
     * print them as CSV
     */
    public function get_ppsft_class_enrol() {
        global $CFG, $DB;

        ob_end_flush();
        $out = fopen('php://output', 'w');

        // get the ppsft_class_enrol
        $sql = 'SELECT ppsft_classes.term AS term,
                       ppsft_classes.institution AS institution,
                       ppsft_classes.class_nbr AS class_nbr,
                       user.username AS username
                FROM {ppsft_class_enrol} ppsft_class_enrol
                     INNER JOIN {ppsft_classes} ppsft_classes ON ppsft_classes.id = ppsft_class_enrol.ppsftclassid
                     INNER JOIN {user} user ON user.id = ppsft_class_enrol.userid
                WHERE ppsft_class_enrol.status = :status_enrol';

        $rs = $DB->get_recordset_sql($sql, array('status_enrol' => 'E'));

        foreach ($rs as $r) {
            fputcsv($out, array(
                $r->term,
                $r->institution,
                $r->class_nbr,
                $r->username,
                'student'));
        }

        // get the ppsft_class_instr
        $sql = 'SELECT ppsft_classes.term AS term,
                       ppsft_classes.institution AS institution,
                       ppsft_classes.class_nbr AS class_nbr,
                       user.username AS username
                FROM {ppsft_class_instr} ppsft_class_instr
                     INNER JOIN {ppsft_classes} ppsft_classes ON ppsft_classes.id = ppsft_class_instr.ppsftclassid
                     INNER JOIN {user} user ON user.id = ppsft_class_instr.userid';

        $rs = $DB->get_recordset_sql($sql);

        foreach ($rs as $r) {
            fputcsv($out, array(
                $r->term,
                $r->institution,
                $r->class_nbr,
                $r->username,
                'instr'));
        }

        // close DB resource;
        $rs->close();
        fclose($out);
    }


    /**
     * get the relationship between ppsft_classes and course,
     * print them as CSV
     */
    public function get_class_course() {
        global $CFG, $DB;

        ob_end_flush();
        $out = fopen('php://output', 'w');

        $sql = 'SELECT ppsft_classes.term AS term,
                       ppsft_classes.institution AS institution,
                       ppsft_classes.class_nbr AS class_nbr,
                       enrol.courseid AS courseid
                FROM {enrol_umnauto_classes} enrol_umnauto_classes
                     INNER JOIN {ppsft_classes} ppsft_classes ON ppsft_classes.id = enrol_umnauto_classes.ppsftclassid
                     INNER JOIN {enrol} enrol ON enrol.id = enrol_umnauto_classes.enrolid';

        $rs = $DB->get_recordset_sql($sql);

        foreach ($rs as $r) {
            fputcsv($out, array(
                $r->term,
                $r->institution,
                $r->class_nbr,
                $r->courseid));
        }

        // close DB resource;
        $rs->close();
        fclose($out);
    }


    /**
     * helper function to post data to remote URL
     * @param string $url
     * @param array $data
     * @param string $header, optional
     */
    public static function post_data($url, $data, $header = null) {
        // Create URL parameter string
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        try {
            $result = curl_exec($ch);
            if ($result === false) {
                throw new Exception('Curl error in post_data: '.curl_error($ch));
            }

            $returncode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (!in_array($returncode, [200, 201, 202])) {
                throw new Exception("Failed sending updates to $url: $result");
            }
        } finally {
            curl_close($ch);
        }
    }
}


/**
 * handle role_assigned event
 * @param stdClass $event
 */
function moodlekiosk_role_assigned($event) {
    global $CFG, $DB;

    // store to cache table
    $r = new stdClass();
    $r->event          = 'role_assigned';
    $r->roleid         = $event->roleid;
    $r->contextid      = $event->contextid;
    $r->userid         = $event->userid;
    $r->timemodified   = $event->timemodified;

    $DB->insert_record('moodlekiosk_event_cache', $r, false);

    return true;
}


/**
 * handle role_unassigned event
 * @param stdClass $event
 */
function moodlekiosk_role_unassigned($event) {
    global $CFG, $DB;

    // store to cache table
    $r = new stdClass();
    $r->event          = 'role_unassigned';
    $r->roleid         = $event->roleid;
    $r->contextid      = $event->contextid;
    $r->userid         = $event->userid;
    $r->timemodified   = $event->timemodified;

    $DB->insert_record('moodlekiosk_event_cache', $r, false);

    return true;
}
