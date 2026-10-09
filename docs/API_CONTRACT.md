## Learning Journey Assistant — Backend API Contract

Base URL (local dev): `http://localhost:5000`
Base URL (deployed): https://learning-journey-assistant-jf6l.onrender.com

All endpoints are GET. Example JSON below is illustrative: field names come from `app.py`, but the values are made up.

### Database endpoints (what the dashboard reads)

| Endpoint | Description |
|---|---|
| `/subjects` | All subjects: `id`, `code`, `name` |
| `/subject/<id>` | One subject (404 if not found) |
| `/students` | All students: `id`, `name` |
| `/assessments` | All assessments, including AI results (see below) |
| `/assessment/<id>` | One assessment. **Does not include `mastery_score` or `assignment_name`**, use `/assessments` for those |
| `/student/<id>/summary` | Strengths, gaps and assessment count for one student (404 if not found) |
| `/rubric/<criterion_id>` | One rubric criterion from the database. Nothing in the current pipeline populates this table, so expect 404 |

**`/assessments` response:**
```json
{
  "assessments": [
    {
      "id": 1,
      "student_id": 1,
      "subject_id": 1,
      "assignment_name": "CSE3CYB Assignment 1",
      "feedback_text": "AI evidence text for this learning outcome",
      "grade": 44.0,
      "mastery_score": 62,
      "mastery_estimate": "Partially Achieved",
      "identified_gap": "Learning outcome name from the AI engine"
    }
  ]
}
```

**Mastery fields:**
- `grade` is the overall assignment grade from Moodle.
- `mastery_score` is an integer 0–100 produced by the AI engine, one row per rubric criterion.
- `mastery_estimate` is derived from `mastery_score` in `save_results.py`: 75 or above is "Achieved", 50–74 is "Partially Achieved", below 50 is "Not Yet Achieved". These thresholds are marked "team to confirm" in the code.
- `identified_gap` holds the learning outcome name from the AI output, not a database link to an outcome.

**`/student/<id>/summary` response:**
```json
{
  "student_id": 1,
  "student_name": "Moodle user 6",
  "strengths": ["identified_gap values where mastery_estimate is Achieved"],
  "gaps": ["identified_gap values where it is Partially or Not Yet Achieved"],
  "assessment_count": 4
}
```

### Live Moodle endpoints (proxied through `moodle_client.py`)

| Endpoint | Response key | Moodle function | Notes |
|---|---|---|---|
| `/moodle/courses` | `courses` | `core_course_get_courses` | All courses |
| `/moodle/course/<course_id>/outcomes` | `outcomes` | `local_ljaoutcomes_get_course_outcomes` | Learning outcomes and scale labels |
| `/moodle/course/<course_id>/grades` | `grades` | `gradereport_user_get_grade_items` | Gradebook items and feedback |
| `/moodle/course/<course_id>/assignments` | `assignments` | `mod_assign_get_assignments` | Gives assignment `id` and `cmid` |
| `/moodle/rubric/<cmid>` | `rubric` | `core_grading_get_definitions` | Takes the **course-module ID (cmid)**, not an area ID. Returns criteria, levels and points |
| `/moodle/rubric/definition/<definition_id>/instances` | `instances` | `core_grading_get_gradingform_instances` | Student rubric results (selected levels) for a rubric definition |
| `/moodle/assignment/<assignment_id>/grades` | `grades` | `mod_assign_get_grades` | Grade records: record `id`, `userid`, `attemptnumber` |

**How the IDs connect** (example: CSE3CYB Assignment 1 is assignment 10, cmid 26, rubric definition 99):
1. `/moodle/course/<course_id>/assignments` gives the assignment `id` and `cmid`.
2. `/moodle/rubric/<cmid>` gives the rubric definition and its `id`.
3. `/moodle/rubric/definition/<definition_id>/instances` gives each student's selected levels. Each result's `itemid` matches a grade record `id`.
4. `/moodle/assignment/<assignment_id>/grades` links that grade record `id` to the student's `userid`.

**Errors (all `/moodle/...` routes):** HTTP 502 with `{ "error": "<message>" }` when the Moodle call fails or returns an exception.

### Known open items
- **Rubric-to-outcome/competency mapping** is not stored. The live pipeline doesn't write `RubricCriterion` or `RubricResult` rows; the AI output names the outcome instead.
- **Mastery thresholds** (75 / 50) are provisional.
- **Student enrolments** are not reliably available: matching via grade records misses students with no grades.
- **Coverage:** `live_ai_data.py` is currently hardcoded to one assignment (CSE3CYB Assignment 1), so AI-backed data covers that assignment only.
- Always take IDs (assignment ID, `cmid`, definition ID) from API responses, never infer them from names or course IDs.
