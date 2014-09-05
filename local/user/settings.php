<?php

$ADMIN->add('accounts', new admin_externalpage(
	'local_user',
	'Bulk user creation',
    $CFG->wwwroot.'/local/user/bulk_creation.php',
    array('local/user:usebulk')
));

