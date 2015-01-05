<?php

$handlers = array (
    'role_assigned ' => array (
        'handlerfile'      => '/local/moodlekiosk/locallib.php',
        'handlerfunction'  => 'moodlekiosk_role_assigned',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),

    'role_unassigned ' => array (
        'handlerfile'      => '/local/moodlekiosk/locallib.php',
        'handlerfunction'  => 'moodlekiosk_role_unassigned',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
);