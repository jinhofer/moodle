<?php

$settings->add(new admin_setting_configtext(
        'block_moodlekiosk/course_search_url',
        get_string('course_search_url', 'block_moodlekiosk'),
        get_string('course_search_url_desc', 'block_moodlekiosk'),
        'https://moodlekiosk.server/api/user_courses/'
));

$settings->add(new admin_setting_configtext(
        'block_moodlekiosk/search_token',
        get_string('search_token', 'block_moodlekiosk'),
        get_string('search_token_desc', 'block_moodlekiosk'),
        ''
));

$settings->add(new admin_setting_configtext(
        'block_moodlekiosk/instance_name',
        get_string('instance_name', 'block_moodlekiosk'),
        get_string('instance_name_desc', 'block_moodlekiosk'),
        ''
));

$settings->add(new admin_setting_configcheckbox(
        'block_moodlekiosk/non_acad_first',
        get_string('non_acad_first', 'block_moodlekiosk'),
        get_string('non_acad_first_desc', 'block_moodlekiosk'),
        true
));


$mini_list_sizes = array();
for ($i = 2; $i <= 15; $i++) {
    $mini_list_sizes[$i] = $i;
}
$mini_list_sizes['-1'] = get_string('all');

$settings->add(new admin_setting_configselect(
        'block_moodlekiosk/mini_list_size',
        get_string('mini_list_size', 'block_moodlekiosk'),
        get_string('mini_list_size_desc', 'block_moodlekiosk'),
        5,
        $mini_list_sizes
));


$hiding_tolerances = array();
for ($i = 0; $i <= 10; $i++) {
    $hiding_tolerances[$i] = $i;
}
$settings->add(new admin_setting_configselect(
        'block_moodlekiosk/hiding_tolerance',
        get_string('hiding_tolerance', 'block_moodlekiosk'),
        get_string('hiding_tolerance_desc', 'block_moodlekiosk'),
        3,
        $hiding_tolerances
));