<?php
$string['pluginname'] = 'UMN auto-enrollment';
$string['pluginname_desc'] = 'The UMN auto-enrollment plugin supports automatic enrollment of students in a UMN class into a Moodle course.';

$string['managepagetitle'] = 'Automatic Enrollment';

$string['umnautoterms'] = 'Terms';

# Capability descriptions
$string['umnauto:config']          = 'Configure UMN auto-enrollment instances';
$string['umnauto:enrolanystudent'] = 'Add any class number';
$string['umnauto:manage']          = 'Manage enrolled users';
$string['umnauto:unenrol']         = 'Unenroll users from course';
$string['umnauto:unenrolself']     = 'Unenroll self from course';

$string['coursesettingsheader'] = 'UMN auto-enrollment';
$string['unenroloptions'] = 'Auto-enrollment on PeopleSoft events';
$string['autoenrolonly'] = 'Enroll only';
$string['autoenrolanddrop'] = 'Enroll and drop';
$string['autoenroldropandwithdraw'] = 'Enroll, drop, and withdraw';

$string['enrolusers'] = 'Enrol users';

$string['classtablecaption'] = 'Your PeopleSoft classes that may be linked to this Moodle course site';

$string['studenttablecaption'] = 'List of students in linked PeopleSoft class(es) and their enrollment status in PeopleSoft and Moodle';
$string['studenttabletitle'] = 'Linked PeopleSoft class {$a->subject} {$a->catalog_nbr} sec {$a->section} (class# {$a->class_nbr}) {$a->institution}';
$string['studenttableempty'] = 'No enrolled students';

$string['lastnamecolumnheader'] = 'Last Name';
$string['firstnamecolumnheader'] = 'First Name';
$string['psclasssectioncolumnheader'] = 'PS<br />Class/Sec';
$string['psclassnumbercolumnheader'] = 'PS<br />Class#';
$string['idnumbercolumnheader'] = 'ID';
$string['internetidcolumnheader'] = 'Internet ID';
$string['psenrolstatuscolumnheader'] = 'PS<br />Enroll Status';
$string['mdlenrolstatuscolumnheader'] = 'Moodle<br />Enroll Status';
$string['psenroldtcolumnheader'] = 'PS<br />Enroll Date';
$string['psdropdtcolumnheader'] = 'PS<br />Drop Date';
$string['linkedcolumnheader'] = 'Linked to<br />Course Site?';
$string['institutioncolumnheader'] = 'Institution';
$string['termcolumnheader'] = 'Term';
$string['titlecolumnheader'] = 'Title';
$string['studentcountcolumnheader'] = 'Student<br />Count';


$string['instructionstext'] =
'<em>Instructions:</em> To auto enroll students, click the \'Add\' button near the 5-digit class number, then click the \'Update course enrollment\' button.<br />
<strong>Please do NOT add all class numbers that you see on the list;</strong> select only those that correspond to the title of this particular Moodle site. Add multiple class numbers only in the case that your course is cross-listed, and double-check what semester you are adding.<br />
<strong>Co-designers</strong> who have Instructor access to the Moodle site <strong>cannot use this interface</strong>, only people listed in PeopleSoft as official instructors can use this interface.';

$string['invalidclassnbr'] = 'PeopleSoft class number is not valid';
$string['noclassesassociated'] = 'You have not added any PeopleSoft classes to this course site.';

