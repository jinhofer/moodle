<?php

$capabilities = array(
    //being able to view all resources
    'local/library_reserves:viewallresources' => array(
        'captype'          => 'read',
        'contextlevel'     => CONTEXT_SYSTEM,
        'riskbitmask'      => RISK_PERSONAL,
        'archetypes'       => array(
            'teacher'          => CAP_ALLOW,
            'editingteacher'   => CAP_ALLOW,
            'manager'          => CAP_ALLOW,
        )
    )
);
