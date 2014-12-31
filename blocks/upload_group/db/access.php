<?php

$capabilities = array(
    'block/upload_group:addinstance'    => array(
        'riskbitmask'                   => RISK_DATALOSS,
        'captype'                       => 'write',
        'contextlevel'                  => CONTEXT_BLOCK,
        'archetypes'                    => array(
            'editingteacher'            => CAP_ALLOW,
            'manager'                   => CAP_ALLOW),
        'clonepermissionsfrom'          => 'moodle/site:manageblocks'
    )
);
