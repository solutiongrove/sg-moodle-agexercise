<?php

/**
 * Define all the restore steps that will be used by the restore_agexercise_activity_task
 */

/**
 * Structure step to restore one agexercise activity
 */
class restore_agexercise_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $agexercise = new restore_path_element('agexercise', '/activity/agexercise');
        $paths[] = $agexercise;

        if ($userinfo) {
            $paths[] = new restore_path_element('agexercise_grade', '/activity/agexercise/grades/grade');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_agexercise($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // insert the agexercise record
        $newitemid = $DB->insert_record('agexercise', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_agexercise_grade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->agexercise = $this->get_new_parentid('agexercise');

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->grade = $data->gradeval;

        $DB->insert_record('agexercise_grades', $data);
    }

    protected function after_execute() {
        // Add agexercise related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_agexercise', 'intro', null);
    }
}
