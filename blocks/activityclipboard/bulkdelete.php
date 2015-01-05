<?php

require_once('../../config.php');

require_once 'activityclipboard_table.php';
require_once 'lib.php';
require_once("$CFG->libdir/formslib.php");

class activityclipboard_bulkdelete_form extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $items = $this->_customdata['items'];

        $mform->addElement('hidden', 'course', $this->_customdata['courseid']);

        $mform->addElement('html', '<div id="bulkdelete_checkboxes">');

        foreach ($items as $item) {
            $textwithtree = $item->tree ? $item->tree.'/ '.$item->text : $item->text;
            $mform->addElement('advcheckbox',
                               'delete['.$item->id.']',
                               null,
                               '&nbsp;'.activityclipboard_get_icon($item->name,
                                                          $item->icon)
                                     .'&nbsp;'.htmlspecialchars($textwithtree),
                               array('group'=>1));
        }

        $mform->addElement('html', '</div> <!-- bulkdelete_checkboxes -->');

        $mform->addElement('button', 'selectallnone', get_string('selectallornone', 'form'));

        $this->add_action_buttons(true, get_string('deleteselected', 'block_activityclipboard'));
    }
}

$courseid = required_param('course', PARAM_INT);

require_login($courseid);

$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_url('/blocks/activityclipboard/bulkdelete.php');
$PAGE->set_pagelayout('admin');

$returnurl = $CFG->wwwroot.'/course/view.php?id='.$courseid;

$items = activityclipboard_table::get_user_items();

$bulkdeleteform = new activityclipboard_bulkdelete_form(null,
                                                        array('items' => $items,
                                                              'courseid' => $courseid));
if ($bulkdeleteform->is_cancelled()) {
    redirect($returnurl);
}

if ($formdata = $bulkdeleteform->get_data()) {
    // Because we are using advcheckbox, the deletes are an array
    // representing all the checkboxes. The keys are the ids from
    // the activityclipboard table, and the values are 0 or 1 depending
    // on whether the checkbox is checked.
    $deletes = array_filter($formdata->delete);

    // Pick out from the user's list of clipboard items those
    // that the user selected for deletion.
    $itemstodelete = array_intersect_key($items, $deletes);

    activityclipboard_delete_items($itemstodelete);
    redirect($returnurl);
}

// Page rendering follows...

$PAGE->requires->string_for_js('confirm_delete_selected', 'block_activityclipboard');
$PAGE->requires->yui_module('moodle-block_activityclipboard-bulkdelete',
                            'M.blocks_activityclipboard.init_activityclipboardBulkDelete');

$strtitle   = get_string('bulkdelete'       , 'block_activityclipboard');
$strheading = get_string('bulkdeleteheading', 'block_activityclipboard');

$PAGE->set_heading($strtitle);
$PAGE->set_title($strtitle);
$PAGE->navbar->add($strtitle);

echo $OUTPUT->header();
echo $OUTPUT->heading($strheading, 2);
$bulkdeleteform->display();
echo $OUTPUT->footer();

