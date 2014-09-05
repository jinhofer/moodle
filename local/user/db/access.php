<?php

$capabilities = array(
    // being able to view idnumber, which is UMN emplid
    'local/user:view_idnumber' => array(
        'captype'          => 'read',
        'contextlevel'     => CONTEXT_SYSTEM,
        'riskbitmask'      => RISK_PERSONAL,
    ),
    // being able to use the bulk creation page
    'local/user:usebulk' => array(
        'captype'          => 'write',
        'contextlevel'     => CONTEXT_SYSTEM,
        'riskbitmask'      => RISK_PERSONAL,
    ),
    // STRY0010016 20130805 kerzn002
    // This capability is actually connected to the core functionality
    // of whether or not users can be directly created from a directory
    // search.  It would feel more natural if this were in lib/db/access.php
    // but in the interest of not modifying core access.php, it's here instead.
    // Also, since instructors need to have the ability to create new users,
    // this capability is granted to the teacher role in course context which is
    // also unusual since one would normally expect system actions (ie. adding users)
    // to be associated with system roles and privileges.
    'local/user:createfromdirectory' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'riskbitmask' => RISK_SPAM | RISK_PERSONAL
    )
);
