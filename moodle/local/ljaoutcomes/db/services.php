<?php
defined('MOODLE_INTERNAL') || die();
$functions = array(
    'local_ljaoutcomes_get_course_outcomes' => array(
        'classname' => 'local_ljaoutcomes_external',
        'methodname' => 'get_course_outcomes',
        'classpath' => 'local/ljaoutcomes/externallib.php',
        'description' => 'Read course-linked outcome definitions and achievement scale labels. No student results.',
        'type' => 'read',
        'capabilities' => 'moodle/course:view,gradereport/user:view'
    )
);
