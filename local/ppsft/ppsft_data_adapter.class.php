<?php

/**
 * This implementation of the ppsft_data_adapter connects to a
 * PeopleSoft schema.
 *
 * Public methods:
 *
 *      get_classes_by_triplets_or_catalog($searchparams)
 *      get_enrollment_updates_since($start_time)
 *      get_class_by_triplet($term, $institution, $class_nbr)
 *      get_instructor_classes($emplid, $min_term)
 *      get_student_classes($emplid, $min_term)
 *      get_class_instructors($term, $institution, $class_nbr)
 *      get_class_students($term, $institution, $class_nbr)
 *      get_terms_since($start_term)
 *      get_current_term()
 */


class ppsft_data_adapter {

    private $db;

    public function __construct($database_connection) {
        $this->db = $database_connection;
    }

    /**
     *
     */
    public function get_classes_by_instructor_and_term($emplid, $term) {

        $sql =<<<SQL
select distinct c.strm || c.institution || c.class_nbr as triplet,
        c.strm as term,
        c.institution,
        c.subject,
        c.catalog_nbr,
        c.class_section,
        c.ssr_component,
        cc.course_title_long as long_title,
        c.class_nbr
from cssysadm.o_ps_class_tbl c,
     dwsysadm.ps_crse_catalog cc,
     cssysadm.o_ps_class_instr ci
where
    cc.crse_id = c.crse_id
    and cc.eff_status = 'A'
    and cc.effdt = coalesce(
                    (select max(cc2.effdt)
                     from dwsysadm.ps_crse_catalog cc2
                     where cc2.eff_status = 'A'
                                and cc2.effdt <= c.start_dt
                                and cc2.crse_id = cc.crse_id),
                    (select min(cc3.effdt)
                     from dwsysadm.ps_crse_catalog cc3
                     where cc3.eff_status = 'A'
                                and cc3.crse_id = cc.crse_id))
    and c.crse_id = ci.crse_id
    and c.crse_offer_nbr = ci.crse_offer_nbr
    and c.strm = ci.strm
    and c.session_code = ci.session_code
    and c.class_section = ci.class_section
    and ci.emplid = :emplid and ci.strm = :term
    and ci.emplid <> ' '
order by c.strm, c.institution, c.subject, c.catalog_nbr, c.class_section
SQL;
#print $sql;
        $classes = $this->db->get_records_sql($sql,
                                              array('emplid' => $emplid,
                                                    'term'   => $term));
        return $classes;
    }


    /**
     *
     */
    public function get_classes_by_triplets_or_catalog($searchparams) {

        $matchcondition = $this->convert_search_params_to_sql($searchparams);

        if (! trim($matchcondition)) {
            return array();
        }

        # TODO: Settle on either "section" or "class_section".

        $sql =<<<SQL
select distinct c.strm || c.institution || c.class_nbr as triplet,
        c.strm as term,
        c.institution,
        c.subject,
        c.catalog_nbr,
        c.class_section,
        c.class_section as section,
        c.ssr_component,
        cc.course_title_long as long_title,
        c.class_nbr
from cssysadm.o_ps_class_tbl c, dwsysadm.ps_crse_catalog cc
where
    cc.crse_id = c.crse_id
    and cc.eff_status = 'A'
    and cc.effdt = coalesce(
                    (select max(cc2.effdt)
                     from dwsysadm.ps_crse_catalog cc2
                     where cc2.eff_status = 'A'
                                and cc2.effdt <= c.start_dt
                                and cc2.crse_id = cc.crse_id),
                    (select min(cc3.effdt)
                     from dwsysadm.ps_crse_catalog cc3
                     where cc3.eff_status = 'A'
                                and cc3.crse_id = cc.crse_id))
    and
    ( $matchcondition )
order by c.strm, c.institution, c.subject, c.catalog_nbr, c.class_section
SQL;
#print $sql;
        $classes = $this->db->get_records_sql($sql);
        return $classes;
    }

    /**
     * We must clean these carefully because the parameters go directly into
     * the SQL rather than being passed as parameters.
     * This is public for unit testing.
     */
    public function convert_search_params_to_sql($searchparams) {

        // All four columns in ppsft are varchars.

        $enabled_terms = enrol_get_plugin('umnauto')->get_term_map(true);
        $institutions = ppsft_institutions();

        $conditions = array();
        foreach($searchparams as $p) {

            $term = $p['term'];
            $inst = $p['institution'];

            if (empty($term) or ! array_key_exists($term, $enabled_terms)) {
                continue;
            }

            if (empty($inst) or ! array_key_exists($inst, $institutions)) {
                continue;
            }

            if (array_key_exists('clsnbr', $p)) {
                // Search by class number.

                $clsnbr = $p['clsnbr'];
                if (empty($clsnbr) or preg_match('/[^0-9]/', $clsnbr)) {
                    continue;
                }

                $conditions[] =
                  "( c.strm='$term' and c.institution='$inst' and c.class_nbr=$clsnbr )";
            } else {
                // Search by catalog subject and catalog number.

                $subj = strtoupper($p['subject']);
                $cata = $p['catalog'];

                if (empty($subj) or preg_match('/[^A-Z]/', $subj)) {
                    continue;
                }

                if (empty($cata) or preg_match('/[^A-Za-z0-9, *%]/', $cata)) {
                    continue;
                }

                $catalognbrs = explode(',', $cata);
                $catconditions = array();
                foreach ($catalognbrs as $catalognbr) {
                    $catalognbr = trim(strtr($catalognbr, array('*'=>'%')));
                    if (FALSE === strpos($catalognbr, '%')) {
                        $catconditions[] = "c.catalog_nbr = '$catalognbr'";
                    } else {
                        $catconditions[] = "c.catalog_nbr like '$catalognbr'";
                    }
                }
                $catconditionstr = implode(" or ", $catconditions);

                $conditions[] =
                  "( c.strm='$term' and c.institution='$inst' and c.subject='$subj' "
                     ." and ($catconditionstr) )";
            }
        }
        return implode("\n or \n", $conditions);
    }

    /**
     * Get student emplids with updates from a given time.
     * Currently, this gets row data in a relatively raw form from
     * the underlying table.
     * Using rownum as first column to ensure that we do not lose
     * records as Moodle uses the first column for map key.
     */
    public function get_enrollment_updates_since($since_time) {

        $since_time = $since_time ?: '2000-01-01T00:00:01';

        $sql =<<<SQL
select rownum,
       to_char(um_syseffdt, 'YYYY-MM-DD"T"HH24:MI:SS') as syseffdt,
       um_trig_emplid,
       um_changetype,
       um_keyvalue
from cssysadm.o_ps_um_da_dly_audit a
where um_identifier = 'STDNT_ENRL'
  and um_syseffdt >= to_date(:since_time, 'YYYY-MM-DD"T"HH24:MI:SS')
order by um_syseffdt asc
SQL;

        $enrollment_updates = $this->db->get_records_sql($sql,
                                                         array('since_time'=>$since_time));
        return $enrollment_updates;
    }


    /**
     * Given the triplet, returns class data.
     */
    // TODO: Might also need to get acad_group_desc (is this college?) for
    //       all_stats report.  Neither of
    //       these attributes appear to be available from o_ps_class_tbl.
    public function get_class_by_triplet($term, $institution, $class_nbr) {

        // Not using join syntax for ps_crse_catalog because joining ps_crse_catalog
        // (in dwsysadm) causes an ORA-02019.  This might be due to an Oracle bug.
        // Another connection arrangement might not have this problem; currently
        // the other views are in cssysadm.

        // The nested subquery with the union is designed to get either the most
        // recently effective catalog record or (if there are none with an effdt in
        // the past) the catalog record with the earliest effdt in the future.
        // If we also need to include cases where there are NO catalog records,
        // we could union the entire query with a similar query except with NOT EXISTS
        // on the subquery.

        // Should investigate how ps_crse_catalog (for course_title_long)
        // impacts performance.

        $sql =<<<SQL
select distinct c.strm || c.institution || c.class_nbr as triplet,
        cc.course_title_long as long_title,
        c.class_nbr, c.strm as term, c.institution,
        c.subject, c.catalog_nbr, c.class_section,
        c.start_dt, c.end_dt, c.enrl_tot,
        c.descr, c.class_section as section
from cssysadm.o_ps_class_tbl c, dwsysadm.ps_crse_catalog cc
where cc.crse_id = c.crse_id
    and cc.eff_status = 'A'
    and cc.effdt = coalesce(
                    (select max(cc2.effdt)
                     from dwsysadm.ps_crse_catalog cc2
                     where cc2.eff_status = 'A'
                                and cc2.effdt <= c.start_dt
                                and cc2.crse_id = cc.crse_id),
                    (select min(cc3.effdt)
                     from dwsysadm.ps_crse_catalog cc3
                     where cc3.eff_status = 'A'
                                and cc3.crse_id = cc.crse_id))
  and c.strm = :term
  and c.institution = :institution
  and c.class_nbr = :class_nbr
SQL;

        $class = $this->db->get_record_sql($sql,
                                           array('term'        => $term,
                                                 'institution' => $institution,
                                                 'class_nbr'   => $class_nbr));
        return $class;
    }


    /**
     * Given an emplid and minimum term, returns a list of
     * classes for which the emplid person is an instructor.
     * If no $min_term, uses result from get_current_term.
     */
    public function get_instructor_classes($emplid, $min_term=null) {

        if (!$min_term) {
            $min_term = $this->get_current_term();
        }

        $sql =<<<SQL
select distinct c.strm || c.institution || c.class_nbr as triplet,
        c.class_nbr, c.strm as term, c.institution,
        c.subject, c.catalog_nbr, c.class_section,
        c.start_dt, c.end_dt, c.enrl_tot,
        c.descr, c.class_section as section
from cssysadm.o_ps_class_instr ci
  join cssysadm.o_ps_class_tbl c
        on  c.crse_id = ci.crse_id
        and c.crse_offer_nbr = ci.crse_offer_nbr
        and c.strm = ci.strm
        and c.session_code = ci.session_code
        and c.class_section = ci.class_section
where ci.emplid = :emplid and ci.strm >= :term and ci.emplid <> ' '
SQL;

        $classes = $this->db->get_records_sql($sql,
                                              array('emplid' => $emplid,
                                                    'term'   => $min_term));
        return $classes;
    }

    /**
     * Given a triplet, returns the instructor emplids for the class.
     */
    public function get_class_instructors($term, $institution, $class_nbr) {

        $sql =<<<SQL
select ci.emplid
from cssysadm.o_ps_class_instr ci
  join cssysadm.o_ps_class_tbl c
        on  c.crse_id = ci.crse_id
        and c.crse_offer_nbr = ci.crse_offer_nbr
        and c.strm = ci.strm
        and c.session_code = ci.session_code
        and c.class_section = ci.class_section
where c.strm        = :term
  and c.institution = :institution
  and c.class_nbr   = :classnbr
  and ci.emplid <> ' '
SQL;

        return $this->db->get_fieldset_sql($sql,
                                           array('term'        => $term,
                                                 'institution' => $institution,
                                                 'classnbr'   => $class_nbr));
    }

    /**
     * Input parameter, $triplets, is an array of triplets where each
     * triplet is an array with keys 'term', 'institution', and 'clsnbr'.
     * Returns an array of emplids.
     * Possible PeopleSoft instr_type values are PI, PRXY, SI, TA, for
     * primary, proxy, secondary, and ta, respectively.
     * Making private for now just because it is only used internally.  Can be
     * made public, if necessary.
     */
    private function get_instructors_for_classes($triplets, $ppsftroles=null) {

        $classmatchcondition = $this->convert_search_params_to_sql($triplets);

        $rolematchcondition = $this->convert_roles_array_to_sql($ppsftroles);

        if (! trim($classmatchcondition)) {
            return array();
        }

        $sql =<<<SQL
select distinct ci.emplid
from cssysadm.o_ps_class_instr ci
  join cssysadm.o_ps_class_tbl c
        on  c.crse_id = ci.crse_id
        and c.crse_offer_nbr = ci.crse_offer_nbr
        and c.strm = ci.strm
        and c.session_code = ci.session_code
        and c.class_section = ci.class_section
where ( $classmatchcondition ) $rolematchcondition and ci.emplid <> ' '
SQL;

        return $this->db->get_fieldset_sql($sql);
    }

    /**
     * Returns an array of emplids that contains primary instructors
     * for the classes listed in $triplets.
     */
    public function get_primary_instructors_for_classes($triplets) {
        $ppsftroles = array('PI');
        return $this->get_instructors_for_classes($triplets, $ppsftroles);
    }

    private function convert_roles_array_to_sql($ppsftroles) {
        if (empty($ppsftroles)) return '';
        $roles = preg_grep('/^\w+$/', $ppsftroles);
        if (empty($roles)) return '';
        return " and ci.instr_role in ('".implode("','", $roles)."') ";
    }

    /**
     * This is for get_class_enrollments and get_student_enrollments.  We don't want
     * a given E record if there exists another E record for that student-class that
     * has a more recent add date and either does not have drop date or both the given
     * and the other E record both have drop dates.  We also don't want a given E
     * record if it has a drop date and there is another E record without a drop date.
     */
    private $filterEStatusSql =<<<SQL
not exists (select *
            from cssysadm.o_ps_stdnt_enrl se3
            where se3.emplid      = se.emplid
              and se3.strm        = se.strm
              and se3.institution = se.institution
              and se3.class_nbr   = se.class_nbr
              and se3.stdnt_enrl_status = 'E'
              and ((se3.enrl_add_dt > se.enrl_add_dt and
                    (se3.enrl_drop_dt is null or
                     (se3.enrl_drop_dt is not null and se.enrl_drop_dt is not null)
                    )
                   or (se3.enrl_drop_dt is null and se.enrl_drop_dt is not null)
                   )
                  )
            )
SQL;

    /**
     * This is for get_class_enrollments and get_student_enrollments.  We don't want
     * a given D record if there exists an E record for that student-class.  We also
     * don't want a given D record if there exists another D record that either has
     * a more recent drop date or has the same drop date and a more recent add date.
     */
    private $filterDStatusSql =<<<SQL
not exists (select *
            from cssysadm.o_ps_stdnt_enrl se2
            where se2.emplid      = se.emplid
              and se2.strm        = se.strm
              and se2.institution = se.institution
              and se2.class_nbr   = se.class_nbr
              and (se2.stdnt_enrl_status = 'E'
                   or (se2.stdnt_enrl_status='D'
                       and (se2.enrl_drop_dt > se.enrl_drop_dt
                            or (se2.enrl_drop_dt = se.enrl_drop_dt
                                and se2.enrl_add_dt > se.enrl_add_dt
                               )
                           )
                      )
                  )
           )
SQL;

    /**
     * Given an emplid and minimum term, returns a list of
     * class enrollments for the emplid person.
     * If no $min_term, uses result from get_current_term.
     * Includes drops and withdrawals in addition to active enrollments.
     */
    public function get_student_enrollments($emplid, $min_term=null) {

        if (!$min_term) {
            $min_term = $this->get_current_term();
        }

        $sql =<<<SQL
select distinct se.strm || se.institution || se.class_nbr as triplet,
       se.class_nbr, se.strm as term, se.institution, se.stdnt_enrl_status,
       se.grading_basis_enrl as grading_basis,
       nvl2(se.enrl_drop_dt,
            case se.stdnt_enrl_status when 'E' then 'H' else 'D' end,
            se.stdnt_enrl_status) status,
       to_char(se.enrl_add_dt, 'IYYY-MM-DD') as add_date,
       to_char(se.enrl_drop_dt, 'IYYY-MM-DD') as drop_date
from cssysadm.o_ps_stdnt_enrl se
where se.emplid = :emplid
  and se.strm >= :term
  and ((se.stdnt_enrl_status = 'E' and $this->filterEStatusSql)
       or (se.stdnt_enrl_status = 'D' and $this->filterDStatusSql)
      )
SQL;

        $classes = $this->db->get_records_sql($sql,
                                              array('emplid' => $emplid,
                                                    'term'   => $min_term));
        return $classes;
    }

    /**
     * Given a triplet, returns the enrollments for that class.  Includes
     * drops and withdrawals in addition to active enrollments.
     */
    public function get_class_enrollments($term, $institution, $class_nbr) {
        // Using 'H' status for withdrawn.  'W' is already used for wait listed.
        //      Even though we don't get W records, avoiding confusion and
        //      possible issues by not using.  We set to 'H' (for withdrawn)
        //      when stdnt_enrl_status is 'E' but enrl_drop_dt is not null.
        $sql =<<<SQL
select distinct se.emplid, se.stdnt_enrl_status,
       se.grading_basis_enrl as grading_basis,
       nvl2(se.enrl_drop_dt,
            case se.stdnt_enrl_status when 'E' then 'H' else 'D' end,
            se.stdnt_enrl_status) status,
       to_char(se.enrl_add_dt, 'IYYY-MM-DD') as add_date,
       to_char(se.enrl_drop_dt, 'IYYY-MM-DD') as drop_date
from cssysadm.o_ps_stdnt_enrl se
where se.strm        = :term
  and se.institution = :institution
  and se.class_nbr   = :classnbr
  and ((se.stdnt_enrl_status = 'E' and $this->filterEStatusSql)
       or (se.stdnt_enrl_status = 'D' and $this->filterDStatusSql)
      )
SQL;

        $students = $this->db->get_records_sql($sql,
                                    array('term'        => $term,
                                          'institution' => $institution,
                                          'classnbr'    => $class_nbr));
        return $students;
    }

    /**
     * Gets the current term as a four-digit string.
     */
    public function get_current_term() {

        // cssysadm is required below probably because no synonym
        // is set up in Oracle for this view.

        $sql =<<<SQL
select max(tt.strm)
from cssysadm.o_ps_term_tbl tt
where tt.institution = 'UMNTC'
  and tt.acad_career = 'UGRD'
  and tt.term_begin_dt <= sysdate
SQL;

        $term = $this->db->get_field_sql($sql, null, MUST_EXIST);
        return $term;
    }

    /**
     * Starting with the term passed in as a parameter, gets all the
     * ppsft terms that have any classes associated with them.
     */
    function get_terms_since($start_term) {

        # TODO: Join with o_ps_term_tbl and get descr ("Spring 2011"), also?

        $sql =<<<SQL
select distinct STRM
from cssysadm.o_ps_class_tbl
where strm >= :term
order by strm
SQL;

        return $this->db->get_fieldset_sql($sql, array('term'=>$start_term));
    }

    /**
     * Some users (especially in Duluth) take actions that delete enrollment rows
     * rather than update the enrollment to 'D'.  We can detect those actions
     * using o_ps_audit_um_s_enrl.
     * Returns a single timestamp if the most recent action for the passed emplid
     * and triplet is a 'D'.  Otherwise, returns null.  Currently, all rows in the
     * table have audit_actn = 'D'.
     */
    public function get_enrollment_row_delete_action($emplid, $term, $institution, $class_nbr) {
        $sql =<<<SQL
select audit_stamp
from o_ps_audit_um_s_enrl ae
where emplid = :emplid and
      strm = :term and
      institution = :institution and
      class_nbr = :class_nbr and
      audit_actn = 'D' and
      audit_stamp = (select max(audit_stamp)
                     from o_ps_audit_um_s_enrl ae2
                     where ae2.emplid=ae.emplid and
                           ae2.strm=ae.strm and
                           ae2.institution=ae.institution and
                           ae2.class_nbr=ae.class_nbr)
SQL;

        $params = array('emplid'      => $emplid,
                        'term'        => $term,
                        'institution' => $institution,
                        'class_nbr'   => $class_nbr);

        $deleted_ts = $this->db->get_field_sql($sql, $params, IGNORE_MISSING);
        return $deleted_ts;
    }

}

