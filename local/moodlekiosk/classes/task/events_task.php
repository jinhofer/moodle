<?php

// As part of porting local/moodlekiosk to 2.8, moved the logic that was
// in local_moodlekiosk_cron, essentially as-is, to this task.

namespace local_moodlekiosk\task;

class events_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('kioskeventstask', 'local_moodlekiosk');
    }

    /**
     * flush the event cache to remote MoodleKiosk
     */
    public function execute() {
        global $CFG, $DB;

        $url = trim(get_config('local_moodlekiosk', 'listener_url'));

        // ignore if no remote URL provided
        if (empty($url)) {
            return;
        }

        $now = time();

        // get all the records in cache table prior to now
        $sql = 'SELECT cache.event AS cache__event,
                       user.username AS user__username,
                       context.contextlevel AS context__contextlevel,
                       context.instanceid AS context__instanceid,
                       role.shortname AS role__shortname
                FROM {moodlekiosk_event_cache} cache
                     INNER JOIN {user} user ON user.id = cache.userid
                     INNER JOIN {context} context ON context.id = cache.contextid
                     INNER JOIN {role} role ON role.id = cache.roleid
                WHERE cache.timemodified <= :now
                ORDER BY cache.timemodified ASC';

        $rs = $DB->get_recordset_sql($sql, array('now' => $now));

        $events = array();
        foreach ($rs as $r) {
            $events[] = array(
                'event'          => $r->cache__event,
                'username'       => $r->user__username,
                'contextlevel'   => $r->context__contextlevel,
                'instanceid'     => $r->context__instanceid,
                'role'           => $r->role__shortname);
        }
        $rs->close();    // free the recordset resource

        // quit if there's nothing to process
        if (count($events) == 0) {
            return;
        }

        // delete from cache the records that have been retrieved
        // delete before sending to avoid holding DB connection,
        // also doesn't care if data will be sent successfully or not
        $DB->delete_records_select('moodlekiosk_event_cache',
                                   'timemodified <= :now',
                                   array('now' => $now));

        // send the event data to remote MoodleKiosk
        $data = array('events'     => json_encode($events),
                      'instance'   => get_config('local_moodlekiosk', 'instance_name'),
                      'api_key'    => get_config('local_moodlekiosk', 'api_key'));

        require_once($CFG->dirroot.'/local/moodlekiosk/locallib.php');
        try {
            \local_moodlekiosk::post_data($url, $data);
        } catch (\Exception $ex) {
            error_log($ex);
            throw $ex;
        }
    }
}
