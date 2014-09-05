<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local enrol external functions and service definitions.
 *
 * @package    local
 * @subpackage webservice
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(

    // === enrol related functions ===
    'local_user_get_by_x500' => array(
        'classname'   => 'local_user_external',
        'methodname'  => 'get_by_x500',
        'classpath'   => 'local/user/externallib.php',
        'description' => 'get Moodle users from UMN UIDs (x500)',
        'type'        => 'read',
        'capabilities'=> 'moodle/user:viewdetails'
    ),
    'local_user_get_by_emplid' => array(
        'classname'   => 'local_user_external',
        'methodname'  => 'get_by_emplid',
        'classpath'   => 'local/user/externallib.php',
        'description' => 'get Moodle users from a UMN emplID',
        'type'        => 'read',
        'capabilities'=> 'moodle/user:viewdetails,local/user:view_idnumber'
    ),
    'local_user_create_from_x500' => array(
        'classname'   => 'local_user_external',
        'methodname'  => 'create_from_x500',
        'classpath'   => 'local/user/externallib.php',
        'description' => 'create Moodle users from UMN x500',
        'type'        => 'write',
        'capabilities'=> 'moodle/user:create'
    ),
    'local_user_create_from_emplid' => array(
        'classname'   => 'local_user_external',
        'methodname'  => 'create_from_emplid',
        'classpath'   => 'local/user/externallib.php',
        'description' => 'create Moodle users from UMN emplIDs',
        'type'        => 'write',
        'capabilities'=> 'moodle/user:create,local/user:view_idnumber'
    )
);
