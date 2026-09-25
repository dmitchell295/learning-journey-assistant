<?php
/**
 * setup_learning_outcomes.php
 *
 * Creates real Moodle "Outcomes" (Site administration > Grades > Outcomes)
 * for each LJA subject — 4 per subject, each corresponding to one of the
 * 4 core competencies (and the rubric criterion that assesses it).
 *
 * Creates course-linked outcome definitions only. The shared ordering is a
 * fixture convention, not a stored criterion link or student achievement.
 *
 * WHERE TO PUT THIS FILE:
 *   <moodle_root>/admin/cli/setup_learning_outcomes.php
 *
 * HOW TO RUN IT:
 *   docker cp setup_learning_outcomes.php moodle-migration-moodle-1:/var/www/html/admin/cli/setup_learning_outcomes.php
 *   docker exec -it moodle-migration-moodle-1 php /var/www/html/admin/cli/setup_learning_outcomes.php
 *
 * Safe to re-run — skips outcomes that already exist (matched by shortname).
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/cronlib.php');

cron_setup_user();

$competency_order = ['technical', 'justification', 'communication', 'requirements'];

// 4 learning outcomes per subject, one per competency, in the same order as
// competency_order — so LO1 pairs with the "technical" rubric criterion, etc.
$subject_outcomes = [
    'CSE2OOP' => [
        'technical' => 'Design and implement well-structured classes and objects applying core OOP principles (encapsulation, inheritance, polymorphism).',
        'justification' => 'Justify the selection and application of design patterns for a given software problem.',
        'communication' => 'Communicate software designs clearly through documentation and code comments.',
        'requirements' => 'Translate a set of functional requirements into a working object-oriented solution.',
    ],
    'CSE2DWD' => [
        'technical' => 'Implement a functioning database-backed web application integrating front-end and back-end components.',
        'justification' => 'Justify architectural and schema design decisions for a web application.',
        'communication' => 'Produce clear technical documentation describing a web system\'s design and implementation.',
        'requirements' => 'Implement a web application that fully satisfies a given set of functional requirements.',
    ],
    'CSE1ALG' => [
        'technical' => 'Design and implement correct, efficient algorithms and data structures for a given problem.',
        'justification' => 'Analyse and justify the time/space complexity trade-offs of an algorithmic approach.',
        'communication' => 'Clearly explain and justify an algorithmic approach to a technical or non-technical audience.',
        'requirements' => 'Apply an appropriate algorithm or data structure to fully solve a specified problem.',
    ],
    'CSE2NET' => [
        'technical' => 'Configure and implement reliable network protocols and topologies.',
        'justification' => 'Justify network design decisions based on performance, security, and reliability trade-offs.',
        'communication' => 'Produce a clear technical report documenting network design and configuration.',
        'requirements' => 'Meet a specified set of network functionality and performance requirements.',
    ],
    'CSE3AIP' => [
        'technical' => 'Implement AI/ML models or algorithms that produce accurate, meaningful outputs.',
        'justification' => 'Justify the choice of AI approach and its trade-offs relative to alternatives.',
        'communication' => 'Explain the reasoning process behind an AI model\'s decisions and outputs.',
        'requirements' => 'Apply an AI approach that fully addresses a specified task\'s requirements.',
    ],
    'CSE2SEP' => [
        'technical' => 'Apply sound software engineering practices throughout the development lifecycle.',
        'justification' => 'Justify process and design decisions made during software development.',
        'communication' => 'Produce documentation that effectively supports team communication and collaboration.',
        'requirements' => 'Trace requirements from specification through to final implementation.',
    ],
    'CSE1DBS' => [
        'technical' => 'Design and implement efficient, well-normalized relational database schemas and queries.',
        'justification' => 'Justify schema design decisions including normalization and indexing trade-offs.',
        'communication' => 'Clearly document database schemas and queries for other developers.',
        'requirements' => 'Implement a database solution that satisfies a specified set of data requirements.',
    ],
    'CSE3CYB' => [
        'technical' => 'Implement security controls that effectively mitigate identified risks.',
        'justification' => 'Develop and justify a threat model with appropriate mitigations.',
        'communication' => 'Produce clear security reports documenting analysis and findings.',
        'requirements' => 'Implement a solution that satisfies a specified set of security requirements.',
    ],
];

// -----------------------------------------------------------------------

function get_or_create_scale() {
    global $DB, $USER;

    $existing = $DB->get_record('scale', ['name' => 'LJA Outcome Achievement Scale']);
    if ($existing) {
        mtrace("Scale already exists — skipping creation.");
        return $existing;
    }

    $scale = new stdClass();
    $scale->courseid = 0; // 0 = site-wide, usable by any course
    $scale->userid = $USER->id;
    $scale->name = 'LJA Outcome Achievement Scale';
    $scale->scale = 'Not Yet Achieved,Partially Achieved,Achieved';
    $scale->description = 'Standard 3-point scale for tracking learning outcome achievement in LJA.';
    $scale->descriptionformat = FORMAT_PLAIN;
    $scale->timemodified = time();

    $scale->id = $DB->insert_record('scale', $scale);
    mtrace("Created outcome achievement scale.");
    return $scale;
}

function get_or_create_outcome($shortname, $fullname, $scaleid) {
    global $DB, $USER;

    $existing = $DB->get_record('grade_outcomes', ['shortname' => $shortname]);
    if ($existing) {
        return $existing;
    }

    $outcome = new stdClass();
    $outcome->courseid = null; // standard/site-wide outcome, attached to courses via grade_outcomes_courses
    $outcome->shortname = $shortname;
    $outcome->fullname = $fullname;
    $outcome->scaleid = $scaleid;
    $outcome->description = $fullname;
    $outcome->descriptionformat = FORMAT_PLAIN;
    $outcome->usermodified = $USER->id;
    $outcome->timecreated = time();
    $outcome->timemodified = time();

    $outcome->id = $DB->insert_record('grade_outcomes', $outcome);
    return $outcome;
}

function attach_outcome_to_course($outcomeid, $courseid) {
    global $DB;

    $existing = $DB->get_record('grade_outcomes_courses', ['outcomeid' => $outcomeid, 'courseid' => $courseid]);
    if ($existing) {
        return;
    }

    $link = new stdClass();
    $link->outcomeid = $outcomeid;
    $link->courseid = $courseid;
    $DB->insert_record('grade_outcomes_courses', $link);
}

// --- RUN -----------------------------------------------------------------

mtrace("=== Setting up learning outcomes ===\n");

$scale = get_or_create_scale();

foreach ($subject_outcomes as $shortname => $outcomes) {
    $course = $DB->get_record('course', ['shortname' => $shortname]);
    if (!$course) {
        mtrace("Course $shortname not found — skipping.");
        continue;
    }
    mtrace("Subject: $shortname");

    $i = 1;
    foreach ($competency_order as $key) {
        $outcomeshortname = $shortname . '-LO' . $i;
        $fulltext = $outcomes[$key];

        try {
            $outcome = get_or_create_outcome($outcomeshortname, $fulltext, $scale->id);
            attach_outcome_to_course($outcome->id, $course->id);
            mtrace("  $outcomeshortname created and attached (maps to: $key).");
        } catch (\Throwable $e) {
            mtrace("  !! Could not create/attach $outcomeshortname: " . $e->getMessage());
            global $DB;
            if ($DB->is_transaction_started()) {
                $DB->force_transaction_rollback();
            }
        }
        $i++;
    }
}

mtrace("\n=== Done. ===");
