## Learning Journey Assistant — Backend API Contract

Base URL (local dev): `http://localhost:5000`

### Internal database endpoints (SQLite — synthetic dashboard data)

| Endpoint | Method | Description |
|---|---|---|
| `/subjects` | GET | List all subjects |
| `/subject/<id>` | GET | Single subject by ID |
| `/students` | GET | List all students |
| `/assessments` | GET | List all assessments |
| `/assessment/<id>` | GET | Single assessment by ID |
| `/rubric/<criterion_id>` | GET | Single rubric criterion |
| `/student/<id>/summary` | GET | Dashboard-ready summary: strengths, gaps, assessment count for one student |

**Example — `/student/1/summary`:**
```json
{
  "student_id": 1,
  "student_name": "Amara Osei",
  "strengths": ["Strong grasp of normalisation"],
  "gaps": ["Struggles with query optimisation"],
  "assessment_count": 4
}
```

### Live Moodle endpoints (proxied through `moodle_client.py`)

| Endpoint | Method | Wraps Moodle function | Description |
|---|---|---|---|
| `/moodle/courses` | GET | `core_course_get_courses` | All courses on the Moodle instance |
| `/moodle/course/<course_id>/outcomes` | GET | `local_ljaoutcomes_get_course_outcomes` | Learning outcomes + scale labels for a course |
| `/moodle/course/<course_id>/grades` | GET | `gradereport_user_get_grade_items` | Gradebook items/feedback for a course |
| `/moodle/course/<course_id>/assignments` | GET | `mod_assign_get_assignments` | Assignments for a course (returns `cmid`, assignment `id`) |
| `/moodle/rubric/<area_id>` | GET | `core_grading_get_definitions` | Rubric criteria, levels, points for a grading area |
| `/moodle/assignment/<assignment_id>/grades` | GET | `mod_assign_get_grades` | Grade records for an assignment (grade record `id`, `userid`, `attemptnumber`) |

**Example — `/moodle/course/9/outcomes`:**
```json
{
  "outcomes": {
    "outcomes": [
      {
        "id": 29,
        "shortname": "CSE3CYB-LO1",
        "description": "...",
        "scale": [
          {"value": 1, "label": "Not Yet Achieved"},
          {"value": 2, "label": "Partially Achieved"},
          {"value": 3, "label": "Achieved"}
        ]
      }
    ]
  }
}
```

**Error shape (all `/moodle/...` routes):**
```json
{ "error": "get_courses failed: some Moodle exception message" }
```
Returned with HTTP 502 whenever the underlying Moodle call fails or returns an `exception`.

### Known open items (not yet resolved — check before assuming)
- **Rubric-to-outcome/competency mapping is not yet defined.** Rubric criteria and learning outcomes are currently separate; there's no confirmed link between "criterion 1" and a specific outcome. Don't hardcode an assumption here — check with Dylan/Karthik first.
- **Mastery calculation is not yet defined.** Rubric points, assignment grade, and the outcome scale (Not Yet Achieved/Partially Achieved/Achieved) are three separate values — none of them is automatically "the mastery score."
- **Student enrolments aren't fully reliable yet** — matching via grade records misses students with no grades. Don't assume grade presence = enrolment.
- IDs (assignment ID, `cmid`, rubric definition ID) must always come from the API responses themselves — never inferred or hardcoded from course IDs or names.
