<?php

function local_myu_extends_navigation(global_navigation $nav) {
    global $COURSE;

    if ($COURSE->id != SITEID) {
        $course_context = context_course::instance($COURSE->id);

        if (!has_capability('moodle/course:update', $course_context)) {
            return false;
        }

        // first try to look into current courses
        if (($current_course = $nav->get('currentcourse')) && $current_course->contains_active_node()) {
            $course_node  = $current_course->get($COURSE->id);
        }
        else { // look into my courses
            $my_courses = $nav->get('mycourses');
            $course_node = $my_courses->get($COURSE->id);
        }

        // only add the link to the current course
        if ($course_node !== false) {
            $course_node->add(get_string('myu_settings', 'local_myu'), new moodle_url('/local/myu/course_prefs.php', array('courseid' => $COURSE->id)));
        }
    }
}