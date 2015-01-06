<?php

$string['pluginname'] = 'Course customizations including course requests';

// check_course_cache.php
$string['rebuild'] = 'Rebuild course cache';
$string['cachecmok'] = 'OK';
$string['cachecmerror'] = 'ERROR: The following cached course module does not exist in the database:';

// course request confirmation page
$string['courserequestconfirmation'] = 'Your Moodle course site request has been submitted and you will receive an email within a few days with the outcome.';
$string['requestanothersite'] = 'Request another course site';
$string['closewindow'] = 'Close window';

// Course hide/show
$string['coursechangevisibility'] = 'Confirm course visibility change';
$string['coursecheckshow'] = 'Are you sure you want to show this course to students?';
$string['coursecheckhide'] = 'Are you sure you want to hide this course from students?';
$string['coursehide'] = 'Hide this course';
$string['coursehiddensubheading'] = '(Hidden from Students)';
$string['courseshow'] = 'Show this course';

// ppsft search
$string['ppsftsearch'] = 'Auto-enrollment search options';
$string['ppsftsearchresults'] = 'Auto-enrollment search results';
$string['termselecthdr'] = 'Term*';
$string['institutionselecthdr'] = 'Institution*';
$string['subjecttexthdr'] = 'Subject*';
$string['catalogtexthdr'] = 'Catalog*';
$string['classnumbertexthdr'] = 'Class number*';
$string['searchbysubject'] = 'Search for courses by subject';
$string['searchbyclassnumber'] = 'Search for courses by class number';
$string['searchformyclasses'] = 'Search for my courses';
$string['catalogsearchdescription'] = 'This search allows you to return cross-subject and cross-institution offerings, e.g. PHAR 1002 for Twin Cities and Duluth.';
$string['classnumbersearchdescription'] = 'This search requires 5-digit class numbers to generate the selection list. All fields are required.';
$string['myclasssearchdescription'] = 'This search will return all courses for the selected term for which you are listed as an instructor in PeopleSoft.';

$string['subjecthelpnote'] = 'e.g. ENGL';
$string['catalognumberhelpnote'] = 'e.g. 1001 or 1001,1001H or 10*';
$string['classnumberhelpnote'] = 'e.g. 61538';

$string['ppsftsearchformempty'] = 'At least one row must have search parameters set.';
$string['ppsftsearchformmissingparams'] = 'Partially set rows are not allowed.';

$string['ppsftsearchclassesnotfound'] = 'No classes matching search parameters found.';
$string['ppsftsearchresultinstructions'] = 'Please check all the courses for which you would like to automatically enroll students to your Moodle course site.';

$string['ppsftsearchinstructions'] = 'Choose one of the following search methods to generate a list of courses to include for enrolling students in this course.';

$string['ppsftsearchresultnotselected'] = 'Please select at least one class.';

// migration server management
$string['configmanagemigrationservers'] = 'Course request migration servers';
$string['migrationserverwwwroot'] = 'Server instance URL';
$string['migrationenabled'] = 'Enabled';
$string['upgradeserverwwwroot'] = 'Upgrade instance URL';
$string['action'] = 'Action';
$string['migrationservers'] = 'Migration servers';
$string['addmigrationserver'] = 'Add migration server';
$string['migrationserver'] = 'Migration server';
$string['enabled'] = 'Enabled';
$string['migrationservertoremove'] = 'Migration server to remove';

$string['migrationclients'] = 'Migration clients';
$string['migrationclient'] = 'Migration client';
$string['migrationclientwwwroot'] = 'Client instance URL';
$string['addmigrationclient'] = 'Add migration client';
$string['migrationclienttoremove'] = 'Migration client to remove';

// requestgateway
$string['courserequestgatewayintro'] = '<table width="450" align="center">
<tr>
<td align="center" bgcolor="#FFFFAA">
<strong>Note:</strong> Moodle course site requests for Fall 2013 will ONLY be entered in Moodle 2.4.  If you started working on your Fall 2013 course site in Moodle 2.2, you will have to request a Moodle 2.4 course site based on the 2.2 version (copy the 2.2 URL into the 2.4 request form).</p>
</td>
</tr>
</table>

<div style="text-align:center">
  <p>One request = One new Moodle course site<br>
    To get multiple Moodle course sites, submit a <em>separate</em> request for each separate Moodle course site.</p>Please do not use this form to request changes to already <em>existing </em>course sites. <br>
</p>
<p>    Send all questions to <a href="mailto:moodle@umn.edu">moodle@umn.edu</a>.</p>
</div>';
$string['requestformlink_acad']    = 'Academic course site with auto-enrollment';
$string['requestformlink_nonacad'] = 'Development or non-academic course site';

$string['requestgatewayheading'] = 'Request a Moodle course site';

// Course requests
$string['fullnamecourseta'] = 'Course title';
$string['fullnamecoursenonacad'] = 'Course site title';
$string['fullnamecourseacad'] = 'Course site title';
$string['shortnamecourseta'] = 'Course subject and number (ACCT 2001)';
$string['shortnamecoursenonacad'] = 'Short title';

$string['autoenrolclasses'] = 'Auto-enrol class(es):&nbsp';
$string['primaryinstructors'] = 'Primary instructor(s):&nbsp';

$string['yourrole'] = 'Your role in the course site';

$string['sendemail'] = 'Send email';

$string['disclaimerhead'] = 'Disclaimer';
$string['disclaimertext'] = 'By submitting this form, I assert that I am authorized to request a Moodle course site on behalf of the instructor or program.  Instructors of record are automatically added as instructors and notified of requests made on their behalf.';

$string['addanotherrow'] = 'Add another row';
$string['lookupclassnumbers'] = '<a target="_blank" href="http://onestop2.umn.edu/courseinfo/searchcriteria.jsp?campus={$a}">Lookup class numbers</a>';

$string['nonacademiccourserequest'] = 'Development/non-academic course site request';
$string['academiccourserequest'] = 'Academic course site request';

$string['courserequest'] = 'Course request';
$string['courserequestsuccess'] = 'Successfully saved your course request. Expect an email within a few days with the outcome';
$string['fullnamehelpnote'] = 'e.g. OIT Assignments and Workshops - Student';
$string['shortnamehelpnote'] = 'e.g. AssignWorkStudent';
$string['primaryinstructoremailnote'] = 'Note: Email notification will always be sent to primary instructor';
$string['sourcecourseurlhelpnote'] = 'Copy URL from previous Moodle course';
$string['courserequestsupporthelpnote'] = 'Additional comments or instructions for Moodle Support';
$string['addadditionalroleuserhelpnote'] = '<a href="http://www.oit.umn.edu/moodle/instructor-guides/roles-overview/index.htm" target="_blank">Role types</a>';

$string['allowrequest'] = 'Allow requests';

$string['academicinstructions'] = '<div style="background-color:#FEF5CA; color:#B22222; text-align:center; padding:2px 0 1px; margin:2px"> <p><strong>One request = One Moodle course site</strong></p></div>

<div style="text-align:center; padding:3px 0 1px; margin-bottom:3px">
<p>To get one Moodle course site for several section(s), select the sections and submit <em>one</em> request.<br />
To get a separate Moodle course site for each section, submit <em>separate</em> requests.</p>
<p>Remember that you <em>cannot re-use the same course site</em> next semester if your students are different. You will need to request a new course site.</p></div>';
$string['callnumbers'] = '5-digit Class Number(s)';
$string['classesheader'] = 'Courses';
$string['coursenotlisted'] = 'I do not see the course section I need below OR I am not the official instructor.';
$string['courserequestsupport'] = 'Special instructions';
$string['courses'] = 'Courses';
$string['fullnametakenbycourse'] = 'This full name is already used by another course. Please add a random number at the end of this field and then click "Request a course" again, so your request will be processed.';
$string['fullnametakenbyrequest'] = 'This full name is already used by a pending course request. Please add a random number at the end of this field and then click "Request a course" again, so your request will be processed.';
$string['internetidinvalid'] = 'Please include comma-separated <strong>internet IDs</strong> only. E.g., thoms999';
$string['missingfullname'] = 'Missing full name';
$string['missingshortname'] = 'Missing short name';
$string['missingyourrole'] = 'Missing your role';
$string['missingterm'] = 'Missing session/term';
$string['notapplicable'] = 'Not Applicable';
$string['requestcourse'] = 'Request a course';
$string['sections'] = 'Course Section(s) (sec 001 and 003)';
$string['sourcecourseidinvalid'] = 'No course with id {$a}';
$string['sourcecourseinstanceinvalid'] = 'URL points to an invalid source Moodle instance';
$string['sourcecourseurl'] = 'URL of Moodle course site to copy';
$string['sourcecourseurlinvalid'] = 'The URL of a Moodle course site provided is invalid. Please check for correctness. A correct URL should look something like "https://moodle.umn.edu/course/view.php?id=1234567"';
$string['shortnametakenbycourse'] = 'This short name is already used by another course. Please add a random number at the end of this field and then click "Request a course" again, so your request will be processed.';
$string['shortnametakenbyrequest'] = 'This short name is already used by a pending course request. Please add a random number at the end of this field and then click "Request a course" again, so your request will be processed.';
$string['sourcecourseurl_instructions'] = '<span style="font-weight:bold;">Copy content/Roll-over:</span> Paste FULL URL of your old Moodle course site if you want the support team to transfer content over. <br>
  1.9 sites: <a href="https://moodle.umn.edu" target="_blank">https://moodle.umn.edu</a>, archived sites: <a href="https://archive.moodle.umn.edu" target="_blank"> https://archive.moodle.umn.edu</a><br>';
$string['submissioncomments'] = 'Click "Request a course" and wait for a confirmation screen to appear.  If you do not see it, make sure that you have filled out ALL required fields in the form above. <p> By requesting a Moodle course site, the requester and faculty sponsor (when applicable) agree to the policies listed at <a href="http://www.oit.umn.edu/moodle/policies/" target="_blank"> http://www.oit.umn.edu/moodle/policies/</a>';
$string['userinbothinstructorfields'] = 'Same internet ID has been entered in both instructor/designer fields.';
$string['x500notinldap'] = 'The following Internet IDs are not valid: {$a}';

$string['depth1category'] = 'Institution/primary Moodle category';
$string['depth1select'] = '-- Select one --';
$string['missingdepth1category'] = 'Missing category';
$string['depth2category'] = 'College/secondary Moodle category';
$string['depth2select'] = '-- Select one --';
$string['missingdepth2category'] = 'Missing subcategory';
$string['depth3category'] = 'Department/tertiary Moodle category';
$string['depth3select'] = 'None';

$string['roleselect'] = '-- Select one --';
$string['roleselectnone'] = 'None';

$string['additionalroleselecttop'] = '-- Select one --';
$string['additionalroleselect'] = 'Additional user role';
$string['additionalroleuserlist'] = 'Internet IDs of users you want to assign this role (separate with commas)';
$string['addadditionalrolerow'] = 'Add another user role';

// Column headers for ppsft classes table
$string['termcolumnheader'] = 'Term';
$string['classnbrcolumnheader'] = 'Class #';
$string['subjectcolumnheader'] = 'Subject';
$string['catalognbrcolumnheader'] = 'Catalog<br />Number';  # TO BE REMOVED
$string['descrcolumnheader'] = 'Course title';
$string['sectioncolumnheader'] = 'Section';  # TO BE REMOVED
$string['institutioncolumnheader'] = 'Institution';
$string['catsectioncolumnheader'] = 'Catalog#-Sec';
$string['classtypecolumnheader'] = 'Type';
$string['sitelinkcolumnheader'] = 'Existing<br />course site';

// Column headers for pending table
$string['shortnamependinghdr'] = 'Short name';
$string['fullnamependinghdr'] = 'Full name (Title)';
$string['requesterpendinghdr'] = 'Requested by';
$string['reasonpendinghdr'] = 'Reason for course request';
$string['enrolleespendinghdr'] = 'Enroll';
$string['termpendinghdr'] = 'Term';
$string['sectionspendinghdr'] = 'Section';
$string['callnumberspendinghdr'] = 'Class #';
$string['sourcecourseurlpendinghdr'] = 'URL';
$string['migrateuserseditpendinghdr'] = 'Users?/<br />Edit URLs';
$string['actionpendinghdr'] = 'Action';

// Others for pending page
$string['startimport'] = 'Start import';
$string['pendinginprogress'] = 'In progress';
$string['pendingready'] = '<span style="color:green;font-weight:bold;">Ready</span>';
$string['pendingerror'] = '<span style="color:red;font-weight:bold;">Error</span>';
$string['pendingnoerrorinfoavail'] = 'No error information available';

// request edit form

$string['editrequestpagetitle'] = 'Edit course request';
$string['sourcecourseurl_editinstructions'] = 'The URL of the course from which to create the new course';
$string['copyuserdata'] = 'Copy user data';

// request category map
$string['configcourserequestcategorymap'] = 'Course request category map';
$string['categorymappinglist'] = 'Category mapping';
$string['categorymapinstructions'] = 'Check the subcategories that you wish to display on the course request dropdown.';
$string['categories'] = 'Course categories';
$string['mult'] = 'Multi-term';

// Text for approve and reject links in pending table
$string['approve'] = 'Approve';
$string['approvesilently'] = 'Approve&nbsp;Silently';
$string['reject'] = 'Reject';
$string['rejectsilently'] = 'Reject&nbsp;Silently';

// Reject and approve form headers
$string['notsilentrejecthdr'] = '<div style="color:green">Not silently rejecting</div>';
$string['notsilentapprovehdr'] = '<div style="color:green">Not silently approving</div>';
$string['silentrejecthdr'] = '<div style="color:red">Silently rejecting</div>';
$string['silentapprovehdr'] = '<div style="color:red">Silently approving</div>';

$string['emailtestonly'] = '<div style="font-weight:bold">***********************  EMAIL TEST ONLY  ***********************</div>';

// Some other reject and approve strings.
$string['coursereasonforapproving']      = 'Add optional text to include in acceptance message<br />(this will be emailed to the requester)';
$string['coursereasonforapprovingemail'] = 'This will be emailed to the requester';
$string['coursereasonforrejecting']      = 'Your reasons for rejecting this request';
$string['coursereasonforrejectingemail'] = 'This will be emailed to the requester';

$string['courserejected']        = 'Course has been rejected and the requester has been notified.';
$string['courserejected_silent'] = 'Course has been rejected and nobody has been notified.';


// emails

$string['courseapprovedemail_subject'] = 'Your Moodle 2 course has been approved!';
$string['courserejectedemail_subject'] = 'Your Moodle 2 course has been rejected';

// The bodies of the rejection and approval emails each have up to three parts. The first is
// a header that has content only if the email is to be silent (that is, not sent to the requester).
// The second part is a user-specific part that contains the salutation. The third part is
// common to all the recipients and contains general instructions and request information.
// For the rejection email, the third part is not a separately-defined string; it is a parameter
// to the user-specific string.

$string['courseapprovedemail_silent'] = "Silently approved\n\n";
$string['courserejectedemail_silent'] = "Silently rejected\n\n";

$string['courserejectedemail_user'] = 'Dear {$a->userfullname},'."\n\n".'Your request for the course {$a->courseshortname}, {$a->coursefullname} could not be completed. Here is the reason provided:'."\n\n".'{$a->reason}';

$string['courseapprovedemail_user'] = 'PLEASE DO NOT REPLY TO THIS EMAIL.

Dear {$a->userfullname} ({$a->username}),';

$string['courseapprovedemail_common'] = 'The request for a new Moodle 2.6 course site for {$a->shortname} submitted by ({$a->requester_username}) has been approved.

COPYING CONTENT
If you have asked us to COPY CONTENT from a previous course URL, please allow 5-10 minutes for the content to be copied before you access the new course site. The URL to the course site is {$a->url}.

COURSE ADMINISTRATION
Note that your new course site is HIDDEN from students, meaning your class will not be able to see or access the content until you make the site available to them.

SUMMARY OF THE REQUEST
Please review the information submitted by you below. Direct any concerns or inquiries to Moodle Support by submitting a Help Request Form at http://z.umn.edu/moodlehelp.

    Requester Internet ID: {$a->requester_username}
    Requested by: {$a->requester_name}
    Course Full Title: {$a->fullname}
    Course Short Title: {$a->shortname}
    Course Section(s): {$a->sections}
    Class Number(s): {$a->callnumbers}
    Category: {$a->category_string}
{$a->assignedroles}
    Additional comments or instructions for Moodle support: {$a->reason}
    Previous Course URL: {$a->sourcecourseurl}

ADDITIONAL INFORMATION
The use of Moodle course sites is governed by the policies at: http://www.oit.umn.edu/moodle/policies/.';
$string['courserequestnotifyemailsubject'] = 'Your Moodle course site request has been received';
$string['courserequestnotifyemail'] = 'PLEASE DO NOT REPLY TO THIS EMAIL

Thank you for submitting a Moodle course site request. Please review the information submitted by you below and contact us via moodle@umn.edu if there are any discrepancies.

SUMMARY OF THE REQUEST:
    Requester Internet ID: {$a->requester_username}
    Requested by: {$a->requester_name}
    Course Full Title: {$a->fullname}
    Course Short Title: {$a->shortname}
    Course Section(s): {$a->sections}
    Class Number(s): {$a->callnumbers}
    Category: {$a->category_string}
{$a->assignedroles}
    Additional comments or instructions for Moodle support: {$a->reason}
    Previous Course URL: {$a->sourcecourseurl}

You will receive an email with the new course site URL once the request has been reviewed and approved by the support team.

Note: By requesting a Moodle course site, the requester and faculty sponsor (when applicable) agree to the policies listed at
http://www.oit.umn.edu/moodle/policies/';

$string['roleuserlist'] = '    {$a->rolename}: {$a->userlist}';
$string['roleuserlistdelim'] = "\n";

// For customization of course request configuration
$string['configcourserequestemailsender'] = 'Send emails from';
$string['configcourserequestemailsender2'] = 'Should be a Moodle username (typically something like "internetid@umn.edu")';
$string['configcourserequestsourcecourseid']  = 'Default source course id';
$string['configcourserequestsourcecourseid2'] = 'The course id of the course that serves as a default template for new courses. This is part of the UMN course request customizations.';
$string['courserequestadditionalroles1'] = 'Additional user roles';
$string['courserequestadditionalroles2'] = 'User can request that other users be added to these roles in the course.';

// Category Course Theme/Template Settings
$string['course:managecoursesettings'] = 'Category course settings';
$string['categorycoursetemplateid'] = 'Course id for category template';
$string['categorycoursetemplateid_help'] = 'The course id of the course that serves as a default template for new (blank) courses in this category. This will not affect any courses that are copied into this category.';
$string['categorycoursetemplateid_error'] = 'The specified course does not exist.';
$string['defaultcategorytheme'] = 'Default category theme';
$string['defaultcategorytheme_help'] = 'When an existing course is copied into this category the course setting "Force theme" will be set to this theme regardless of the setting in the source course. Instructors, designers, etc... can change this setting after the course is copied but initially all courses copied to this category after this setting is set will default to this theme.';
$string['editcategorycoursesettings'] = 'Edit category course settings';
$string['editcategorycoursesettingsthis'] = 'Category course settings';
