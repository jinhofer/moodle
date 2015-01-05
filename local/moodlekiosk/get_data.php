<?php

/**
 * This page returns bulk data (category, course, role, ...) to MoodleKiosk
 * in CSV format
 */

require_once('../../config.php');
require_once('locallib.php');

// check for required params (required_param() prints HTML)
$required_params = array('api_key', 'data_type');
$param_values = array();
foreach ($required_params as $param) {
    if (!isset($_GET[$param])) {
        header('HTTP/1.0 400 Invalid param ' . $param);
        echo "ERROR\n", 'Missing param: ', $param;
        exit(0);
    }
    else {
        $param_values[$param] = $_GET[$param];
    }
}

// check for api key
$stored_key = get_config('local_moodlekiosk', 'api_key');

if (!isset($_GET['api_key']) || $param_values['api_key'] != $stored_key) {
    header('HTTP/1.0 401 Invalid API key');
    echo "ERROR\n", 'Invalid API key';
    exit(0);
}

$lib = new local_moodlekiosk();

// process request
switch ($param_values['data_type']) {
    case 'user':
        $lib->get_data('user');
        break;

    case 'category':
        $lib->get_data('course_categories');
        break;

    case 'course':
        $lib->get_course_data();
        break;

    case 'course_enrol':
        $lib->get_course_enrol();
        break;

    case 'support':
        $lib->get_support();
        break;

    case 'ppsft_term':
        $lib->get_data('ppsft_terms');
        break;

    case 'ppsft_class':
        $lib->get_data('ppsft_classes');
        break;

    case 'ppsft_class_enrol':
        $lib->get_ppsft_class_enrol();
        break;

    case 'class_course':
        $lib->get_class_course();
        break;

    default:
        header('HTTP/1.0 400 Invalid data_type');
        echo "ERROR\n", 'Invalid data type: ', $param_values['data_type'];
}

exit(0);
