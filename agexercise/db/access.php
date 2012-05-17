<?php

defined('MOODLE_INTERNAL') || die;

$capabilities = array(
    'mod/agexercise:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'guest' => CAP_ALLOW,
            'user' => CAP_ALLOW,
        )
    ),

);

