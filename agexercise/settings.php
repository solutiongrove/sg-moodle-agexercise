<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_AUTO,
                                                           RESOURCELIB_DISPLAY_FRAME,
                                                           RESOURCELIB_DISPLAY_OPEN,
                                                           RESOURCELIB_DISPLAY_NEW,
                                                           RESOURCELIB_DISPLAY_POPUP,
                                                          ));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_AUTO,
                                   RESOURCELIB_DISPLAY_OPEN,
                                   RESOURCELIB_DISPLAY_NEW,
                                  );

     //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('agexercise/framesize',
        get_string('framesize', 'agexercise'), get_string('configframesize', 'agexercise'), 130, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('agexercise/requiremodintro',
        get_string('requiremodintro', 'admin'), get_string('configrequiremodintro', 'admin'), 1));
    $settings->add(new admin_setting_configmultiselect('agexercise/displayoptions',
        get_string('displayoptions', 'agexercise'), get_string('configdisplayoptions', 'agexercise'),
        $defaultdisplayoptions, $displayoptions));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('agexercisemodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox_with_advanced('agexercise/printheading',
        get_string('printheading', 'agexercise'), get_string('printheadingexplain', 'agexercise'),
        array('value'=>1, 'adv'=>false)));
    $settings->add(new admin_setting_configcheckbox_with_advanced('agexercise/printintro',
        get_string('printintro', 'agexercise'), get_string('printintroexplain', 'agexercise'),
        array('value'=>1, 'adv'=>false)));
    $settings->add(new admin_setting_configselect_with_advanced('agexercise/display',
        get_string('displayselect', 'agexercise'), get_string('displayselectexplain', 'agexercise'),
        array('value'=>RESOURCELIB_DISPLAY_AUTO, 'adv'=>false), $displayoptions));
    $settings->add(new admin_setting_configtext_with_advanced('agexercise/popupwidth',
        get_string('popupwidth', 'agexercise'), get_string('popupwidthexplain', 'agexercise'),
        array('value'=>620, 'adv'=>true), PARAM_INT, 7));
    $settings->add(new admin_setting_configtext_with_advanced('agexercise/popupheight',
        get_string('popupheight', 'agexercise'), get_string('popupheightexplain', 'agexercise'),
        array('value'=>450, 'adv'=>true), PARAM_INT, 7));

    //--- cron settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('agexercisecronsettings', get_string('cronsettings', 'agexercise'), get_string('cronsettings_intro', 'agexercise')));
    $settings->add(new admin_setting_configtext('agexercise/proficientdatesyncvalue',
        get_string('proficientdatesyncvalue', 'agexercise'), get_string('proficientdatesyncvalue_help', 'agexercise'), 0, PARAM_INT));

}
