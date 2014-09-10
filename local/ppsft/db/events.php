<?php

defined('MOODLE_INTERNAL') || die();

$handlers = array(
    'user_deleted' => array (
        'handlerfile'     => '/local/ppsft/locallib.php',
        'handlerfunction' => 'ppsft_handle_user_deleted',
        'schedule'        => 'instant'
    )
);



