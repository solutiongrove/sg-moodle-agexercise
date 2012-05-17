<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configcheckbox('agexercise/requiremodintro',
        get_string('requiremodintro', 'admin'), get_string('configrequiremodintro', 'admin'), 1));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('agexercisemodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox_with_advanced('agexercise/printheading',
        get_string('printheading', 'agexercise'), get_string('printheadingexplain', 'agexercise'),
        array('value'=>1, 'adv'=>false)));
    $settings->add(new admin_setting_configcheckbox_with_advanced('agexercise/printintro',
        get_string('printintro', 'agexercise'), get_string('printintroexplain', 'agexercise'),
        array('value'=>1, 'adv'=>false)));

    //--- cron settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('agexercisecronsettings', get_string('cronsettings', 'agexercise'), get_string('cronsettings_intro', 'agexercise')));
    $settings->add(new admin_setting_configtext('agexercise/proficientdatesyncvalue',
        get_string('proficientdatesyncvalue', 'agexercise'), get_string('proficientdatesyncvalue_help', 'agexercise'), 0, PARAM_INT));

}
