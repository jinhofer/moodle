<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'enrol/umnauto:config' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        )
    ),

    'enrol/umnauto:manage' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        )
    ),

    'enrol/umnauto:unenrol' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        )   
    ),  

    'enrol/umnauto:unenrolself' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
        )   
    ),  

    'enrol/umnauto:enrolanystudent' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array()
    )

);

