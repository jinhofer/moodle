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
 * Unit test for the filter_urlicon
 *
 * @package    filter_urlicon
 * @category   phpunit
 * @copyright  University of Minnesota 2013
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/urlicon/filter.php'); // Include the code to test


class filter_urlicon_testcase extends basic_testcase {

    function get_add_icon_to_urls_test_cases() {
        $texts = array (
            // FLIPGRID ================
            // not a link, leave it as is
            'http://flipgrid.com#123abc - URL'
                => 'http://flipgrid.com#123abc - URL',

            // link, add image
            '<a href="http://flipgrid.com#123abc" class="_blanktarget">http://flipgrid.com#123abc</a> - URL'
                => '<a href="http://flipgrid.com#123abc" class="_blanktarget">FLIPGRID_ICONhttp://flipgrid.com#123abc</a> - URL',

            // link, add image
            '<a target="_blank" href="http://flipgrid.com#123abc" class="_blanktarget">http://flipgrid.com#123abc</a> - URL'
                => '<a target="_blank" href="http://flipgrid.com#123abc" class="_blanktarget">FLIPGRID_ICONhttp://flipgrid.com#123abc</a> - URL',

            // link with separator between domain and fragment
            '<a href="http://flipgrid.com/#123abc" class="_blanktarget">http://flipgrid.com/#123abc</a> - URL'
                => '<a href="http://flipgrid.com/#123abc" class="_blanktarget">FLIPGRID_ICONhttp://flipgrid.com/#123abc</a> - URL',

            // GDOC without domain ===============
            // not a link, leave it as is
            'https://docs.google.com/document/d/1kh64J4z4YSOyB45y7RVEID_Xj_TdQnnCAbj__4KsT4/edit - URL'
                => 'https://docs.google.com/document/d/1kh64J4z4YSOyB45y7RVEID_Xj_TdQnnCAbj__4KsT4/edit - URL',

            // link, add image
            '<a href="https://docs.google.com/document/d/1kh64J4z4YSOyB45y7RVEID_Xj_TdQnnCAbj__4KsT4/edit" class="_blanktarget">https://docs.google.com/document/d/1kh64J4z4YSOyB45y7RVEID_Xj_TdQnnCAbj__4KsT4/edit</a> - URL'
                => '<a href="https://docs.google.com/document/d/1kh64J4z4YSOyB45y7RVEID_Xj_TdQnnCAbj__4KsT4/edit" class="_blanktarget">GOOGLEDOC_ICONhttps://docs.google.com/document/d/1kh64J4z4YSOyB45y7RVEID_Xj_TdQnnCAbj__4KsT4/edit</a> - URL',

            // GDOC with domain ===============
            // not a link, leave it as is
            'https://docs.google.com/a/umn.edu/document/d/1kh64J4z4YSOyB45y7RVEID_Xj_TdQnnCAbj__4KsT4/edit - URL'
                    => 'https://docs.google.com/a/umn.edu/document/d/1kh64J4z4YSOyB45y7RVEID_Xj_TdQnnCAbj__4KsT4/edit - URL',

            // link, add image
            '<a href="https://docs.google.com/a/umn.edu/document/d/1kh64J4z4YSOyB45y7RVEID_Xj_TdQnnCAbj__4KsT4/edit" class="_blanktarget">https://docs.google.com/a/umn.edu/document/d/1kh64J4z4YSOyB45y7RVEID_Xj_TdQnnCAbj__4KsT4/edit#abcdef=12</a> - URL'
                            => '<a href="https://docs.google.com/a/umn.edu/document/d/1kh64J4z4YSOyB45y7RVEID_Xj_TdQnnCAbj__4KsT4/edit" class="_blanktarget">GOOGLEDOC_ICONhttps://docs.google.com/a/umn.edu/document/d/1kh64J4z4YSOyB45y7RVEID_Xj_TdQnnCAbj__4KsT4/edit#abcdef=12</a> - URL',

            // link, add image
            '<a target="_blank" href="https://docs.google.com/a/umn.edu/document/d/1CYlA2a43zAcmovVm43jMTvo5DjWkV4WfCSRhX8UjM_c/edit#" class="_blanktarget">https://docs.google.com/a/umn.edu/document/d/1CYlA2a43zAcmovVm43jMTvo5DjWkV4WfCSRhX8UjM_c/edit#</a> - URL'
                            => '<a target="_blank" href="https://docs.google.com/a/umn.edu/document/d/1CYlA2a43zAcmovVm43jMTvo5DjWkV4WfCSRhX8UjM_c/edit#" class="_blanktarget">GOOGLEDOC_ICONhttps://docs.google.com/a/umn.edu/document/d/1CYlA2a43zAcmovVm43jMTvo5DjWkV4WfCSRhX8UjM_c/edit#</a> - URL',

            // GSS without domain ===============
            // not a link, leave it as is
            'https://docs.google.com/spreadsheet/ccc?key=0AgRehmiaw6badDR5eC1CSk41bk5YdTZUOFo4ss9JcXc&usp=drive_web#gid=1 - URL'
                    => 'https://docs.google.com/spreadsheet/ccc?key=0AgRehmiaw6badDR5eC1CSk41bk5YdTZUOFo4ss9JcXc&usp=drive_web#gid=1 - URL',

            // link, add image
            '<a href="https://docs.google.com/spreadsheet/ccc?key=0AgRehmiaw6badDR5eC1CSk41bk5YdTZUOFo4ss9JcXc&usp=drive_web#gid=1" class="_blanktarget">https://docs.google.com/spreadsheet/ccc?key=0AgRehmiaw6badDR5eC1CSk41bk5YdTZUOFo4ss9JcXc&usp=drive_web#gid=1</a> - URL'
                    => '<a href="https://docs.google.com/spreadsheet/ccc?key=0AgRehmiaw6badDR5eC1CSk41bk5YdTZUOFo4ss9JcXc&usp=drive_web#gid=1" class="_blanktarget">GOOGLESPREADSHEET_ICONhttps://docs.google.com/spreadsheet/ccc?key=0AgRehmiaw6badDR5eC1CSk41bk5YdTZUOFo4ss9JcXc&usp=drive_web#gid=1</a> - URL',

            // GSS with domain ===============
            // not a link, leave it as is
            'https://docs.google.com/a/umn.edu/spreadsheet/ccc?key=0AgRehmiaw6badDR5eC1CSk41bk5YdTZUOFo4ss9JcXc&usp=drive_web#gid=1 - URL'
                    => 'https://docs.google.com/a/umn.edu/spreadsheet/ccc?key=0AgRehmiaw6badDR5eC1CSk41bk5YdTZUOFo4ss9JcXc&usp=drive_web#gid=1 - URL',

            // link, add image
            '<a href="https://docs.google.com/a/umn.edu/spreadsheet/ccc?key=0AgRehmiaw6badDR5eC1CSk41bk5YdTZUOFo4ss9JcXc&usp=drive_web#gid=1" class="_blanktarget">https://docs.google.com/a/umn.edu/spreadsheet/ccc?key=0AgRehmiaw6badDR5eC1CSk41bk5YdTZUOFo4ss9JcXc&usp=drive_web#gid=1</a> - URL'
                    => '<a href="https://docs.google.com/a/umn.edu/spreadsheet/ccc?key=0AgRehmiaw6badDR5eC1CSk41bk5YdTZUOFo4ss9JcXc&usp=drive_web#gid=1" class="_blanktarget">GOOGLESPREADSHEET_ICONhttps://docs.google.com/a/umn.edu/spreadsheet/ccc?key=0AgRehmiaw6badDR5eC1CSk41bk5YdTZUOFo4ss9JcXc&usp=drive_web#gid=1</a> - URL',

            // link, add image
            '<a target="_blank" href="https://docs.google.com/a/umn.edu/spreadsheet/ccc?key=0AgRehmiaw6badDR5eC1CSk41bk5YdTZUOFo4ss9JcXc&usp=drive_web#gid=1" class="_blanktarget">https://docs.google.com/a/umn.edu/spreadsheet/ccc?key=0AgRehmiaw6badDR5eC1CSk41bk5YdTZUOFo4ss9JcXc&usp=drive_web#gid=1</a> - URL'
                    => '<a target="_blank" href="https://docs.google.com/a/umn.edu/spreadsheet/ccc?key=0AgRehmiaw6badDR5eC1CSk41bk5YdTZUOFo4ss9JcXc&usp=drive_web#gid=1" class="_blanktarget">GOOGLESPREADSHEET_ICONhttps://docs.google.com/a/umn.edu/spreadsheet/ccc?key=0AgRehmiaw6badDR5eC1CSk41bk5YdTZUOFo4ss9JcXc&usp=drive_web#gid=1</a> - URL',
        );

        $data = array();
        foreach ($texts as $text => $correctresult) {
            $data[] = array($text, $correctresult);
        }
        return $data;
    }

    /**
     * @dataProvider get_add_icon_to_urls_test_cases
     */
    function test_add_icon_to_urls($text, $correctresult) {
        $testablefilter = new testable_filter_urlicon();
        $testablefilter->add_icon_to_urls($text);
        $this->assertEquals($correctresult, $text);
    }

}


/**
 * Test subclass that makes all the protected methods we want to test public.
 */
class testable_filter_urlicon extends filter_urlicon {
    public function __construct() {
        $this->icon_defs = array(
            'FLIPGRID'           => 'FLIPGRID_ICON',
            'GOOGLEDOC'          => 'GOOGLEDOC_ICON',
            'GOOGLESPREADSHEET'  => 'GOOGLESPREADSHEET_ICON'
        );
    }

    public function add_icon_to_urls(&$text) {
        return parent::add_icon_to_urls($text);
    }
}
