<?php

/**
 * Lang strings for MoodleKiosk block
 *
 * @package    block_moodlekiosk
 * @copyright  2013 University of Minnesota
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Moodle kiosk';
$string['introtext'] = 'This block displays links to your previous Moodle course sites. Links will open in new tabs/windows. '.
                       'Click on the "Customize this page" link in the upper right to modify how these course site links display.';
$string['moodlekiosk:myaddinstance'] = 'Add a new Moodle Kiosk block to the My Moodle page';

// config
$string['search_token'] = 'Search API Token';
$string['search_token_desc'] = 'Authentication API Token to search course from MoodleKiosk system';
$string['instance_name'] = 'This instance name';
$string['instance_name_desc'] = 'Shortname of the current instance to identify itself against MoodleKiosk system';
$string['course_search_url'] = 'Course search URL';
$string['course_search_url_desc'] = 'URL of the remote MoodelKiosk server to perform search';

$string['non_acad_first'] = 'Non-academic section first';
$string['non_acad_first_desc'] = 'Whether to show the non-academic section before the academic section';
$string['non_acad_first_help'] = 'Whether to show the non-academic section before the academic section';
$string['mini_list_size'] = 'Number of courses to display';
$string['mini_list_size_desc'] = 'Number of courses to display when retracted';
$string['mini_list_size_help'] = 'Number of courses to display when retracted';
$string['hiding_tolerance'] = 'Hiding tolerance';
$string['hiding_tolerance_desc'] = 'Do not retract the list if the number of courses to be hidden is less than this';
// page
$string['searchlabel'] = 'Search courses across all Moodle instances (including archives):';
$string['linkretract'] = 'Shorten the course list';
$string['linkexpand'] = 'Show all {$a} courses';

// edit form
$string['display_header'] = 'Display settings';
$string['site_setting'] = 'Use site setting';

// errors
$string['noaction'] = 'No action specified';
$string['invalidaction'] = 'Unknown action: {$a}';
$string['invalidsearchvalue'] = 'Please enter at least 3 characters to perform the search.';
$string['searchresult'] = 'Course search results for "{$a}"';
$string['entersearchprompt'] = 'Please enter at least 3 characters to search';

