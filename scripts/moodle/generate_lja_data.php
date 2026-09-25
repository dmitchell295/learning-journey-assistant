<?php
/**
 * generate_lja_data.php
 *
 * Learning Journey Assistant — synthetic data generator.
 * Creates synthetic students & subjects, enrols each persona in its subjects, adds four
 * assignments per subject (spread across the term), and writes a grade +
 * persona-matched feedback comment for each student on each assignment.
 *
 * WHERE TO PUT THIS FILE:
 *   <moodle_root>/admin/cli/generate_lja_data.php
 *   (same folder as Moodle's own cli scripts like admin/cli/install.php)
 *
 * HOW TO RUN IT (from your host machine):
 *   docker exec -it <your_moodle_container_name> php \
 *     /var/www/html/admin/cli/generate_lja_data.php
 *
 *   Replace <your_moodle_container_name> with your real container name —
 *   find it with: docker ps
 *   And replace /var/www/html with wherever Moodle actually lives inside
 *   your container if it's different (check with: docker exec -it <container> pwd
 *   from inside a moodle shell, or look at your docker-compose.yml volumes).
 *
 * SAFE TO RE-RUN: existing users/courses (matched by username/shortname)
 * are skipped, not duplicated or overwritten.
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');

// Show full error details instead of the generic "Database transaction aborted" message.
$CFG->debug = (E_ALL | E_STRICT);
$CFG->debugdisplay = 1;

require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/lib.php');
require_once($CFG->libdir . '/cronlib.php');

// CLI scripts have no logged-in user by default, which makes permission
// checks (e.g. "can this user grade an assignment?") fail. Run as admin.
cron_setup_user();

// ---------------------------------------------------------------------
// 1. DEFINE THE DATA
// ---------------------------------------------------------------------

$subjects = [
    // shortname is skipped if it already exists (e.g. CSE2OOP)
    ['shortname' => 'CSE2OOP', 'fullname' => 'Object-Oriented Programming'],
    ['shortname' => 'CSE2DWD', 'fullname' => 'Database & Web Development'],
    ['shortname' => 'CSE1ALG', 'fullname' => 'Algorithms & Data Structures'],
    ['shortname' => 'CSE2NET', 'fullname' => 'Computer Networks'],
    ['shortname' => 'CSE3AIP', 'fullname' => 'AI Principles'],
    ['shortname' => 'CSE2SEP', 'fullname' => 'Software Engineering Practice'],
    ['shortname' => 'CSE1DBS', 'fullname' => 'Database Systems'],
    ['shortname' => 'CSE3CYB', 'fullname' => 'Cybersecurity Fundamentals'],
];

// persona => per-competency performance band (strong / adequate / weak)
// competencies: technical, justification, communication, requirements
$students = [
    [
        'username' => 'priyak', 'firstname' => 'Priya', 'lastname' => 'K',
        'email' => 'priyak@lja.local',
        'bands' => ['technical' => 'strong', 'justification' => 'weak',
                    'communication' => 'adequate', 'requirements' => 'adequate'],
        'subjects' => ['CSE2OOP', 'CSE2DWD', 'CSE1ALG', 'CSE2NET'],
    ],
    [
        'username' => 'student2', 'firstname' => 'Amara', 'lastname' => 'Osei',
        'email' => 'student2@lja.local',
        'bands' => ['technical' => 'strong', 'justification' => 'strong',
                    'communication' => 'strong', 'requirements' => 'strong'],
        'subjects' => ['CSE1ALG', 'CSE2NET', 'CSE3AIP', 'CSE2SEP'],
    ],
    [
        'username' => 'student3', 'firstname' => 'Noah', 'lastname' => 'Bennett',
        'email' => 'student3@lja.local',
        'bands' => ['technical' => 'weak', 'justification' => 'weak',
                    'communication' => 'weak', 'requirements' => 'weak'],
        'subjects' => ['CSE3AIP', 'CSE2SEP', 'CSE1DBS', 'CSE3CYB'],
    ],
    [
        'username' => 'student4', 'firstname' => 'Kenji', 'lastname' => 'Tanaka',
        'email' => 'student4@lja.local',
        'bands' => ['technical' => 'strong', 'justification' => 'adequate',
                    'communication' => 'weak', 'requirements' => 'adequate'],
        'subjects' => ['CSE1DBS', 'CSE3CYB', 'CSE2OOP', 'CSE2DWD'],
    ],
    [
        'username' => 'student5', 'firstname' => 'Ella', 'lastname' => 'Marsh',
        'email' => 'student5@lja.local',
        // improvement arc handled separately — see generate_feedback()
        'bands' => ['technical' => 'adequate', 'justification' => 'adequate',
                    'communication' => 'adequate', 'requirements' => 'adequate'],
        'improving' => true,
        'subjects' => ['CSE2OOP', 'CSE3AIP', 'CSE1DBS', 'CSE2NET'],
    ],
];

$competencies = ['technical' => 'Technical Implementation',
                  'justification' => 'Justification & Reasoning',
                  'communication' => 'Communication',
                  'requirements' => 'Requirements Analysis'];

// Feedback templates: [competency][band] => array of sentence variants
$feedback_templates = [
    'technical' => [
        'strong' => [
            "Implementation is clean, correct, and well-structured. Edge cases are handled and the code follows good practice throughout.",
            "Strong implementation overall — the code follows good practice and handles edge cases well.",
            "A technically solid submission — logic is sound and the implementation handles most edge cases correctly.",
        ],
        'adequate' => [
            "Implementation is functionally correct with minor structural issues. A few edge cases were missed.",
            "Mostly correct implementation, with a few edge cases missed along the way.",
            "Solid technical work overall, with a couple of small structural gaps to tidy up.",
        ],
        'weak' => [
            "Implementation contains significant errors or does not fully meet the functional requirements. Core logic needs revisiting.",
            "Core logic needs significant rework — several parts of the implementation are incorrect.",
            "The implementation falls short of the functional requirements in several places.",
        ],
    ],
    'justification' => [
        'strong' => [
            "Design decisions are clearly justified with sound reasoning and awareness of trade-offs.",
            "Thoughtful, well-reasoned justification for every major design decision.",
            "Trade-offs are clearly weighed and design choices are well justified throughout.",
        ],
        'adequate' => [
            "Some justification is provided for design choices, but reasoning could go deeper into trade-offs.",
            "Reasoning behind design choices is present but could be more thorough.",
            "Design choices are explained but trade-offs aren't explored in much depth.",
        ],
        'weak' => [
            "Little to no justification is given for design decisions; reasoning is thin or missing entirely.",
            "Design decisions are not explained or justified in any meaningful way.",
            "Reasoning behind design choices is thin to non-existent.",
        ],
    ],
    'communication' => [
        'strong' => [
            "Explanation is clear, well-organised, and easy to follow for a technical or non-technical reader alike.",
            "Communicated clearly and logically from start to finish.",
            "Easy to follow and clearly structured throughout.",
        ],
        'adequate' => [
            "Explanation is mostly clear but could be more concise or better structured in places.",
            "Reasonably clear, though the structure could be tightened up.",
            "Gets the point across but would benefit from clearer organisation.",
        ],
        'weak' => [
            "Explanation is difficult to follow; ideas are not clearly communicated.",
            "The submission is hard to follow and would benefit from a clearer structure.",
            "Communication is unclear, making it hard to follow the intended approach.",
        ],
    ],
    'requirements' => [
        'strong' => [
            "All stated requirements are addressed accurately and completely.",
            "Full and accurate coverage of every stated requirement.",
            "Every requirement is met in detail, with nothing missed.",
        ],
        'adequate' => [
            "Most requirements are addressed; a small number are partially met or missed.",
            "Requirements are largely covered, though a couple of details were missed.",
            "Mostly complete coverage of requirements, with a minor gap or two.",
        ],
        'weak' => [
            "Several requirements are missing or misunderstood.",
            "A number of key requirements were not addressed.",
            "Multiple gaps remain in requirements coverage.",
        ],
    ],
];

$band_score = ['strong' => [80, 95], 'adequate' => [60, 79], 'weak' => [35, 59]];

// ---------------------------------------------------------------------
// 2. HELPER FUNCTIONS
// ---------------------------------------------------------------------

function get_or_create_course($subject) {
    global $DB;
    if ($existing = $DB->get_record('course', ['shortname' => $subject['shortname']])) {
        mtrace("  Course {$subject['shortname']} already exists — skipping creation.");
        return $existing;
    }
    $data = new stdClass();
    $data->fullname = $subject['fullname'];
    $data->shortname = $subject['shortname'];
    $data->category = 1; // Miscellaneous — change if you use a specific category id.
    $data->summary = $subject['fullname'] . ' — synthetic LJA subject.';
    $data->visible = 1;
    $course = create_course($data);
    mtrace("  Created course {$subject['shortname']}.");
    return $course;
}

function get_or_create_student($student) {
    global $DB, $CFG;
    if ($existing = $DB->get_record('user', ['username' => $student['username'], 'deleted' => 0])) {
        mtrace("  User {$student['username']} already exists — skipping creation.");
        return $existing;
    }
    $u = new stdClass();
    $u->username = $student['username'];
    $u->password = bin2hex(random_bytes(24)); // Synthetic accounts: no shared password.
    $u->firstname = $student['firstname'];
    $u->lastname = $student['lastname'];
    $u->email = $student['email'];
    $u->confirmed = 1;
    $u->mnethostid = $CFG->mnet_localhost_id;
    $u->auth = 'manual';
    $newid = user_create_user($u, true, false);
    mtrace("  Created user {$student['username']} (id {$newid}).");
    return $DB->get_record('user', ['id' => $newid]);
}

function enrol_student($course, $user) {
    global $DB;
    $context = context_course::instance($course->id);
    if (is_enrolled($context, $user)) {
        return;
    }
    $studentrole = $DB->get_record('role', ['shortname' => 'student']);
    enrol_try_internal_enrol($course->id, $user->id, $studentrole->id);
    mtrace("  Enrolled {$user->username} into {$course->shortname}.");
}

function get_or_create_assignment($course, $assignmentnum, $numassignments) {
    global $DB;
    $name = $course->shortname . ' Assignment ' . $assignmentnum;
    $existing = $DB->get_record('assign', ['course' => $course->id, 'name' => $name]);
    if ($existing) {
        $cm = get_coursemodule_from_instance('assign', $existing->id, $course->id);
        return [$existing, $cm];
    }

    // Spread assignments across the term: earliest due date is furthest in the
    // past, latest is most recent, evenly spaced two weeks apart.
    $weeksperassignment = 3;
    $weeksfromnow = ($numassignments - $assignmentnum) * $weeksperassignment;
    $duedate = time() - ($weeksfromnow * WEEKSECS) + (3 * DAYSECS);
    $opendate = $duedate - (2 * WEEKSECS);

    $moduleinfo = new stdClass();
    $moduleinfo->modulename = 'assign';
    $moduleinfo->module = $DB->get_field('modules', 'id', ['name' => 'assign'], MUST_EXIST);
    $moduleinfo->course = $course->id;
    $moduleinfo->section = 0;
    $moduleinfo->visible = 1;
    $moduleinfo->name = $name;
    $moduleinfo->intro = 'Synthetic assignment for LJA data generation.';
    $moduleinfo->introformat = FORMAT_HTML;
    $moduleinfo->duedate = $duedate;
    $moduleinfo->allowsubmissionsfromdate = $opendate;
    $moduleinfo->grade = 100;
    $moduleinfo->completion = 0;
    $moduleinfo->assignsubmission_onlinetext_enabled = 1;
    $moduleinfo->assignfeedback_comments_enabled = 1;

    // Required assign-specific settings normally filled in by the edit form —
    // must be set explicitly since we're bypassing that form.
    $moduleinfo->submissiondrafts = 0;
    $moduleinfo->requiresubmissionstatement = 0;
    $moduleinfo->sendnotifications = 0;
    $moduleinfo->sendlatenotifications = 0;
    $moduleinfo->sendstudentnotifications = 1;
    $moduleinfo->teamsubmission = 0;
    $moduleinfo->requireallteammemberssubmit = 0;
    $moduleinfo->blindmarking = 0;
    $moduleinfo->attemptreopenmethod = 'none';
    $moduleinfo->markingworkflow = 0;
    $moduleinfo->markingallocation = 0;
    $moduleinfo->cutoffdate = 0;
    $moduleinfo->gradingduedate = 0;

    $moduleinfo = add_moduleinfo($moduleinfo, $course);
    mtrace("  Created assignment: $name.");
    $assign = $DB->get_record('assign', ['id' => $moduleinfo->instance]);
    $cm = get_coursemodule_from_instance('assign', $assign->id, $course->id);
    return [$assign, $cm];
}

function generate_feedback($bands, $competencies, $templates, $improving, $assignmentnum, $numassignments, $username) {
    static $lastused = [];

    $lines = [];
    foreach ($competencies as $key => $label) {
        $band = $bands[$key];
        $variants = $templates[$key][$band];
        $trackkey = $username . '|' . $key;
        $previdx = $lastused[$trackkey] ?? -1;

        // Pick a variant different from last time (when more than one exists),
        // so the same student never sees identical wording on consecutive assignments.
        do {
            $idx = array_rand($variants);
        } while (count($variants) > 1 && $idx === $previdx);
        $lastused[$trackkey] = $idx;

        $lines[] = "[$label] " . $variants[$idx];
    }
    if ($improving) {
        if ($assignmentnum == 1) {
            $lines[] = "Early submission — there is clear room to build on these foundations across the term.";
        } elseif ($assignmentnum == $numassignments) {
            $lines[] = "Marked improvement compared to earlier submissions this term — this is the strongest work so far.";
        } else {
            $lines[] = "Continued improvement from the previous submission.";
        }
    }
    return implode("\n\n", $lines);
}

function compute_grade($bands, $band_score, $improving, $assignmentnum, $numassignments) {
    $total = 0;
    $count = 0;
    foreach ($bands as $band) {
        $range = $band_score[$band];
        $total += rand($range[0], $range[1]);
        $count++;
    }
    $avg = intdiv($total, $count);
    if ($improving) {
        // Progressive climb across the term: first assignment gets no bonus,
        // last assignment gets the full bonus.
        $progress = ($numassignments > 1) ? ($assignmentnum - 1) / ($numassignments - 1) : 1;
        $avg += (int) round($progress * 18);
    }
    return min(100, max(0, $avg));
}

function ensure_submission_exists($assign, $user) {
    global $DB;
    $existing = $DB->get_record('assign_submission', [
        'assignment' => $assign->id, 'userid' => $user->id, 'latest' => 1,
    ]);
    if ($existing) {
        return $existing;
    }
    $submission = new stdClass();
    $submission->assignment = $assign->id;
    $submission->userid = $user->id;
    $submission->timecreated = time();
    $submission->timemodified = time();
    $submission->status = 'new';
    $submission->attemptnumber = 0;
    $submission->latest = 1;
    $submission->id = $DB->insert_record('assign_submission', $submission);
    return $submission;
}

function save_assignment_grade($assign, $cm, $course, $user, $gradevalue, $feedbacktext) {
    global $DB, $USER;

    $now = time();

    // Write (or update) the grade record directly.
    $graderecord = $DB->get_record('assign_grades', ['assignment' => $assign->id, 'userid' => $user->id]);
    if ($graderecord) {
        return; // Preserve existing grades and feedback on reruns.
    } else {
        $graderecord = new stdClass();
        $graderecord->assignment = $assign->id;
        $graderecord->userid = $user->id;
        $graderecord->timecreated = $now;
        $graderecord->timemodified = $now;
        $graderecord->grader = $USER->id;
        $graderecord->grade = $gradevalue;
        $graderecord->attemptnumber = 0;
        $gradeid = $DB->insert_record('assign_grades', $graderecord);
    }

    // Write (or update) the feedback comment, linked to that grade record.
    $feedbackrecord = $DB->get_record('assignfeedback_comments', ['assignment' => $assign->id, 'grade' => $gradeid]);
    if ($feedbackrecord) {
        $feedbackrecord->commenttext = $feedbacktext;
        $feedbackrecord->commentformat = FORMAT_PLAIN;
        $DB->update_record('assignfeedback_comments', $feedbackrecord);
    } else {
        $feedbackrecord = new stdClass();
        $feedbackrecord->assignment = $assign->id;
        $feedbackrecord->grade = $gradeid;
        $feedbackrecord->commenttext = $feedbacktext;
        $feedbackrecord->commentformat = FORMAT_PLAIN;
        $DB->insert_record('assignfeedback_comments', $feedbackrecord);
    }

    // Push the grade into Moodle's gradebook so it shows up in Grades / reports.
    $gradeitem = new stdClass();
    $gradeitem->userid = $user->id;
    $gradeitem->rawgrade = $gradevalue;
    $gradeitem->feedback = $feedbacktext;
    $gradeitem->feedbackformat = FORMAT_PLAIN;
    assign_grade_item_update($assign, $gradeitem);
}

// ---------------------------------------------------------------------
// 3. RUN
// ---------------------------------------------------------------------

mtrace("=== LJA synthetic data generation starting ===\n");

mtrace("Creating subjects...");
$courserecords = [];
foreach ($subjects as $subject) {
    $courserecords[$subject['shortname']] = get_or_create_course($subject);
}

mtrace("\nCreating students...");
$userrecords = [];
foreach ($students as $student) {
    $userrecords[$student['username']] = get_or_create_student($student);
}

mtrace("\nEnrolling students per subject, creating assignments, and writing grades + feedback...");
$numassignments = 4;
foreach ($courserecords as $shortname => $course) {
    mtrace("Subject: $shortname");

    // Only seed students assigned to this subject; preserve other records.
    $enrolledstudents = [];
    foreach ($students as $student) {
        $user = $userrecords[$student['username']];
        $belongs = in_array($shortname, $student['subjects'], true);
        try {
            if ($belongs) {
                enrol_student($course, $user);
                $enrolledstudents[] = $student;
            } else {
                // Preserve existing enrolments and records outside this seed scope.
            }
        } catch (\Throwable $e) {
            mtrace("  !! Could not update enrolment for {$user->username} in $shortname: " . $e->getMessage());
        }
    }

    for ($assignmentnum = 1; $assignmentnum <= $numassignments; $assignmentnum++) {
        try {
            [$assign, $cm] = get_or_create_assignment($course, $assignmentnum, $numassignments);
        } catch (\Throwable $e) {
            mtrace("  !! Could not create/find assignment $assignmentnum for $shortname: " . $e->getMessage());
            global $DB;
            if ($DB->is_transaction_started()) {
                $DB->force_transaction_rollback();
            }
            continue;
        }

        foreach ($enrolledstudents as $student) {
            $user = $userrecords[$student['username']];
            try {
                ensure_submission_exists($assign, $user);

                $bands = $student['bands'];
                $improving = !empty($student['improving']);
                $grade = compute_grade($bands, $band_score, $improving, $assignmentnum, $numassignments);
                $feedback = generate_feedback($bands, $competencies, $feedback_templates, $improving, $assignmentnum, $numassignments, $student['username']);

                save_assignment_grade($assign, $cm, $course, $user, $grade, $feedback);
                mtrace("  {$user->username} — Assignment $assignmentnum: grade $grade");
            } catch (\Throwable $e) {
                mtrace("  !! Failed for {$user->username} in $shortname (Assignment $assignmentnum): " . $e->getMessage());
            }
        }
    }
}

mtrace("\n=== Done. ===");