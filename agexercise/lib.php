<?php

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in AGEXERCISE module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function agexercise_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function agexercise_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * Removes all grades from gradebook
 *
 * @param int $courseid
 * @param string optional type
 */
function agexercise_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $agexercises = $DB->get_records_sql("
            SELECT q.*, cm.idnumber as cmidnumber, q.course as courseid
            FROM {modules} m
            JOIN {course_modules} cm ON m.id = cm.module
            JOIN {agexercise} q ON cm.instance = q.id
            WHERE m.name = 'agexercise' AND cm.course = ?", array($courseid));

    foreach ($agexercises as $agexercise) {
        agexercise_grade_item_update($agexercise, 'reset');
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function agexercise_reset_userdata($data) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/agexercise/locallib.php');

    $componentstr = get_string('modulenameplural', 'agexercise');
    $status = array();

    // Remove all grades from gradebook
    $DB->delete_records_select('agexercise_grades',
            'agexercise IN (SELECT id FROM {agexercise} WHERE course = ?)', array($data->courseid));
    if (empty($data->reset_gradebook_grades)) {
      agexercise_reset_gradebook($data->courseid);
    }
    $status[] = array(
        'component' => $componentstr,
        'item' => get_string('gradesdeleted', 'agexercise'),
        'error' => false);
    return $status;
}

/**
 * List of view style log actions
 * @return array
 */
function agexercise_get_view_actions() {
    return array('view', 'view all', 'report');
}

/**
 * List of update style log actions
 * @return array
 */
function agexercise_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add agexercise instance.
 * @param object $data
 * @param object $mform
 * @return int new agexercise instance id
 */
function agexercise_add_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/agexercise/locallib.php');

    $data->name = get_string('exercisename', 'agexercise').$data->name;
    $displayoptions['printheading'] = (int)!empty($data->printheading);
    $displayoptions['printintro']   = (int)!empty($data->printintro);
    $data->displayoptions = serialize($displayoptions);

    $data->relativepath = agexercise_fix_submitted_path($data->relativepath);
    $data->exid = array_pop(split('/', $data->relativepath));

    $data->timemodified = time();
    $data->id = $DB->insert_record('agexercise', $data);

    // Do the processing required after an add or an update.
    agexercise_after_add_or_update($data);

    return $data->id;
}

/**
 * Update agexercise instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function agexercise_update_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/agexercise/locallib.php');

    $displayoptions['printheading'] = (int)!empty($data->printheading);
    $displayoptions['printintro']   = (int)!empty($data->printintro);
    $data->displayoptions = serialize($displayoptions);

    $data->relativepath = agexercise_fix_submitted_path($data->relativepath);
    $data->exid = array_pop(split('/', $data->relativepath));

    $data->timemodified = time();
    $data->id           = $data->instance;

    $DB->update_record('agexercise', $data);

    // Do the processing required after an add or an update.
    agexercise_after_add_or_update($data);

    return true;
}

/**
 * Delete agexercise instance.
 * @param int $id
 * @return bool true
 */
function agexercise_delete_instance($id) {
    global $DB;

    if (!$agexercise = $DB->get_record('agexercise', array('id'=>$id))) {
        return false;
    }

    // note: all context files are deleted automatically

    agexercise_delete_all_grades($agexercise);

    agexercise_grade_item_delete($agexercise);

    $DB->delete_records('agexercise', array('id'=>$agexercise->id));

    return true;
}

/**
 * Return use outline
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $agexercise
 * @return object|null
 */
function agexercise_user_outline($course, $user, $mod, $agexercise) {
    global $DB, $CFG;
    require_once("$CFG->libdir/gradelib.php");
    $grades = grade_get_grades($course->id, 'mod', 'agexercise', $agexercise->id, $user->id);

    if (empty($grades->items[0]->grades)) {
        return null;
    } else {
        $grade = reset($grades->items[0]->grades);
    }

    $result = new stdClass();
    $result->info = get_string('grade') . ': ' . $grade->str_long_grade;

    //datesubmitted == time created. dategraded == time modified or time overridden
    //if grade was last modified by the user themselves use date graded. Otherwise use
    // date submitted
    // TODO: move this copied & pasted code somewhere in the grades API. See MDL-26704
    if ($grade->usermodified == $user->id || empty($grade->datesubmitted)) {
        $result->time = $grade->dategraded;
    } else {
        $result->time = $grade->datesubmitted;
    }

    return $result;
}

/**
 * Return use complete
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $agexercise
 */
function agexercise_user_complete($course, $user, $mod, $agexercise) {
    global $DB, $CFG, $OUTPUT;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'agexercise', $agexercise->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        echo $OUTPUT->container(get_string('grade').': '.$grade->str_long_grade);
    }

    return true;
}

/**
 * Delete grade item for given agexercise
 *
 * @param object $agexercise object
 * @return object agexercise
 */
function agexercise_grade_item_delete($agexercise) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/agexercise', $agexercise->course, 'mod', 'agexercise', $agexercise->id, 0,
            null, array('deleted' => 1));
}

/**
 * Returns the users with data in one agexercise
 *
 * @todo: deprecated - to be deleted in 2.2
 *
 * @param int $agexerciseid
 * @return bool false
 */
function agexercise_get_participants($agexerciseid) {
    return false;
}

/**
 * This function extends the global navigation for the site.
 * It is important to note that you should not rely on PAGE objects within this
 * body of code as there is no guarantee that during an AJAX request they are
 * available
 *
 * @param navigation_node $navigation The agexercise node within the global navigation
 * @param stdClass $course The course object returned from the DB
 * @param stdClass $module The module object returned from the DB
 * @param stdClass $cm The course module instance returned from the DB
 */
function agexercise_extend_navigation($navigation, $course, $module, $cm) {
    /**
     * This is currently just a stub so that it can be easily expanded upon.
     * When expanding just remove this comment and the line below and then add
     * you content.
     */
    $navigation->nodetype = navigation_node::NODETYPE_LEAF;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function agexercise_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-agexercise-*'=>get_string('page-mod-agexercise-x', 'agexercise'));
    return $module_pagetype;
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as synchronizing grades, rebuilding cache, etc ...
 */
function agexercise_cron($inpage=FALSE) {
    global $DB, $CFG;

    require_once($CFG->dirroot.'/mod/agexercise/locallib.php');
    require_once($CFG->dirroot.'/local/agbase/locallib.php');
    require_once($CFG->libdir.'/gradelib.php');

    if ($inpage) {
      $eol = "<br/>";
    } else {
      $eol = "\n";
    }

    mtrace("Starting agexercise grade check",$eol);
    $moodleid = get_config('local_agbase', 'agservermoodleid');
    $proficient_date_sync_value = rtrim(get_config('agexercise','proficientdatesyncvalue'),".0").".0";
    $rest_request = new local_agbase_rest();
    mtrace("Fetching completed exercises after UTC ".$proficient_date_sync_value,$eol);
    $response_data = $rest_request->call("POST",
                                         "exercise.gradequeue",
                                         array('moodleid' => $moodleid,
                                               'proficient_date_utc'=>$proficient_date_sync_value)
                                         );

    if ($response_data == "") {
        mtrace("error while trying to fetch data",$eol);
    } else {
        $converted_data = json_decode($response_data);
        if (is_array($converted_data)) {
            $new_proficient_date_sync_value = $proficient_date_sync_value;
            foreach ($converted_data as $exercise_item) {
                if ($exercise_item->proficientdate > $new_proficient_date_sync_value) {
                    $new_proficient_date_sync_value = $exercise_item->proficientdate;
                }
                $grade = "100.00";
                mtrace("Processing grades for user ".$exercise_item->userid." on exercise ".$exercise_item->exid,$eol);
                agexercise_enqueue_grade($exercise_item->exid, $exercise_item->userid, $grade);
            }
            set_config('proficientdatesyncvalue', $new_proficient_date_sync_value, 'agexercise');
        }
    }
    if ($queued_grades = $DB->get_records('agexercise_grades_queue')) {
        foreach ($queued_grades as $one_grade) {
            if ($agexercises = $DB->get_records('agexercise', array('exid'=>$one_grade->exid))) {
                foreach ($agexercises as $agexercise) {
                    $is_locked = FALSE;
                    mtrace("found on course ".$agexercise->course." with name ".$agexercise->name,$eol);
                    $gradebook_grades = grade_get_grades($agexercise->course, 'mod', 'agexercise', $agexercise->id);
                    if (!empty($gradebook_grades->items)) {
                        $grade_item = $gradebook_grades->items[0];
                        if ($grade_item->locked) {
                            $is_locked = TRUE;
                        }
                    }
                    if ($is_locked) {
                        mtrace("skipping due to grade locked",$eol);
                    } else {
                        agexercise_save_best_grade($agexercise, $one_grade->userid, $one_grade->grade);
                        $DB->delete_records('agexercise_grades_queue', array('id' => $one_grade->id));
                    }
                }
            }
        }
    }
    mtrace("Finished agexercise grade check",$eol);
    return true;
}

/**
 * This function is called at the end of agexercise_add_instance
 * and agexercise_update_instance, to do the common processing.
 *
 * @param object $agexercise the agexercise object.
 */
function agexercise_after_add_or_update($agexercise) {
    global $DB;

    //update related grade item
    agexercise_grade_item_update($agexercise);
}

/**
 * Create grade item for given agexercise
 *
 * @param object $agexercise object with extra cmidnumber
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function agexercise_grade_item_update($agexercise, $grades = null) {
    global $CFG, $OUTPUT;
    require_once($CFG->dirroot . '/mod/agexercise/locallib.php');
    require_once($CFG->libdir.'/gradelib.php');

    if (array_key_exists('cmidnumber', $agexercise)) { // may not be always present
        $params = array('itemname' => $agexercise->name, 'idnumber' => $agexercise->cmidnumber);
    } else {
        $params = array('itemname' => $agexercise->name);
    }

    if ($agexercise->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $agexercise->grade;
        $params['grademin']  = 0;

    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    $params['hidden'] = 0;

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    $gradebook_grades = grade_get_grades($agexercise->course, 'mod', 'agexercise', $agexercise->id);
    if (!empty($gradebook_grades->items)) {
        $grade_item = $gradebook_grades->items[0];
        if ($grade_item->locked) {
            $confirm_regrade = optional_param('confirm_regrade', 0, PARAM_INT);
            if (!$confirm_regrade) {
                $message = get_string('gradeitemislocked', 'grades');
                $regrade_link = qualified_me() . '&amp;confirm_regrade=1';
                echo $OUTPUT->box_start('generalbox', 'notice');
                echo '<p>'. $message .'</p>';
                echo $OUTPUT->container_start('buttons');
                echo $OUTPUT->single_button($regrade_link, get_string('regradeanyway', 'grades'));
                echo $OUTPUT->container_end();
                echo $OUTPUT->box_end();

                return GRADE_UPDATE_ITEM_LOCKED;
            }
        }
    }

    return grade_update('mod/agexercise', $agexercise->course, 'mod', 'agexercise', $agexercise->id, 0, $grades, $params);
}

/**
 * Return grade for given user or all users.
 *
 * @param int $agexercise exercise data
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none. These are raw grades. They should
 * be processed with agexercise_format_grade for display.
 */
function agexercise_get_user_grades($agexercise, $userid = 0) {
    global $CFG, $DB;

    $params = array($agexercise->id);
    $usertest = '';
    if ($userid) {
        $params[] = $userid;
        $usertest = 'AND u.id = ?';
    }
    return $DB->get_records_sql("
            SELECT
                u.id,
                u.id AS userid,
                qg.grade AS rawgrade,
                qg.timemodified AS dategraded

            FROM {user} u
            JOIN {agexercise_grades} qg ON u.id = qg.userid

            WHERE qg.agexercise = ?
            $usertest", $params);
}

/**
 * Update grades in central gradebook
 *
 * @param object $agexercise the agexercise settings.
 * @param int $userid specific user only, 0 means all users.
 */
function agexercise_update_grades($agexercise, $userid = 0, $nullifnone = true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if ($agexercise->grade == 0) {
        agexercise_grade_item_update($agexercise);

    } else if ($grades = agexercise_get_user_grades($agexercise, $userid)) {
        agexercise_grade_item_update($agexercise, $grades);

    } else if ($userid && $nullifnone) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;
        agexercise_grade_item_update($agexercise, $grade);

    } else {
        agexercise_grade_item_update($agexercise);
    }
}

/**
 * Update all grades in gradebook.
 */
function agexercise_upgrade_grades() {
    global $DB;

    $sql = "SELECT COUNT('x')
              FROM {agexercise} a, {course_modules} cm, {modules} m
             WHERE m.name='agexercise' AND m.id=cm.module AND cm.instance=a.id";
    $count = $DB->count_records_sql($sql);

    $sql = "SELECT a.*, cm.idnumber AS cmidnumber, a.course AS courseid
              FROM {agexercise} a, {course_modules} cm, {modules} m
             WHERE m.name='agexercise' AND m.id=cm.module AND cm.instance=a.id";
    $rs = $DB->get_recordset_sql($sql);
    if ($rs->valid()) {
        $pbar = new progress_bar('agexerciseupgradegrades', 500, true);
        $i=0;
        foreach ($rs as $agexercise) {
            $i++;
            upgrade_set_timeout(60*5); // set up timeout, may also abort execution
            agexercise_update_grades($agexercise, 0, false);
            $pbar->update($i, $count, "Updating AGEXERCISE grades ($i/$count).");
        }
    }
    $rs->close();
}

/**
 * Delete all the grades belonging to a agexercise.
 *
 * @param object $agexercise The agexercise object.
 */
function agexercise_delete_all_grades($agexercise) {
    global $CFG, $DB;
    $DB->delete_records('agexercise_grades', array('agexercise' => $agexercise->id));
}

