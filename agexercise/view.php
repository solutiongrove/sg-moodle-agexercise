<?php

require('../../config.php');
require_once("$CFG->dirroot/mod/agexercise/locallib.php");
require_once($CFG->libdir . '/completionlib.php');

$id       = optional_param('id', 0, PARAM_INT);        // Course module ID
$redirect = optional_param('redirect', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('agexercise', $id, 0, false, MUST_EXIST);
$agexercise = $DB->get_record('agexercise', array('id'=>$cm->instance), '*', MUST_EXIST);

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('mod/agexercise:view', $context);

add_to_log($course->id, 'agexercise', 'view', 'view.php?id='.$cm->id, $agexercise->id, $cm->id);

// Update 'viewed' state if required by completion system
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/agexercise/view.php', array('id' => $cm->id));

$displaytype = $agexercise->display;
if ($displaytype == RESOURCELIB_DISPLAY_OPEN) {
    // For 'open' links, we always redirect to the content - except if the user
    // just chose 'save and display' from the form then that would be confusing
    if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'modedit.php') === false) {
        $redirect = true;
    }
}

if ($redirect) {
    // coming from course page or url index page,
    // the redirection is needed for completion tracking and logging
    $fullurl = agexercise_get_full_url($agexercise, $cm, $course);
    redirect(str_replace('&amp;', '&', $fullurl));
}

switch ($displaytype) {
    case RESOURCELIB_DISPLAY_FRAME:
        agexercise_display_frame($agexercise, $cm, $course);
        break;
    default:
        agexercise_print_workaround($agexercise, $cm, $course);
        break;
}
