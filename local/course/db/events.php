<?php

defined('MOODLE_INTERNAL') || die();

$handlers = array(
    'course_deleted' => array (
        'handlerfile'     => '/local/course/lib.php',
        'handlerfunction' => 'local_course_course_deleted',
        'schedule'        => 'instant'
    )
);

$observers = array(
    array(	
        'eventname'   => '\local_instances\event\instance_deleted',
        'includefile' => '/local/course/lib.php',
        'callback'    => 'local_course_moodleinstance_deleted'
    )
);

