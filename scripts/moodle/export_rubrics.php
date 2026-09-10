<?php
/**
 * export_rubrics.php
 *
 * Reads all rubric data (criteria, levels, and each student's selections)
 * directly from Moodle's database and writes it to rubric_data.json.
 *
 * Optional offline diagnostic export. The REST API now exposes rubrics;
 * use scripts/pull_moodle_data.py for the live integration.
 * Competency order is a convention for these generated fixtures only.
 *
 * WHERE TO PUT THIS FILE:
 *   <moodle_root>/admin/cli/export_rubrics.php
 *
 * HOW TO RUN IT:
 *   docker cp export_rubrics.php moodle-migration-moodle-1:/var/www/html/admin/cli/export_rubrics.php
 *   docker exec -it moodle-migration-moodle-1 php /var/www/html/admin/cli/export_rubrics.php
 *
 * OUTPUT: written inside the container to /tmp/rubric_data.json — copy it
 * back to your computer afterwards with:
 *   docker cp moodle-migration-moodle-1:/tmp/rubric_data.json rubric_data.json
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/grade/grading/lib.php');

$competency_order = ['technical', 'justification', 'communication', 'requirements'];
$subjects = ['CSE2OOP', 'CSE2DWD', 'CSE1ALG', 'CSE2NET', 'CSE3AIP', 'CSE2SEP', 'CSE1DBS', 'CSE3CYB'];

function get_rubric_criteria($controller) {
    global $DB;

    $definition = $controller->get_definition();
    if (!$definition || !$definition->id) {
        return null;
    }

    $criteria = $DB->get_records('gradingform_rubric_criteria', ['definitionid' => $definition->id], 'sortorder ASC');
    $result = [];
    foreach ($criteria as $criterionid => $criterion) {
        $levels = $DB->get_records('gradingform_rubric_levels', ['criterionid' => $criterionid], 'score ASC');
        $levelsout = [];
        foreach ($levels as $level) {
            $levelsout[] = [
                'levelid' => (int) $level->id,
                'score' => (float) $level->score,
                'definition' => $level->definition,
            ];
        }
        $result[$criterionid] = [
            'criterionid' => (int) $criterionid,
            'label' => $criterion->description,
            'levels' => $levelsout,
        ];
    }
    return $result;
}

function get_student_fillings($controller, $assignid, $userid) {
    global $DB;

    $grade = $DB->get_record('assign_grades', ['assignment' => $assignid, 'userid' => $userid]);
    if (!$grade) {
        return null;
    }

    $definition = $controller->get_definition();

$instance = $DB->get_record('grading_instances', [
    'itemid' => $grade->id,
    'definitionid' => $definition->id,
    'status' => 1,
]);
    if (!$instance) {
        return null;
    }

    $fillings = $DB->get_records('gradingform_rubric_fillings', ['instanceid' => $instance->id]);
    $result = [];
    foreach ($fillings as $filling) {
        $result[$filling->criterionid] = [
            'levelid' => (int) $filling->levelid,
            'remark' => $filling->remark,
        ];
    }
    return $result;
}

// --- RUN -----------------------------------------------------------------

mtrace("=== Exporting rubric data ===\n");

$output = [];

foreach ($subjects as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname]);
    if (!$course) {
        mtrace("Course $shortname not found — skipping.");
        continue;
    }
    mtrace("Subject: $shortname");

    $output[$shortname] = ['assignments' => []];

    $assigns = $DB->get_records('assign', ['course' => $course->id]);
    foreach ($assigns as $assign) {
        $cm = get_coursemodule_from_instance('assign', $assign->id, $course->id);
        if (!$cm || !empty($cm->deletioninprogress) ||
                !preg_match('/^' . preg_quote($shortname, '/') . ' Assignment [1-4]$/', $assign->name)) {
            continue;
        }

        $context = context_module::instance($cm->id);
        $gradingman = get_grading_manager($context, 'mod_assign', 'submissions');
        $controller = $gradingman->get_controller('rubric');

        if (!$controller->is_form_defined()) {
            continue; // no rubric on this assignment
        }

        $criteria = get_rubric_criteria($controller);
        if (!$criteria) {
            continue;
        }

        // Build a criterionid -> competency key map using creation order.
        $criteriabykey = [];
        $i = 0;
        foreach ($criteria as $criterionid => $info) {
            if (isset($competency_order[$i])) {
                $criteriabykey[$criterionid] = $competency_order[$i];
            }
            $i++;
        }

        $students = $DB->get_records_sql(
            "SELECT DISTINCT u.id, u.username
               FROM {assign_grades} ag
               JOIN {user} u ON u.id = ag.userid
              WHERE ag.assignment = ?", [$assign->id]
        );

        $assignmentdata = [
            'criteria' => array_values($criteria),
            'student_selections' => [],
        ];

        foreach ($students as $student) {
            $fillings = get_student_fillings($controller, $assign->id, $student->id);
            if (!$fillings) {
                continue;
            }

            $selections = [];
            foreach ($fillings as $criterionid => $filling) {
                $competencykey = $criteriabykey[$criterionid] ?? null;
                $label = $criteria[$criterionid]['label'] ?? null;
                $selections[] = [
                    'criterion' => $label,
                    'competency' => $competencykey,
                    'levelid' => $filling['levelid'],
                    'remark' => $filling['remark'],
                ];
            }
            $assignmentdata['student_selections'][$student->username] = $selections;
        }

        $output[$shortname]['assignments'][$assign->name] = $assignmentdata;
        mtrace("  Exported rubric data for {$assign->name}.");
    }
}

$json = json_encode($output, JSON_PRETTY_PRINT);
file_put_contents('/tmp/rubric_data.json', $json);

mtrace("\n=== Done. Written to /tmp/rubric_data.json inside the container. ===");
mtrace("Copy it out with: docker cp moodle-migration-moodle-1:/tmp/rubric_data.json rubric_data.json");
