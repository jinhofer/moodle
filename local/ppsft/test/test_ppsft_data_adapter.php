<?php
define('CLI_SCRIPT', true);

require('../../../config.php');
require('../lib.php');


$connection = ppsft_database_connection();

$adapter = ppsft_get_adapter();

if (false) {

$terms = $adapter->get_terms_since(1103);
print_r($terms);

$students = $adapter->get_class_enrollments(1109, 'UMNTC', 56837);
print_r($students);

$classes = $adapter->get_instructor_classes('1587375', '1123');
print_r($classes);

$classes = $adapter->get_instructor_classes('1587375');
print_r($classes);

$current_term = $adapter->get_current_term();
print($current_term."\n");

$class = $adapter->get_class_by_triplet(1109, 'UMNTC', 56837);
print_r($class);

$since_time = '2011-05-07T01:34:00'; 
$enrollment_updates = $adapter->get_enrollment_updates_since($since_time);
print_r($enrollment_updates);

$student_classes = $adapter->get_student_enrollments('4162851', '1113');
print_r($student_classes);

$instructors = $adapter->get_class_instructors(1109, 'UMNTC', 56837);
print_r($instructors);

}


$student_classes = $adapter->get_student_enrollments('3892768', '1113');
print_r($student_classes);

