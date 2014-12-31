<?php
$string['pluginname'] = 'Block upload group';
$string['blockname'] = 'Upload group';
$string['blocktitle'] = 'Upload group';
$string['usage'] = 'Assign participants into groups from an uploaded CSV file.';
$string['upload_group:addinstance'] = 'Add a new Upload Group block';

// interface
$string['role_desc'] = 'Assign this role to users that will be enrolled:';
$string['upload_group_data'] = 'Upload group data';
$string['submit_group_data'] = 'Submit group data';
$string['encoding'] = 'Encoding';
$string['delimiter'] = 'Delimiter';
$string['row_preview_num'] = 'Row preview limit';
$string['process_group_data'] = 'Process group data';

// result
$string['result_group_created'] = 'Groups created';
$string['result_member_added'] = 'Group member added';
$string['result_user_not_found'] = 'User not found';
$string['result_group_failed'] = 'Group creation failed';
$string['result_enroll_failed'] = 'User enrolment failed';
$string['result_member_failed'] = 'Group member failed to be added';

$string['upload_help'] = <<< EOB
Preparing the source file:
<ul>
<li>Create a CSV file with two columns: USERNAME and GROUP.</li>
<li>In the USERNAME column put the full username (e.g. jdoexxxx@umn.edu) of anyone you want to add to the group including users who are not enrolled in the course - they will be enrolled as part of the process.</li>
<li>In the GROUP column put the group name, which can be an existing group in the course or a new group which will be created and populated at the same time.</li>
<li>Save the file.</li>
</ul>
<br>
In the form below:
<ul>
<li>Click &#8220;Choose a file&#8221; and upload your CSV file.</li>
<li>Click &#8220;Submit group data&#8221;</li>
</ul>
EOB;

$string['confirm_upload_help'] = <<< EOB
<p><ul>
<li>Verify the table below looks alright.</li>
<li>Select a role to be assigned to users that will be enrolled into the course.</li>
<li>Click &#8220;Process group data&#8221;</li>
</ul></p>
EOB;
