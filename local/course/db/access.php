<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    // Create, update and delete course categories. (Deleting a course category
    // does not let you delete the courses it contains, unless you also have
    // moodle/course: delete.) Creating and deleting requires this permission in
    // the parent category.
    'local/course:managecoursesettings' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/category:update'
    ),

);
