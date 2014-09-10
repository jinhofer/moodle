<?php

/**
 * Takes emplid and triplets on stdin delimited by new lines.  Each
 * emplid-triplet is emplid, term, institution, and class number
 * delimited by spaces.
 * If the enrollment record for the corresponding student and class was deleted
 * (instead of set to 'D') then the way to confirm that the vanished enrollment
 * was actually performed is to check for it in ps_audit_um_s_enrl.  That's
 * what this script does.  If it confirms the deletion, it then marks the user as
 * unenrolled in mdl_ppsft_class_enrol.
 *
 * Example: echo "1404355 1109 UMNTC 56837" | php sync_vanished_enrollment.php 
 * 
 * ORACLE_HOME must be defined in the environment.
 */

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');
require_once(__DIR__.'/../ppsft_data_updater.class.php');
require_once(__DIR__.'/../ppsft_data_adapter.class.php');
require_once($CFG->dirroot.'/local/user/lib.php');

$USER = get_admin();

$ppsft_adapter = ppsft_get_adapter();
$ppsft_updater = ppsft_get_updater($ppsft_adapter);

function get_ppsft_class_enrol_record_by_emplid_triplet(
                    $emplid, $term, $institution, $class_nbr)
{
    global $DB;

    $sql =<<<SQL
select pce.*
from {ppsft_class_enrol} pce
  join {user} u on u.id = pce.userid
  join {ppsft_classes} pc on pc.id = pce.ppsftclassid
where u.idnumber = :emplid
  and pc.term = :term
  and pc.institution = :institution
  and pc.class_nbr = :class_nbr
SQL;

    $params = array('emplid' => $emplid,
                    'term' => $term,
                    'institution' => $institution,
                    'class_nbr' => $class_nbr);

    $ppsftclassenrol = $DB->get_record_sql($sql, $params);
    return $ppsftclassenrol;
}

function set_vanished_to_dropped($ppsftclassenrol) {
    global $DB;

    if (!$ppsftclassenrol->vanished) {
        echo "ppsftclassenrol $ppsftclassenrol->id not flagged as vanished\n";
        return;
    }

    if ($ppsftclassenrol->status === 'D') {
        echo "ppsftclassenrol $ppsftclassenrol->id already marked as dropped\n";
        return;
    }

    echo "Marking ppsftclassenrol $ppsftclassenrol->id as dropped\n";
    $ppsftclassenrol->status = 'D';
    $DB->update_record('ppsft_class_enrol', $ppsftclassenrol);
}


while ($emplid_triplet = rtrim(fgets(STDIN, 32))) {

    list($emplid, $term, $institution, $class_nbr) = preg_split("/\s+/", $emplid_triplet);

    $deleted_timestamp = $ppsft_adapter->get_enrollment_row_delete_action(
                                                    $emplid,
                                                    $term,
                                                    $institution,
                                                    $class_nbr);

    if (empty($deleted_timestamp)) {
        echo "No enrollment deletion found for $emplid, $term, $institution, $class_nbr\n";
    } else {
        echo "Deletion audit timestamp: $deleted_timestamp\n";

        $ppsftclassenrol = get_ppsft_class_enrol_record_by_emplid_triplet(
                            $emplid, $term, $institution, $class_nbr);

        if (empty($ppsftclassenrol)) {
            echo "No ppsft_class_enrol for $emplid, $term, $institution, $class_nbr\n";
        }

        set_vanished_to_dropped($ppsftclassenrol);
    }
}


echo "done\n";

