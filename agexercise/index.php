<?php

require('../../config.php');

$id = required_param('id', PARAM_INT); // course id

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

add_to_log($course->id, 'agexercise', 'view all', "index.php?id=$course->id", '');

$stragexercise   = get_string('modulename', 'agexercise');
$stragexercises  = get_string('modulenameplural', 'agexercise');
$strsectionname  = get_string('sectionname', 'format_'.$course->format);
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/agexercise/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$stragexercises);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($stragexercises);
echo $OUTPUT->header();

if (!$agexercises = get_all_instances_in_course('agexercise', $course)) {
    notice(get_string('thereareno', 'moodle', $stragexercises), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    $sections = get_all_sections($course->id);
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $table->head  = array ($strsectionname, $strname, $strintro);
    $table->align = array ('center', 'left', 'left');
} else {
    $table->head  = array ($strlastmodified, $strname, $strintro);
    $table->align = array ('left', 'left', 'left');
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($agexercises as $agexercise) {
    $cm = $modinfo->cms[$agexercise->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($agexercise->section !== $currentsection) {
            if ($agexercise->section) {
                $printsection = get_section_name($course, $sections[$agexercise->section]);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $agexercise->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($agexercise->timemodified)."</span>";
    }

    $extra = empty($cm->extra) ? '' : $cm->extra;
    $icon = '';
    if (!empty($cm->icon)) {
        // each agexercise has an icon in 2.0
        $icon = '<img src="'.$OUTPUT->pix_url($cm->icon).'" class="activityicon" alt="'.get_string('modulename', $cm->modname).'" /> ';
    }

    $class = $agexercise->visible ? '' : 'class="dimmed"'; // hidden modules are dimmed
    $table->data[] = array (
        $printsection,
        "<a $class $extra href=\"view.php?id=$cm->id\">".$icon.format_string($agexercise->name)."</a>",
        format_module_intro('agexercise', $agexercise, $cm->id));
}

echo html_writer::table($table);

echo $OUTPUT->footer();
