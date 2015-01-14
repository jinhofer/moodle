<?php

/**
 * Local admin external functions and service definitions.
 *
 * @package    local
 * @subpackage admin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(

    // === admin related functions ===
    'local_admin_get_roles' => array(
        'classname'   => 'local_admin_external',
        'methodname'  => 'get_roles',
        'classpath'   => 'local/admin/externallib.php',
        'description' => 'Get the list of Moodle roles',
        'type'        => 'read',
        'capabilities'=> ''		// @TODO: what capabilities is required?
    )
);
