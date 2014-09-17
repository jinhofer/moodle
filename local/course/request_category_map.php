<?php

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/course/request_category_map_form.php');

admin_externalpage_setup('local_course_request_category_map');

// return back to self
$returnurl = "$CFG->wwwroot/local/course/request_category_map.php";


// First ensure that all categories have a record in course_request_category_map.
$sql =<<<SQL
select id from {course_categories} where id not in (select categoryid from {course_request_category_map});
SQL;
$missingcategories = $DB->get_fieldset_sql($sql);
foreach ($missingcategories as $categoryid) {
    $DB->insert_record('course_request_category_map',
                        array('categoryid' => $categoryid));
}

// In the context of this page, "display" means to display in the course request
// form category dropdown.  A category that displays is not necessarily requestable.

/**
 * Returns the contents of course_request_category_map keyed on
 * categoryid.
 */
$current_settings = $DB->get_records('course_request_category_map',
                                     null,
                                     '',
                                     'categoryid, display, allowrequest, id');

$categorymapform = new local_course_request_category_map_form(null, $current_settings);

if ($categorymapform->is_cancelled()){
    redirect($returnurl);
}

if($data = $categorymapform->get_data()) {

    // Now process form data.
    foreach ($data->allowrequests as $categoryid => $allowrequest) {

        if (array_key_exists($categoryid, $current_settings)) {

            if ($allowrequest != $current_settings[$categoryid]->allowrequest) {
                $DB->update_record('course_request_category_map',
                                   array('id' => $current_settings[$categoryid]->id,
                                         'display' => $allowrequest ? 1 : $current_settings[$categoryid]->display,
                                         'allowrequest' => $allowrequest));
            }
        }
    }

    // For now, at least, we want second tier categories to be requestable if any of their
    // children are requestable.  This consistent with current business requirements and
    // also simplifies course request page error handling.
    $sql =<<<SQL
select distinct m.id
from {course_request_category_map} m
    left join {course_categories} cc on cc.parent=m.categoryid
    left join {course_request_category_map} m2 on m2.categoryid=cc.id
where m.allowrequest=0 and m2.allowrequest=1 and cc.depth=3
SQL;
    $makerequestable = $DB->get_fieldset_sql($sql);
    if (!empty($makerequestable)) {
        $dorequestable = implode(',', $makerequestable);
        $sql = "update {course_request_category_map} set allowrequest=1 where id in ($dorequestable)";
        $DB->execute($sql);
    }

    // Ensure that display is set to on for parent categories of displayable categories.
    // Will need to run multiple times or otherwise modify if we display depth > 2.
    // Get category map id that should have m.display set to 1.
    $sql =<<<SQL
select distinct m.id
from {course_request_category_map} m
    left join {course_categories} cc on cc.parent=m.categoryid
    left join {course_request_category_map} m2 on m2.categoryid=cc.id
where m.display=0 and (m2.display=1 or m2.allowrequest=1 or m.allowrequest=1)
SQL;

    $displayables = $DB->get_fieldset_sql($sql);
    if (!empty($displayables)) {
        // Run the update to set the display flags.

        $dodisplay = implode(',', $displayables);

        $sql = "update {course_request_category_map} set display=1 where id in ($dodisplay)";
        $DB->execute($sql);
    }

    // Clear display flags that should not be set.

    // Find those that set for display that are not set to allow request
    // and do not have a child categories that are set for display.
    $nondisplayablesql =<<<SQL
select m.id
from {course_request_category_map} m
    left join {course_categories} cc on cc.parent=m.categoryid
    left join {course_request_category_map} m2 on m2.categoryid=cc.id
where m.display=1 and m.allowrequest=0
group by m.id
having max(m2.display) < 1 or max(m2.display) is null
SQL;

    while (true) {
        $nondisplayables = $DB->get_fieldset_sql($nondisplayablesql);
        if (empty($nondisplayables)) {
            break;
        } else {
            // Run the update to clear the display flags.
            $donotdisplay = implode(',', $nondisplayables);
            $sql = "update {course_request_category_map} set display=0 where id in ($donotdisplay)";
            $DB->execute($sql);
        }
    }

    redirect($returnurl);
}

echo $OUTPUT->header();

$categorymapform->display();

echo $OUTPUT->footer();


