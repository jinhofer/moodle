<?php

$capabilities = array(
    'block/library_resources:addinstance' => array(
        'captype'              => 'read',
        'contextlevel'         => CONTEXT_BLOCK,
        'archetypes'           => array(
            'editingteacher'  => CAP_ALLOW,
            'manager'         => CAP_ALLOW),
        'clonepermissionsfrom' => 'moodle/site:manageblocks')
);
