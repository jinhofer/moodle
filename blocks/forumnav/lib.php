<?php

include_once($CFG->dirroot.'/blocks/forumnav/constants.php');

// This implementation comes mostly from forum_get_discussions and
// forum_get_discussions_unread in mod/forum/lib.php. Changes in those
// are likely to mean that changes in this are appropriate.
function forumnav_get_discussions($cm, $user=null) {
    global $DB, $USER, $CFG;

    $user = $user ?: $USER;

    $params = array('forumid' => $cm->instance);

    $modcontext = context_module::instance($cm->id);

    if (!has_capability('mod/forum:viewdiscussion', $modcontext, $user)) {
        return array();
    }

    // forumnav_get_timelimit_sql can modify $params
    $timelimit = forumnav_get_timelimit_sql($user, $modcontext, $params);

    // forumnav_get_groupselect_sql can modify $params
    $groupselect = forumnav_get_groupselect_sql($user, $cm, $modcontext, $params);

    $sql = "SELECT d.id, d.name, d.timemodified, COUNT(unread.id) as unread
            FROM {forum_discussions} d
              LEFT JOIN (SELECT p.discussion, p.id
                         FROM {forum_posts} p
                           LEFT JOIN {forum_read} r ON  r.postid = p.id
                                                    AND r.userid = :ruserid
                         WHERE p.modified >= :cutoffdate AND r.id is NULL) unread
                     ON unread.discussion = d.id
            WHERE d.forum = :forumid
            GROUP BY d.id, d.name, d.timemodified
            ORDER BY d.timemodified DESC, d.id DESC";

    $params['ruserid'] = $user->id;
    $now = round(time(), -2);
    $cutoffdate = $now - ($CFG->forum_oldpostdays*24*60*60);

    $params['cutoffdate'] = $cutoffdate;

    return $DB->get_records_sql($sql, $params);
}

// HELPER FUNCTIONS FOLLOW

// This function modifies $params if a time limit applies.
function forumnav_get_timelimit_sql($user, $modcontext, &$params) {
    global $CFG;

    $timelimit = '';

    if (!empty($CFG->forum_enabletimedposts)) { /// Users must fulfill timed posts

        if (!has_capability('mod/forum:viewhiddentimedposts', $modcontext, $user)) {
            $now = round(time(), -2);
            $timelimit = " AND ((d.timestart <= :nowforstart AND (d.timeend = 0 OR d.timeend > :nowforend))";
            $params['nowforstart'] = $now;
            $params['nowforend'] = $now;
            if (isloggedin()) {
                $timelimit .= " OR d.userid = :useridfortime";
                $params['useridfortime'] = $user->id;
            }
            $timelimit .= ")";
        }
    }
    return $timelimit;
}

// This function modifies $params if group restriction applies.
function forumnav_get_groupselect_sql($user, $cm, $modcontext, &$params) {
    $groupselect = '';

    $groupmode    = groups_get_activity_groupmode($cm);
    $currentgroup = groups_get_activity_group($cm);

    if ($groupmode) {

        if ($groupmode == VISIBLEGROUPS or has_capability('moodle/site:accessallgroups', $modcontext, $user)) {
            if ($currentgroup) {
                $groupselect = "AND (d.groupid = :currentgroup OR d.groupid = -1)";
                $params['currentgroup'] = $currentgroup;
            }
        } else {
            //seprate groups without access all
            if ($currentgroup) {
                $groupselect = "AND (d.groupid = :currentgroup OR d.groupid = -1)";
                $params['currentgroup'] = $currentgroup;
            } else {
                $groupselect = "AND d.groupid = -1";
            }
        }
    }
    return $groupselect;
}

