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
 * The main group management user interface.
 *
 * @copyright 2006 The Open University, N.D.Freear AT open.ac.uk, J.White AT open.ac.uk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   core_group
 */
require_once('../config.php');
require_once('lib.php');
require_once($CFG->dirroot . '/local/group/peoplesoft_autogroup.php');

$courseid = required_param('id', PARAM_INT);
$groupid  = optional_param('group', false, PARAM_INT);
$userid   = optional_param('user', false, PARAM_INT);
$action   = groups_param_action();
// Support either single group= parameter, or array groups[]
if ($groupid) {
    $groupids = array($groupid);
} else {
    $groupids = optional_param_array('groups', array(), PARAM_INT);
}
$singlegroup = (count($groupids) == 1);

$returnurl = $CFG->wwwroot.'/group/index.php?id='.$courseid;

// Get the course information so we can print the header and
// check the course id is valid

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);

$url = new moodle_url('/group/index.php', array('id'=>$courseid));
if ($userid) {
    $url->param('user', $userid);
}
if ($groupid) {
    $url->param('group', $groupid);
}
$PAGE->set_url($url);

// Make sure that the user has permissions to manage groups.
require_login($course);

$context = context_course::instance($course->id);
require_capability('moodle/course:managegroups', $context);

$PAGE->requires->js('/group/clientlib.js');

// Check for multiple/no group errors
if (!$singlegroup) {
    switch($action) {
        case 'ajax_getmembersingroup':
        case 'showgroupsettingsform':
        case 'showaddmembersform':
        case 'updatemembers':
            print_error('errorselectone', 'group', $returnurl);
    }
}

switch ($action) {
    case false: //OK, display form.
        break;

    case 'ajax_getmembersingroup':
        $roles = array();
        if ($groupmemberroles = groups_get_members_by_role($groupids[0], $courseid, 'u.id, ' . get_all_user_name_fields(true, 'u'))) {
            foreach($groupmemberroles as $roleid=>$roledata) {
                $shortroledata = new stdClass();
                $shortroledata->name = $roledata->name;
                $shortroledata->users = array();
                foreach($roledata->users as $member) {
                    $shortmember = new stdClass();
                    $shortmember->id = $member->id;
                    $shortmember->name = fullname($member, true);
                    $shortroledata->users[] = $shortmember;
                }
                $roles[] = $shortroledata;
            }
        }
        echo json_encode($roles);
        die;  // Client side JavaScript takes it from here.

    case 'deletegroup':
        if (count($groupids) == 0) {
            print_error('errorselectsome','group',$returnurl);
        }
        $groupidlist = implode(',', $groupids);
        redirect(new moodle_url('/group/delete.php', array('courseid'=>$courseid, 'groups'=>$groupidlist)));
        break;

    case 'showcreateorphangroupform':
        redirect(new moodle_url('/group/group.php', array('courseid'=>$courseid)));
        break;

    case 'showautocreategroupsform':
        redirect(new moodle_url('/group/autogroup.php', array('courseid'=>$courseid)));
        break;

    case 'showimportgroups':
        redirect(new moodle_url('/group/import.php', array('id'=>$courseid)));
        break;

    case 'showgroupsettingsform':
        redirect(new moodle_url('/group/group.php', array('courseid'=>$courseid, 'id'=>$groupids[0])));
        break;

    case 'updategroups': //Currently reloading.
        break;

    case 'removemembers':
        break;

    case 'showaddmembersform':
        redirect(new moodle_url('/group/members.php', array('group'=>$groupids[0])));
        break;

    case 'updatemembers': //Currently reloading.
        break;

        // SDLC-81781 20110610 hoang027 >>>

        case 'run_ppsft_autogroup':
            $auto_grouper = new peoplesoft_autogroup();
            $result = $auto_grouper->run($courseid);

            if (count($result['errors']) > 0) {
                print_error('error_create_ppsft', 'group', $returnurl, null, print_r($result['errors'], true));
            }
            break;

        case 'update_ppsftautogroup_setting':
            $auto_update = '0';
            $terminname  = '0';

            if (isset($_POST['ppsft_autogroup_autoupdate']) && $_POST['ppsft_autogroup_autoupdate'] == '1') {
                $auto_update = '1';
            }

            if (isset($_POST['ppsft_autogroup_terminname']) && $_POST['ppsft_autogroup_terminname'] == '1') {
                $terminname  = '1';
            }

            $umn_auto = enrol_get_plugin('umnauto');
            $umn_auto->set_autogroup_settings($courseid, array(
                    'auto_update'        => $auto_update,
                    'name_include_term'  => $terminname));
            break;

            // <<< SDLC-81781

    default: //ERROR.
        print_error('unknowaction', '', $returnurl);
        break;
}

// Print the page and form
$strgroups = get_string('groups');
$strparticipants = get_string('participants');

/// Print header
$PAGE->set_title($strgroups);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');
echo $OUTPUT->header();

// Add tabs
$currenttab = 'groups';
require('tabs.php');

$disabled = 'disabled="disabled"';

// Some buttons are enabled if single group selected.
$showaddmembersform_disabled = $singlegroup ? '' : $disabled;
$showeditgroupsettingsform_disabled = $singlegroup ? '' : $disabled;
$deletegroup_disabled = count($groupids) > 0 ? '' : $disabled;

echo $OUTPUT->heading(format_string($course->shortname, true, array('context' => $context)) .' '.$strgroups, 3);

//SDLC-81781 20110610 hoang027 >>>
// perform the calculation and checking here so that error can be displayed at top

// ppsft-autogroup function is available for this course if there is associated class
$query = "SELECT COUNT(*)
          FROM   {enrol} enrol
                   INNER JOIN {enrol_umnauto_classes} enrol_umnauto_classes
                       ON enrol.id = enrol_umnauto_classes.enrolid
          WHERE  enrol.courseid = :course_id";

$class_count = $DB->count_records_sql($query, array('course_id' => $courseid));

if ($class_count > 0) {
    // retrieve the auto-update setting if there is associate PeopleSoft class
    $autogroup_settings = $DB->get_record('enrol_umnauto_course', array('courseid' => $courseid));

    if ($autogroup_settings === false) {
        print_error('error_no_autogroup_setting', 'group');
    }
}

// <<< SDLC-81781

echo '<form id="groupeditform" action="index.php" method="post">'."\n";
echo '<div>'."\n";
echo '<input type="hidden" name="id" value="' . $courseid . '" />'."\n";

// SDLC-81781 20110610 hoang027 >>>
echo '<div id="groupcreateguide">', get_string('createguide', 'group'), '</div>';
// <<< SDLC-81781

echo '<table cellpadding="6" class="generaltable generalbox groupmanagementtable boxaligncenter" summary="">'."\n";
echo '<tr>'."\n";


echo "<td>\n";
echo '<p><label for="groups"><span id="groupslabel">'.get_string('groups').':</span><span id="thegrouping">&nbsp;</span></label></p>'."\n";

$onchange = 'M.core_group.membersCombo.refreshMembers();';

echo '<select name="groups[]" multiple="multiple" id="groups" size="15" class="select" onchange="'.$onchange.'"'."\n";
echo ' onclick="window.status=this.selectedIndex==-1 ? \'\' : this.options[this.selectedIndex].title;" onmouseout="window.status=\'\';">'."\n";

$groups = groups_get_all_groups($courseid);
$selectedname = '&nbsp;';
$preventgroupremoval = array();

if ($groups) {
    // Print out the HTML
    foreach ($groups as $group) {
        $select = '';
        $usercount = $DB->count_records('groups_members', array('groupid'=>$group->id));
        $groupname = format_string($group->name).' ('.$usercount.')';
        if (in_array($group->id,$groupids)) {
            $select = ' selected="selected"';
            if ($singlegroup) {
                // Only keep selected name if there is one group selected
                $selectedname = $groupname;
            }
        }
        if (!empty($group->idnumber) && !has_capability('moodle/course:changeidnumber', $context)) {
            $preventgroupremoval[$group->id] = true;
        }

        echo "<option value=\"{$group->id}\"$select title=\"$groupname\">$groupname</option>\n";
    }
} else {
    // Print an empty option to avoid the XHTML error of having an empty select element
    echo '<option>&nbsp;</option>';
}

echo '</select>'."\n";
echo '<p><input type="submit" name="act_updatemembers" id="updatemembers" value="'
        . get_string('showmembersforgroup', 'group') . '" /></p>'."\n";
echo '<p><input type="submit" '. $showeditgroupsettingsform_disabled . ' name="act_showgroupsettingsform" id="showeditgroupsettingsform" value="'
        . get_string('editgroupsettings', 'group') . '" /></p>'."\n";
echo '<p><input type="submit" '. $deletegroup_disabled . ' name="act_deletegroup" id="deletegroup" value="'
        . get_string('deleteselectedgroup', 'group') . '" /></p>'."\n";

echo '<p><input type="submit" name="act_showcreateorphangroupform" id="showcreateorphangroupform" value="'
        . get_string('creategroup', 'group') . '" /></p>'."\n";

echo '<p><input type="submit" name="act_showautocreategroupsform" id="showautocreategroupsform" value="'
        . get_string('autocreategroups', 'group') . '" /></p>'."\n";

echo '<p><input type="submit" name="act_showimportgroups" id="showimportgroups" value="'
        . get_string('importgroups', 'core_group') . '" /></p>'."\n";

echo '</td>'."\n";
echo '<td>'."\n";

echo '<p><label for="members"><span id="memberslabel">'.
    get_string('membersofselectedgroup', 'group').
    ' </span><span id="thegroup">'.$selectedname.'</span></label></p>'."\n";
//NOTE: the SELECT was, multiple="multiple" name="user[]" - not used and breaks onclick.
echo '<select name="user" id="members" size="15" class="select"'."\n";
echo ' onclick="window.status=this.options[this.selectedIndex].title;" onmouseout="window.status=\'\';">'."\n";

$member_names = array();

$atleastonemember = false;
if ($singlegroup) {
    if ($groupmemberroles = groups_get_members_by_role($groupids[0], $courseid, 'u.id, ' . get_all_user_name_fields(true, 'u'))) {
        foreach($groupmemberroles as $roleid=>$roledata) {
            echo '<optgroup label="'.s($roledata->name).'">';
            foreach($roledata->users as $member) {
                echo '<option value="'.$member->id.'">'.fullname($member, true).'</option>';
                $atleastonemember = true;
            }
            echo '</optgroup>';
        }
    }
}

if (!$atleastonemember) {
    // Print an empty option to avoid the XHTML error of having an empty select element
    echo '<option>&nbsp;</option>';
}

echo '</select>'."\n";

echo '<p><input type="submit" ' . $showaddmembersform_disabled . ' name="act_showaddmembersform" '
        . 'id="showaddmembersform" value="' . get_string('adduserstogroup', 'group'). '" /></p>'."\n";
echo '</td>'."\n";
echo '</tr>'."\n";
echo '</table>'."\n";

//<input type="hidden" name="rand" value="om" />
echo '</div>'."\n";
echo '</form>'."\n";

// 20121017 hoang027 >>>
// display the PeopleSoft based auto-grouping functionality
if ($class_count > 0) {
    echo '<form id="ppsftgroupeditform" action="index.php?id=', $courseid, '" method="post">', "\n";
    echo '<table class="generaltable generalbox groupmanagementtable boxaligncenter" summary="">', "\n";

    if ($autogroup_settings !== false) {
        echo '<tr><td><input type="checkbox" name="ppsft_autogroup_terminname" id="rb_ppsft_autogroup_terminname" value="1" ',
        $autogroup_settings->autogroup_option == '1' ? 'checked' : '', ' /></td>';
        echo '<td>', get_string('ppsft_term_in_name', 'group'), '</td></tr>';

        echo '<tr><td><input type="checkbox" name="ppsft_autogroup_autoupdate" id="rb_ppsft_autogroup_autoupdate" value="1" ',
        $autogroup_settings->auto_group == '1' ? 'checked' : '', ' /></td>';
        echo '<td>', get_string('ppsft_auto_update', 'group'), '</td></tr>';

        echo '<tr><td colspan="2"><input type="submit" name="act_update_ppsftautogroup_setting" ',
        'id="update_ppsftautogroup_settingform" value="',
        get_string('save_autogroup_setting_btn', 'group'), '" /></td></tr>';
    }

    echo '<tr><td colspan="2">';
    echo '<br/><input type="submit" name="act_run_ppsft_autogroup" id="run_ppsft_autogroupform" value="',
    get_string('ppsft_autogroup_btn', 'group'), '" /></td></tr>';

    echo '</table></form>';
}

// <<< SDLC-81781

$PAGE->requires->js_init_call('M.core_group.init_index', array($CFG->wwwroot, $courseid));
$PAGE->requires->js_init_call('M.core_group.groupslist', array($preventgroupremoval));

echo $OUTPUT->footer();

/**
 * Returns the first button action with the given prefix, taken from
 * POST or GET, otherwise returns false.
 * @see /lib/moodlelib.php function optional_param().
 * @param string $prefix 'act_' as in 'action'.
 * @return string The action without the prefix, or false if no action found.
 */
function groups_param_action($prefix = 'act_') {
    $action = false;
//($_SERVER['QUERY_STRING'] && preg_match("/$prefix(.+?)=(.+)/", $_SERVER['QUERY_STRING'], $matches)) { //b_(.*?)[&;]{0,1}/

    if ($_POST) {
        $form_vars = $_POST;
    }
    elseif ($_GET) {
        $form_vars = $_GET;
    }
    if ($form_vars) {
        foreach ($form_vars as $key => $value) {
            if (preg_match("/$prefix(.+)/", $key, $matches)) {
                $action = $matches[1];
                break;
            }
        }
    }
    if ($action && !preg_match('/^\w+$/', $action)) {
        $action = false;
        print_error('unknowaction');
    }
    ///if (debugging()) echo 'Debug: '.$action;
    return $action;
}
