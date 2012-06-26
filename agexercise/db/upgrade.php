<?php

defined('MOODLE_INTERNAL') || die;

function xmldb_agexercise_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012062500) {

        require_once("$CFG->libdir/resourcelib.php");

        $table = new xmldb_table('agexercise');
        $field = new xmldb_field('display', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, RESOURCELIB_DISPLAY_AUTO, 'relativepath');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // url savepoint reached
        upgrade_mod_savepoint(true, 2012062500, 'agexercise');
    }

    return true;
}
