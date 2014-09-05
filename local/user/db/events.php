<?php

$handlers = array (
    'user_created' => array (
         'handlerfile'      => '/local/user/lib.php',
         'handlerfunction'  => 'local_user_creator::user_created_handler',
         'schedule'         => 'instant'
     )
);