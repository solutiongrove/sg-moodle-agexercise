<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/agexercise/backup/moodle2/backup_agexercise_stepslib.php'); // Because it exists (must)

/**
 * AGEXERCISE backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_agexercise_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step(new backup_agexercise_activity_structure_step('agexercise_structure', 'agexercise.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of agexercises
        $search="/(".$base."\/mod\/agexercise\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@AGEXERCISEINDEX*$2@$', $content);

        // Link to agexercise view by moduleid
        $search="/(".$base."\/mod\/agexercise\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@AGEXERCISEVIEWBYID*$2@$', $content);

        return $content;
    }
}
