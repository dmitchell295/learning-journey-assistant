<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');
class local_ljaoutcomes_external extends external_api {
    public static function get_course_outcomes_parameters() {
        return new external_function_parameters(array(
            'courseid' => new external_value(PARAM_INT, 'Course ID')
        ));
    }
    public static function get_course_outcomes($courseid) {
        global $DB;
        $params = self::validate_parameters(self::get_course_outcomes_parameters(), array('courseid' => $courseid));
        $course = get_course($params['courseid']);
        $context = context_course::instance($course->id);
        self::validate_context($context);
        require_capability('moodle/course:view', $context);
        require_capability('gradereport/user:view', $context);
        $sql = "SELECT o.id, o.shortname, o.fullname, o.description, o.scaleid, s.scale
                  FROM {grade_outcomes_courses} oc
                  JOIN {grade_outcomes} o ON o.id = oc.outcomeid
             LEFT JOIN {scale} s ON s.id = o.scaleid
                 WHERE oc.courseid = :courseid ORDER BY o.id";
        $records = $DB->get_records_sql($sql, array('courseid' => $course->id));
        $outcomes = array();
        foreach ($records as $r) {
            $labels = array();
            if ($r->scale !== null && $r->scale !== '') {
                foreach (explode(',', $r->scale) as $index => $label) {
                    $labels[] = array('value' => $index + 1, 'label' => trim($label));
                }
            }
            $outcomes[] = array('id' => (int)$r->id,
                'shortname' => (string)$r->shortname, 'fullname' => (string)$r->fullname,
                'description' => (string)$r->description, 'scaleid' => (int)$r->scaleid,
                'scale' => (string)$r->scale, 'scale_levels' => $labels);
        }
        return array('courseid' => (int)$course->id, 'outcomes' => $outcomes);
    }
    public static function get_course_outcomes_returns() {
        return new external_single_structure(array(
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'outcomes' => new external_multiple_structure(new external_single_structure(array(
                'id' => new external_value(PARAM_INT, 'Outcome ID'),
                'shortname' => new external_value(PARAM_RAW, 'Outcome short name'),
                'fullname' => new external_value(PARAM_RAW, 'Outcome full name'),
                'description' => new external_value(PARAM_RAW, 'Stored description; may contain HTML'),
                'scaleid' => new external_value(PARAM_INT, 'Scale ID'),
                'scale' => new external_value(PARAM_RAW, 'Stored comma-separated scale labels'),
                'scale_levels' => new external_multiple_structure(new external_single_structure(array(
                    'value' => new external_value(PARAM_INT, 'One-based scale position, not mastery percentage'),
                    'label' => new external_value(PARAM_RAW, 'Scale label')
                )))
            )))
        ));
    }
}
