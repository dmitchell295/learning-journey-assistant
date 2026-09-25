# LJA Moodle API notes

Hi team,

The Moodle API is now working on Azure. You can pull the current assignment, grade, rubric and learning outcome data through it. I tested the rubric data across all 8 subjects and 32 project assignments.

These are the Moodle API details. We still need to confirm the backend endpoints and how the AI engine will connect to them.

## URL and token

Use POST requests to:

```text
https://lja-moodle.australiaeast.cloudapp.azure.com/webservice/rest/server.php
```

Use `application/x-www-form-urlencoded` for the request body. Each request needs:

- `wstoken`: the Python user API token, which I’ll send separately.
- `wsfunction`: the function you want to call.
- `moodlewsrestformat`: `json`.
- The parameters for that function, listed below.

Keep the token on the backend in an environment variable. Please don’t put it in GitHub or frontend code. Your Moodle website login and this API token are separate.

## What I checked

- 8 subjects with 4 project assignments each: 32 assignments.
- 80 student rubric results, with valid selected levels and links to the student grade records.
- 32 learning outcomes, with 4 per subject, including their descriptions and scale labels.

The rubric check and outcome check both finished with no validation issues.

You may still see permission warnings for module IDs 2 and 3. These are the old Lab Quiz and Assignment 1 activities in CSE2OOP, which are marked for deletion. They aren’t part of the 32 project assignments. The database has 82 active rubric records, while the project API check returned 80. Please check any other warnings rather than ignoring them.

## Functions to use

| Function | What it returns | Example parameters |
|---|---|---|
| `mod_assign_get_assignments` | Courses and assignments, including assignment ID and course-module ID | No extra parameters used in our test |
| `core_grading_get_definitions` | Rubric criteria, available levels, descriptions and points | `cmids[0]=26`, `areaname=submissions`, `activeonly=1` |
| `core_grading_get_gradingform_instances` | The levels selected for each marked rubric | `definitionid=99`, `since=0` |
| `mod_assign_get_grades` | Grade-record ID, student ID, attempt number and assignment grade | `assignmentids[0]=10`, `since=0` |
| `gradereport_user_get_grade_items` | Gradebook items and feedback | `courseid=9`, `userid=6` |
| `local_ljaoutcomes_get_course_outcomes` | Learning outcome definitions and scale labels for a course | `courseid=9` |

`core_course_get_courses` and `mod_assign_get_submissions` are also enabled. Check their arguments in Moodle’s API documentation before using them; they weren’t separately re-tested in this round.

For list parameters, keep the brackets, e.g. `assignmentids[0]`. Check the JSON response for `exception` and `warnings`, even when the HTTP request succeeds. I tested full pulls with `since=0`; incremental updates still need testing.

## Course IDs

| ID | Code | Subject |
|---|---|---|
| 2 | CSE2OOP | Object-Oriented Programming |
| 3 | CSE2DWD | Database & Web Development |
| 4 | CSE1ALG | Algorithms & Data Structures |
| 5 | CSE2NET | Computer Networks |
| 6 | CSE3AIP | AI Principles |
| 7 | CSE2SEP | Software Engineering Practice |
| 8 | CSE1DBS | Database Systems |
| 9 | CSE3CYB | Cybersecurity Fundamentals |

Discover assignment IDs dynamically; do not infer them from course IDs or assignment names.

## How the IDs connect

The assignment ID and course-module ID (`cmid`) are different, so use the IDs returned by the API rather than guessing them.

1. Get the assignments with `mod_assign_get_assignments`.
2. Use an assignment’s `cmid` to get its rubric definition.
3. Use the rubric definition’s `id` to get its student rubric results.
4. Get assignment grades using the assignment ID.
5. Match the rubric result’s `itemid` to the grade record’s `id`. That grade record contains the student’s `userid`.
6. Match each selected `levelid` to its level in the rubric definition to get the points and description.

`raterid` is the person marking the work, not the student. Keep `attemptnumber` from the grade response too.

For example, CSE3CYB Assignment 1 has assignment ID **10**, course-module ID **26**, and rubric definition ID **99**.

| Rubric result ID | Grade record ID | Student ID | Rubric points | Assignment grade |
|---|---|---|---|---|
| 157 | 40 | 6 | 10 + 10 + 10 + 10 = 40 | 44 |
| 158 | 41 | 7 | 25 + 18 + 10 + 18 = 71 | 64 |

The rubric total and assignment grade are separate values in our synthetic data. Please keep both. Neither is automatically the mastery score. The rubric response has `rawgrade: null` in these examples; that doesn’t mean the grade is zero.

Some descriptions contain HTML entities such as `&amp;`. Handle these when displaying the text, and render any HTML safely.

## Learning outcomes

I added a small Moodle plugin because the grade endpoint didn’t return the full course outcome definitions.

Use `local_ljaoutcomes_get_course_outcomes` with a course ID. For course 9, it returns outcomes 29–32, named CSE3CYB-LO1 to CSE3CYB-LO4, with descriptions and this scale:

| Value | Label |
|---|---|
| 1 | Not Yet Achieved |
| 2 | Partially Achieved |
| 3 | Achieved |

These are the available scale options. They don’t tell us which level a student has achieved, and they aren’t mastery percentages. No outcome grade items were returned for the student we tested in course 9.

The API also doesn’t currently return an explicit link from each rubric criterion to an outcome or one of the four competencies. We need to agree on that mapping rather than assume criterion 1 always means LO1.

## Quick Python test

Set the `LJA_TOKEN` environment variable to the token I send you, then run:

```python
import os, json, urllib.parse, urllib.request
endpoint = "https://lja-moodle.australiaeast.cloudapp.azure.com/webservice/rest/server.php"
payload = urllib.parse.urlencode({
    "wstoken": os.environ["LJA_TOKEN"],
    "wsfunction": "local_ljaoutcomes_get_course_outcomes",
    "moodlewsrestformat": "json",
    "courseid": 9,
}).encode()
with urllib.request.urlopen(endpoint, data=payload, timeout=60) as response:
    result = json.load(response)
if "exception" in result:
    raise RuntimeError(result.get("message", "Moodle API error"))
print(json.dumps(result, indent=2))
```

## Access checks

The working token belongs to `pythonuser` in LJA Service. The tested account has Non-editing teacher and Web Service User course roles. We also allowed Manage advanced grading methods on Web Service User because the built-in rubric-results function requires it. That permission has broader grading-management rights, although our service only has read functions enabled.

The custom plugin checks course access and requires `moodle/course:view` and `gradereport/user:view`. Calling it directly as unenrolled Dylan was rejected with “Course or activity not accessible.” His REST request was blocked earlier by web-service access checks, so that wasn’t an isolated REST test of course permissions.

Plugin details: `local_ljaoutcomes`, version `2026091000`, release `0.1.0` beta, on Moodle 3.6.2 with PHP 7.3.1. It reads existing outcomes and scales without creating tables or changing data.

## What we still need to do

- Dylan: confirm the backend endpoints and update `models.py` for enrolments, assignments, competencies, rubric levels/results and separate assignment grades.
- Backend and AI: agree on the mastery calculation and the rubric-to-outcome/competency mapping.
- Me: verify student-subject enrolments through an API function. Matching grades to students doesn’t cover students who have no grades.
- Me: push the plugin, data scripts and API checks to GitHub for CBLS-61, without tokens.
- Me: fix `setup_rubrics.php` so new synthetic rubric results are created correctly. The existing incomplete records were repaired and assignment grades stayed unchanged.
- Me: finish the temporary test-account cleanup and Teacher enrolments, and address the Moodle cron and PHP error-display notices.

Please try the sample call and let me know whether it works on your side. I’ll share the token privately. This document covers the Moodle data connection; the backend API details still need to be confirmed separately.


Packaging update (CBLS-61): revised seed scripts and the expanded live pull are included with this handoff. See DATA_SCRIPTS.md for changes and test limits. The revised seed scripts have not been run on the hosted site.
