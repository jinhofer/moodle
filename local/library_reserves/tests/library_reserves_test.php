<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com     //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Unit tests for library_reserves
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package local
 * @subpackage library_reserves
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}


global $CFG;
require_once($CFG->dirroot . '/local/library_reserves/lib.php');


/**
 *
 */
class library_reserves_test extends advanced_testcase {


    /**
     */
    function test_encode_class_triplet() {
        $service = new library_reserves_service();

        $test_cases = array(
            array('1125', 'UMNTC', '80385', '1125UMNTC80385'),
            array('1103', 'UMNDL', '00260', '1103UMNDL00260'),
            array('1139', 'UMNDL', '2635', '1139UMNDL2635')
        );

        foreach ($test_cases as $case) {
            $encoded = $service->encode_class_triplet($case[0], $case[1], $case[2]);
            $this->assertEquals($encoded, $case[3]);
        }
    }



    /**
     */
    function test_decode_class_triplet() {
        $service = new library_reserves_service();

        $test_cases = array(
            array('1125', 'UMNTC', '80385', '1125UMNTC80385'),
            array('1103', 'UMNDL', '00260', '1103UMNDL00260'),
            array('1139', 'UMNDL', '2635', '1139UMNDL2635')
        );

        foreach ($test_cases as $case) {
            $decoded = $service->decode_class_triplet($case[3]);
            $this->assertEquals($decoded['term'], $case[0]);
            $this->assertEquals($decoded['institution'], $case[1]);
            $this->assertEquals($decoded['class_nbr'], $case[2]);
        }
    }


    /**
     *
     */
    function test_retrieve_data() {

        // Ensure that this is a unit test and not reliant
        // on rd.lib.umn.edu.
        
        $stub = $this->getMockBuilder('library_reserves_curl')
                     ->disableOriginalConstructor()
                     ->getMock();
        $stub->setOption(CURLOPT_VERBOSE, true);
        $rv = <<<EOF
1135UMNTC87565,18712,1
1135UMNTC87565,359,2
1135UMNTC87565,360,3
1135UMNTC87565,361,4
1135UMNTC87565,362,5
1135UMNTC87565,363,6
1135UMNTC87565,364,7
1135UMNTC87565,365,8
1135UMNTC87565,366,9
1135UMNTC87565,367,10
1135UMNTC87565,368,11
1135UMNTC87565,369,12
1135UMNTC87565,370,13
1135UMNTC87565,371,14
1135UMNTC87565,372,15
1135UMNTC87565,373,16
1135UMNTC87565,374,17
1135UMNTC87565,375,18
1135UMNTC87565,376,19
1135UMNTC87565,377,20
1135UMNTC87565,378,21
1135UMNTC87565,379,22
1135UMNTC87565,380,23
1135UMNTC87565,381,24
1135UMNTC87565,382,25
1135UMNTC87565,383,26
1135UMNTC87565,384,27
1135UMNTC89525,18713,1
1135UMNTC89525,226,2
1135UMNTC89525,214,3
1135UMNTC89525,227,4
1135UMNTC89525,215,5
1135UMNTC89525,228,6
1135UMNTC89525,216,7
1135UMNTC89525,229,8
1135UMNTC89525,217,9
1135UMNTC89525,230,10
1135UMNTC89525,218,11
1135UMNTC89525,231,12
1135UMNTC89525,219,13
1135UMNTC89525,232,14
1135UMNTC89525,220,15
1135UMNTC89525,233,16
1135UMNTC89525,221,17
1135UMNTC89525,234,18
1135UMNTC89525,222,19
1135UMNTC89525,235,20
1135UMNTC89525,223,21
1135UMNTC89525,1106,22
1135UMNTC89525,1107,23
1135UMNTC89525,236,24
1135UMNTC89525,224,25
1135UMNTC89525,237,26
1135UMNTC89525,225,27

EOF;
// The trailing newline above is there for a reason - the libraries'
// web service adds an extra \0 at the end of the record so we simulate it here.

        $stub->expects($this->any())
             ->method('execute')
             ->will($this->returnValue($rv));

        $stub->expects($this->any())
             ->method('getInfo')
             ->will($this->returnValue(200));

        // Construct the object under test

        $syncer = new library_reserves_syncer($stub);

        // get data without saving to file
        $params = array('courses' => '1135UMNTC87565,1135UMNTC89525',
                        'request' => 'resources');
        $data = $syncer->retrieve_data($params);

        // get data to a temp file
        $filename = 'resources_1.csv';
        $filepath = $syncer->retrieve_data($params, $filename);

        $this->assertEquals(1176, strlen($data));
        $this->assertFileExists($filepath);
    }

    function test_sync() {
        global $DB;
        // The goal is to make sure that sync() is going to only sync those remote courses
        // that have corresponding ppsft sections.

        $this->resetAfterTest();
    }
}
