<?php

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/agexercise/lib.php");

/**
 * Fix common URL problems that we want teachers to see fixed
 * the next time they edit the resource.
 *
 * This function does not include any XSS protection.
 *
 * @param string $url
 * @return string
 */
function agexercise_fix_submitted_path($url) {
    // note: empty and invalid urls are prevented in form validation
    $url_temp = trim($url);
    $url_parts = parse_url($url_temp);
    $url = '/'.ltrim($url_parts['path'],'/');

    // remove encoded entities - we want the raw URI here
    $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

    return $url;
}

/**
 * Return full url with all extra parameters
 *
 * This function does not include any XSS protection.
 *
 * @param string $agexercise
 * @param object $cm
 * @param object $course
 * @param object $config
 * @return string url with & encoded as &amp;
 */
function agexercise_get_full_url($agexercise, $cm, $course, $config=null) {

    $ag_server_url = get_config('local_agbase', 'agserverurl');

    // make sure there are no encoded entities, it is ok to do this twice
    $relativepath = trim($agexercise->relativepath);
    if (empty($relativepath)) {
        $relativepath = "/exercise/".$agexercise->exid;
    }
    $fullurl = rtrim($ag_server_url, '/') . html_entity_decode($relativepath, ENT_QUOTES, 'UTF-8');

    $allowed = "a-zA-Z0-9".preg_quote(';/?:@=&$_.+!*(),-#%', '/');
    $fullurl = preg_replace_callback("/[^$allowed]/", 'agexercise_filter_callback', $fullurl);

    // encode all & to &amp; entity
    $fullurl = str_replace('&', '&amp;', $fullurl);

    return $fullurl;
}

/**
 * Unicode encoding helper callback
 * @internal
 * @param array $matches
 * @return string
 */
function agexercise_filter_callback($matches) {
    return rawurlencode($matches[0]);
}

/**
 * Print url header.
 * @param object $url
 * @param object $cm
 * @param object $course
 * @return void
 */
function agexercise_print_header($agexercise, $cm, $course) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname.': '.$agexercise->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($agexercise);
    echo $OUTPUT->header();
}

/**
 * Print agexercise heading.
 * @param object $agexercise
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function agexercise_print_heading($agexercise, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($agexercise->displayoptions) ? array() : unserialize($agexercise->displayoptions);

    if ($ignoresettings or !empty($options['printheading'])) {
        echo $OUTPUT->heading(format_string($agexercise->name), 2, 'main', 'agexerciseheading');
    }
}

/**
 * Print agexercise introduction.
 * @param object $agexercise
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function agexercise_print_intro($agexercise, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($agexercise->displayoptions) ? array() : unserialize($agexercise->displayoptions);

    if ($ignoresettings or !empty($options['printintro'])) {
        if (trim(strip_tags($agexercise->intro))) {
            echo $OUTPUT->box_start('mod_introbox', 'agexerciseintro');
            echo format_module_intro('agexercise', $agexercise, $cm->id);
            echo $OUTPUT->box_end();
        }
    }
}


/**
 * Display agexercise frames.
 * @param object $agexercise
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function agexercise_display_frame($agexercise, $cm, $course) {
    global $PAGE, $OUTPUT, $CFG;
    $frame = optional_param('frameset', 'main', PARAM_ALPHA);

    if ($frame === 'top') {
        $PAGE->set_pagelayout('frametop');
        agexercise_print_header($agexercise, $cm, $course);
        agexercise_print_heading($agexercise, $cm, $course);
        agexercise_print_intro($agexercise, $cm, $course);
        echo $OUTPUT->footer();
        die;
    } else {
        $config = get_config('agexercise');
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        $exteurl = agexercise_get_full_url($agexercise, $cm, $course, $config);
        $navurl = "$CFG->wwwroot/mod/agexercise/view.php?id=$cm->id&amp;frameset=top";
        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
        $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
        $title = strip_tags($courseshortname.': '.format_string($agexercise->name));
        $framesize = empty($config->framesize) ? 130 : $config->framesize;
        $modulename = s(get_string('modulename','agexercise'));
        $dir = get_string('thisdirection', 'langconfig');

        $extframe = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html dir="$dir">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>$title</title>
  </head>
  <frameset rows="$framesize,*">
    <frame src="$navurl" title="$modulename"/>
    <frame src="$exteurl" title="$modulename"/>
  </frameset>
</html>
EOF;

        @header('Content-Type: text/html; charset=utf-8');
        echo $extframe;
        die;
    }
}

/**
 * Print agexercise info and link.
 * @param object $agexercise
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function agexercise_print_workaround($agexercise, $cm, $course) {
    global $OUTPUT;

    agexercise_print_header($agexercise, $cm, $course);
    agexercise_print_heading($agexercise, $cm, $course);
    agexercise_print_intro($agexercise, $cm, $course);

    $fullurl = agexercise_get_full_url($agexercise, $cm, $course);

    $display = $agexercise->display;
    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $jsfullurl = addslashes_js($fullurl);
        $options = empty($agexercise->displayoptions) ? array() : unserialize($agexercise->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $extra = "onclick=\"window.open('$jsfullurl', '', '$wh'); return false;\"";
    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $extra = "onclick=\"this.target='_blank';\"";
    } else {
        $extra = '';
    }

    echo '<div class="agurlworkaround">';
    print_string('clicktoopen', 'agexercise', "<a href=\"$fullurl\" $extra>".get_string('exercise', 'agexercise')."</a>");
    echo '</div>';

    echo $OUTPUT->footer();
    die;
}

/**
 * Save the overall grade for a user at a agexercise in the agexercise_grades table
 *
 * @param object $agexercise The agexercise for which the best grade is to be calculated and then saved.
 * @param int $userid The userid to calculate the grade for.
 * @param int $grade The raw grade
 * @return bool Indicates success or failure.
 */
function agexercise_save_best_grade($agexercise, $userid, $grade = "100.00") {
    global $DB;
    global $OUTPUT;

    // Calculate the best grade
    # $bestgrade = agexercise_calculate_best_grade($agexercise, $attempts);
    # $bestgrade = agexercise_rescale_grade($bestgrade, $agexercise, false);
    $bestgrade = $grade;

    // Save the best grade in the database
    if (is_null($bestgrade)) {
        $DB->delete_records('agexercise_grades', array('agexercise' => $agexercise->id, 'userid' => $userid));

    } else if ($grade = $DB->get_record('agexercise_grades',
            array('agexercise' => $agexercise->id, 'userid' => $userid))) {
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        $DB->update_record('agexercise_grades', $grade);

    } else {
        $grade->agexercise = $agexercise->id;
        $grade->userid = $userid;
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        $DB->insert_record('agexercise_grades', $grade);
    }

    agexercise_update_grades($agexercise, $userid);
}


/**
 * Enqueue the overall grade for a user at a agexercise
 *
 * @param object $exid The id of the exercise for which the best grade is to be calculated and then saved.
 * @param int $userid The userid to calculate the grade for.
 * @param int $grade The raw grade
 * @return bool Indicates success or failure.
 */
function agexercise_enqueue_grade($exid, $userid, $grade = "100.00") {
    global $DB;
    global $OUTPUT;

    $bestgrade = $grade;

    // Save the best grade in the database
    if (is_null($bestgrade)) {
        $DB->delete_records('agexercise_grades_queue', array('exid' => $exid, 'userid' => $userid));

    } else if ($grade = $DB->get_record('agexercise_grades_queue',
            array('exid' => $exid, 'userid' => $userid))) {
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        $DB->update_record('agexercise_grades_queue', $grade);
    } else {
        $grade->exid = $exid;
        $grade->userid = $userid;
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        $DB->insert_record('agexercise_grades_queue', $grade);
    }
}
