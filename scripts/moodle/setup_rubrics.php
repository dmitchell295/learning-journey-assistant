<?php
/**
 * setup_rubrics.php
 *
 * Adds a real Moodle rubric (Advanced Grading: Rubric) to every LJA
 * assignment, with subject-specific criteria that each map internally to
 * one of the 4 core competencies. Then fills in each enrolled student's
 * rubric selections to match their existing persona/band — without
 * touching the numeric grades already written by generate_lja_data.php.
 *
 * WHERE TO PUT THIS FILE:
 *   <moodle_root>/admin/cli/setup_rubrics.php
 *
 * HOW TO RUN IT:
 *   docker cp setup_rubrics.php moodle-migration-moodle-1:/var/www/html/admin/cli/setup_rubrics.php
 *   docker exec -it moodle-migration-moodle-1 php /var/www/html/admin/cli/setup_rubrics.php
 *
 * Safe to re-run — skips assignments that already have a rubric defined.
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/grade/grading/lib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/cronlib.php');

cron_setup_user(); // run as admin, same reason as generate_lja_data.php

// --- Same student/persona data as generate_lja_data.php --------------

$students = [
    ['username' => 'priyak',
        'bands' => ['technical' => 'strong', 'justification' => 'weak', 'communication' => 'adequate', 'requirements' => 'adequate'],
        'subjects' => ['CSE2OOP', 'CSE2DWD', 'CSE1ALG', 'CSE2NET']],
    ['username' => 'student2',
        'bands' => ['technical' => 'strong', 'justification' => 'strong', 'communication' => 'strong', 'requirements' => 'strong'],
        'subjects' => ['CSE1ALG', 'CSE2NET', 'CSE3AIP', 'CSE2SEP']],
    ['username' => 'student3',
        'bands' => ['technical' => 'weak', 'justification' => 'weak', 'communication' => 'weak', 'requirements' => 'weak'],
        'subjects' => ['CSE3AIP', 'CSE2SEP', 'CSE1DBS', 'CSE3CYB']],
    ['username' => 'student4',
        'bands' => ['technical' => 'strong', 'justification' => 'adequate', 'communication' => 'weak', 'requirements' => 'adequate'],
        'subjects' => ['CSE1DBS', 'CSE3CYB', 'CSE2OOP', 'CSE2DWD']],
    ['username' => 'student5',
        'bands' => ['technical' => 'adequate', 'justification' => 'adequate', 'communication' => 'adequate', 'requirements' => 'adequate'],
        'subjects' => ['CSE2OOP', 'CSE3AIP', 'CSE1DBS', 'CSE2NET']],
];

// Fixed order — position in this list determines rubric criterion sortorder,
// which is how we map a saved criterion back to its competency (no fragile
// text-matching needed).
$competency_order = ['technical', 'justification', 'communication', 'requirements'];

// Subject-specific criteria, each with its own label AND its own specific
// level descriptions (not generic reused text). Score per level stays fixed
// at 10/18/25 across the board — only the wording is unique per criterion.
$subject_criteria = [
    'CSE2OOP' => [
        'technical' => ['label' => 'Class Design & Implementation', 'levels' => [
            'weak' => 'Class structure is poorly designed, with duplicated logic, tight coupling, or unclear responsibilities.',
            'adequate' => 'Class structure is reasonable but has some duplication or unclear responsibilities in places.',
            'strong' => 'Classes are well encapsulated with clear responsibilities, appropriate inheritance/composition, and minimal duplication.']],
        'justification' => ['label' => 'Design Pattern Justification', 'levels' => [
            'weak' => 'No design patterns are used or justified; architectural choices appear arbitrary.',
            'adequate' => 'Some design patterns are used, but the reasoning behind choosing them isn\'t fully explained.',
            'strong' => 'Appropriate design patterns are chosen and clearly justified based on the problem\'s needs.']],
        'communication' => ['label' => 'Code Documentation & Clarity', 'levels' => [
            'weak' => 'Code is largely undocumented and difficult to follow without significant effort.',
            'adequate' => 'Code has some comments and documentation, but key parts are left unexplained.',
            'strong' => 'Code is clearly documented with meaningful comments and consistent naming throughout.']],
        'requirements' => ['label' => 'Requirements Coverage', 'levels' => [
            'weak' => 'Several core OOP requirements (encapsulation, inheritance, etc.) are missing or misapplied.',
            'adequate' => 'Most OOP requirements are met, with a few gaps in application.',
            'strong' => 'All OOP requirements are fully and correctly implemented.']],
    ],
    'CSE2DWD' => [
        'technical' => ['label' => 'Database & Web Implementation', 'levels' => [
            'weak' => 'Database queries or web components contain significant bugs or fail to function correctly.',
            'adequate' => 'Database and web components mostly work, with minor bugs or inefficiencies.',
            'strong' => 'Database and web components are implemented cleanly and function correctly end-to-end.']],
        'justification' => ['label' => 'Architecture & Design Rationale', 'levels' => [
            'weak' => 'No clear rationale is given for the chosen architecture or schema design.',
            'adequate' => 'Some rationale is given for design choices, but trade-offs aren\'t fully explored.',
            'strong' => 'Architecture and schema decisions are clearly justified with well-considered trade-offs.']],
        'communication' => ['label' => 'Technical Write-up Clarity', 'levels' => [
            'weak' => 'The write-up is difficult to follow and doesn\'t clearly explain the system.',
            'adequate' => 'The write-up is understandable but could be clearer or better organised.',
            'strong' => 'The write-up clearly and concisely explains the system\'s design and implementation.']],
        'requirements' => ['label' => 'Functional Requirements Coverage', 'levels' => [
            'weak' => 'Multiple functional requirements are missing or not working as intended.',
            'adequate' => 'Most functional requirements are met, with a few gaps.',
            'strong' => 'All functional requirements are fully implemented and working correctly.']],
    ],
    'CSE1ALG' => [
        'technical' => ['label' => 'Algorithm Correctness & Efficiency', 'levels' => [
            'weak' => 'The algorithm produces incorrect results or has significant efficiency problems.',
            'adequate' => 'The algorithm is mostly correct, with minor efficiency issues.',
            'strong' => 'The algorithm is correct and efficient, using an appropriate approach for the problem.']],
        'justification' => ['label' => 'Complexity Analysis & Trade-off Reasoning', 'levels' => [
            'weak' => 'No complexity analysis is provided, or the analysis given is incorrect.',
            'adequate' => 'Complexity analysis is attempted but lacks depth or has minor errors.',
            'strong' => 'Complexity analysis is accurate and clearly explains the trade-offs of the chosen approach.']],
        'communication' => ['label' => 'Explanation of Approach', 'levels' => [
            'weak' => 'The approach is not clearly explained, making it hard to follow the reasoning.',
            'adequate' => 'The approach is explained but could be clearer in places.',
            'strong' => 'The approach is clearly and logically explained from start to finish.']],
        'requirements' => ['label' => 'Problem Requirements Coverage', 'levels' => [
            'weak' => 'The solution does not fully address the stated problem requirements.',
            'adequate' => 'The solution addresses most requirements, with minor gaps.',
            'strong' => 'The solution fully and correctly addresses all stated requirements.']],
    ],
    'CSE2NET' => [
        'technical' => ['label' => 'Protocol Configuration & Implementation', 'levels' => [
            'weak' => 'Protocols are misconfigured or fail to establish reliable connections.',
            'adequate' => 'Protocols are mostly configured correctly, with minor connectivity issues.',
            'strong' => 'Protocols are configured correctly and connections are reliable throughout.']],
        'justification' => ['label' => 'Network Design Rationale', 'levels' => [
            'weak' => 'No justification is given for the network topology or design choices.',
            'adequate' => 'Some justification is given, but trade-offs aren\'t fully explored.',
            'strong' => 'Network design choices are clearly justified with well-considered trade-offs.']],
        'communication' => ['label' => 'Technical Report Clarity', 'levels' => [
            'weak' => 'The technical report is unclear and difficult to follow.',
            'adequate' => 'The report is understandable but could be more clearly structured.',
            'strong' => 'The report clearly and concisely documents the network design and results.']],
        'requirements' => ['label' => 'Network Requirements Coverage', 'levels' => [
            'weak' => 'Several network requirements are missing or not functioning correctly.',
            'adequate' => 'Most network requirements are met, with minor gaps.',
            'strong' => 'All network requirements are fully met and functioning correctly.']],
    ],
    'CSE3AIP' => [
        'technical' => ['label' => 'Model/Algorithm Implementation', 'levels' => [
            'weak' => 'The model or algorithm is implemented incorrectly or fails to produce meaningful results.',
            'adequate' => 'The model or algorithm mostly works, with some issues in implementation.',
            'strong' => 'The model or algorithm is implemented correctly and produces meaningful, accurate results.']],
        'justification' => ['label' => 'Approach Justification & Trade-offs', 'levels' => [
            'weak' => 'No justification is given for the chosen AI approach or its trade-offs.',
            'adequate' => 'Some justification is given, but alternative approaches aren\'t considered.',
            'strong' => 'The chosen approach is well justified, with clear consideration of alternatives and trade-offs.']],
        'communication' => ['label' => 'Explanation of Reasoning Process', 'levels' => [
            'weak' => 'The reasoning behind the model\'s decisions is unclear or not explained.',
            'adequate' => 'The reasoning is explained but lacks depth in places.',
            'strong' => 'The reasoning behind the model\'s decisions is clearly and thoroughly explained.']],
        'requirements' => ['label' => 'Task Requirements Coverage', 'levels' => [
            'weak' => 'The solution does not fully address the stated task requirements.',
            'adequate' => 'Most task requirements are addressed, with minor gaps.',
            'strong' => 'All task requirements are fully and correctly addressed.']],
    ],
    'CSE2SEP' => [
        'technical' => ['label' => 'Implementation Quality & Practice', 'levels' => [
            'weak' => 'Implementation shows poor software engineering practice, with significant issues.',
            'adequate' => 'Implementation follows reasonable practice, with a few inconsistencies.',
            'strong' => 'Implementation consistently follows strong software engineering practice.']],
        'justification' => ['label' => 'Process & Design Justification', 'levels' => [
            'weak' => 'No justification is given for the chosen process or design decisions.',
            'adequate' => 'Some justification is given, but reasoning could be more thorough.',
            'strong' => 'Process and design decisions are clearly justified and well reasoned.']],
        'communication' => ['label' => 'Documentation & Team Communication', 'levels' => [
            'weak' => 'Documentation is minimal and doesn\'t support effective team communication.',
            'adequate' => 'Documentation is present but could better support the team\'s understanding.',
            'strong' => 'Documentation is clear and thorough, supporting effective team communication.']],
        'requirements' => ['label' => 'Requirements Traceability', 'levels' => [
            'weak' => 'Requirements are not clearly traced through the design and implementation.',
            'adequate' => 'Requirements are mostly traceable, with a few gaps.',
            'strong' => 'Requirements are clearly traceable from specification through to implementation.']],
    ],
    'CSE1DBS' => [
        'technical' => ['label' => 'Query Design & Normalization', 'levels' => [
            'weak' => 'Queries are inefficient or the schema is poorly normalized, causing data integrity issues.',
            'adequate' => 'Queries and normalization are mostly sound, with minor issues.',
            'strong' => 'Queries are efficient and the schema is well normalized with strong data integrity.']],
        'justification' => ['label' => 'Schema Design Rationale', 'levels' => [
            'weak' => 'No rationale is given for the schema design choices.',
            'adequate' => 'Some rationale is given, but trade-offs aren\'t fully explored.',
            'strong' => 'Schema design choices are clearly justified with well-considered trade-offs.']],
        'communication' => ['label' => 'Query/Schema Documentation Clarity', 'levels' => [
            'weak' => 'Queries and schema are poorly documented and hard to follow.',
            'adequate' => 'Documentation is present but could be clearer or more complete.',
            'strong' => 'Queries and schema are clearly documented and easy to follow.']],
        'requirements' => ['label' => 'Data Requirements Coverage', 'levels' => [
            'weak' => 'Several data requirements are missing or not correctly implemented.',
            'adequate' => 'Most data requirements are met, with minor gaps.',
            'strong' => 'All data requirements are fully and correctly implemented.']],
    ],
    'CSE3CYB' => [
        'technical' => ['label' => 'Security Control Implementation', 'levels' => [
            'weak' => 'Security controls are missing, misconfigured, or fail to mitigate the stated risks.',
            'adequate' => 'Security controls are mostly implemented correctly, with minor gaps.',
            'strong' => 'Security controls are implemented correctly and effectively mitigate the stated risks.']],
        'justification' => ['label' => 'Threat Model & Mitigation Justification', 'levels' => [
            'weak' => 'No threat model is provided, or mitigations aren\'t justified against identified threats.',
            'adequate' => 'A threat model is provided but mitigations aren\'t fully justified.',
            'strong' => 'A clear threat model is provided with well-justified mitigations for each identified threat.']],
        'communication' => ['label' => 'Security Report Clarity', 'levels' => [
            'weak' => 'The security report is unclear and difficult to follow.',
            'adequate' => 'The report is understandable but could be more clearly structured.',
            'strong' => 'The report clearly and concisely documents the security analysis and findings.']],
        'requirements' => ['label' => 'Security Requirements Coverage', 'levels' => [
            'weak' => 'Several security requirements are missing or not correctly addressed.',
            'adequate' => 'Most security requirements are addressed, with minor gaps.',
            'strong' => 'All security requirements are fully and correctly addressed.']],
    ],
];

// Fixed scores per band — only the wording above varies per criterion.
$band_scores = ['weak' => 10, 'adequate' => 18, 'strong' => 25];

// -----------------------------------------------------------------------

function build_rubric_definition($subject_def, $competency_order, $band_scores) {
    $criteria = [];
    $i = 1;
    foreach ($competency_order as $key) {
        $criterioninfo = $subject_def[$key];
        $levels = [];
        $j = 1;
        foreach (['weak', 'adequate', 'strong'] as $band) {
            $levels['NEWID' . $j] = [
                'score' => $band_scores[$band],
                'definition' => $criterioninfo['levels'][$band],
                'definitionformat' => FORMAT_PLAIN,
            ];
            $j++;
        }
        $criteria['NEWID' . $i] = [
            'description' => $criterioninfo['label'],
            'descriptionformat' => FORMAT_PLAIN,
            'sortorder' => $i,
            'levels' => $levels,
        ];
        $i++;
    }
    return $criteria;
}

function setup_rubric_for_assignment($assign, $cm, $subject_def, $competency_order, $band_scores) {
    global $DB;

    $context = context_module::instance($cm->id);
    $gradingman = get_grading_manager($context, 'mod_assign', 'submissions');
    $gradingman->set_active_method('rubric');
    $controller = $gradingman->get_controller('rubric');

    if ($controller->is_form_defined()) {
        mtrace("    Existing rubric preserved for {$assign->name}; skipping.");
        return null;
    }

    $data = new stdClass();
    $data->rubric = [
        'criteria' => build_rubric_definition($subject_def, $competency_order, $band_scores),
        'options' => [
            'sortlevelsasc' => 1,
            'lockzeropoints' => 0,
            'showdescriptionteacher' => 1,
            'showdescriptionstudent' => 1,
            'showscoreteacher' => 1,
            'showscorestudent' => 1,
            'enableremarks' => 1,
            'showremarksstudent' => 1,
        ],
    ];
    $data->name = $assign->name . ' Rubric';
    $data->description = ['text' => 'Rubric for ' . $assign->name, 'format' => FORMAT_HTML];
    $data->descriptionformat = FORMAT_HTML;
    $data->status = gradingform_controller::DEFINITION_STATUS_READY;

    $controller->update_definition($data);
    mtrace("    Rubric created for {$assign->name}.");
    return $controller;
}

/**
 * Returns [competency_key => criterionid] by relying on sortorder matching
 * the order competencies were created in — no text-matching needed.
 */
function map_criteria_to_competencies($controller, $competency_order) {
    global $DB;

    $definition = $controller->get_definition();
    if (!$definition || !$definition->id) {
        return [];
    }

    $criteria = $DB->get_records('gradingform_rubric_criteria', ['definitionid' => $definition->id], 'sortorder ASC');
    if (empty($criteria)) {
        return [];
    }

    $map = [];
    $i = 0;
    foreach ($criteria as $criterionid => $criterion) {
        if (!isset($competency_order[$i])) {
            break;
        }
        $levels = $DB->get_records('gradingform_rubric_levels', ['criterionid' => $criterionid]);
        $map[$competency_order[$i]] = ['id' => $criterionid, 'levels' => $levels];
        $i++;
    }
    return $map;
}

function fill_rubric_for_student($controller, $assign, $userid, $bands, $competency_order) {
    global $DB, $USER;

    $keymap = map_criteria_to_competencies($controller, $competency_order);
    if (empty($keymap)) {
        mtrace("      !! No criteria found on rubric definition — skipping fill.");
        return;
    }

    $grade = $DB->get_record('assign_grades', ['assignment' => $assign->id, 'userid' => $userid]);
    if (!$grade) {
        mtrace("      !! No existing grade record for user $userid — skipping rubric fill.");
        return;
    }

    $instance = $controller->get_or_create_instance(0, $USER->id, $grade->id);

    $filldata = ['criteria' => []];
    foreach ($keymap as $key => $info) {
        $band = $bands[$key];
        $levelid = null;
        foreach ($info['levels'] as $lid => $level) {
            if ($band === 'weak' && $level->score == 10) { $levelid = $lid; }
            if ($band === 'adequate' && $level->score == 18) { $levelid = $lid; }
            if ($band === 'strong' && $level->score == 25) { $levelid = $lid; }
        }
        if (!$levelid) {
            continue;
        }
        $filldata['criteria'][$info['id']] = [
            'levelid' => $levelid,
            'remark' => '',
            'remarkformat' => FORMAT_PLAIN,
        ];
    }

    if (count($filldata['criteria']) !== count($competency_order)) {
        throw new Exception('Incomplete synthetic rubric selections.');
    }
    // Finalise the rubric using Moodle's public API. Deliberately do not push
    // its calculated score to the gradebook: seed grades are separate data.
    $instance->submit_and_get_grade($filldata, $grade->id);
}

// --- RUN -----------------------------------------------------------------

mtrace("=== Setting up subject-specific rubrics ===\n");

foreach ($subject_criteria as $shortname => $subject_def) {
    $course = $DB->get_record('course', ['shortname' => $shortname]);
    if (!$course) {
        mtrace("Course $shortname not found — skipping.");
        continue;
    }
    mtrace("Subject: $shortname");

    $assigns = $DB->get_records('assign', ['course' => $course->id]);
    foreach ($assigns as $assign) {
        $cm = get_coursemodule_from_instance('assign', $assign->id, $course->id);
        if (!$cm || !empty($cm->deletioninprogress) ||
                !preg_match('/^' . preg_quote($shortname, '/') . ' Assignment [1-4]$/', $assign->name)) {
            continue;
        }

        try {
            $controller = setup_rubric_for_assignment($assign, $cm, $subject_def, $competency_order, $band_scores);
        } catch (\Throwable $e) {
            mtrace("    !! Could not set up rubric for {$assign->name}: " . $e->getMessage());
            if ($DB->is_transaction_started()) {
                $DB->force_transaction_rollback();
            }
            continue;
        }

        if ($controller === null) { continue; }

        foreach ($students as $student) {
            if (!in_array($shortname, $student['subjects'], true)) {
                continue;
            }
            $user = $DB->get_record('user', ['username' => $student['username']]);
            if (!$user) {
                continue;
            }
            try {
                fill_rubric_for_student($controller, $assign, $user->id, $student['bands'], $competency_order);
            } catch (\Throwable $e) {
                mtrace("      !! Could not fill rubric for {$student['username']} on {$assign->name}: " . $e->getMessage());
                if ($DB->is_transaction_started()) {
                    $DB->force_transaction_rollback();
                }
            }
        }
        mtrace("    Filled rubric for {$assign->name}.");
    }
}

mtrace("\n=== Done. ===");
