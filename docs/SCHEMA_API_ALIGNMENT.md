# Schema and API Alignment

## Purpose

This document records the verification that the backend database schema,
synthetic test data, and API requirements are aligned.

## Schema Structure

The backend database defines the following main entities:

- Student
- Subject
- Competency
- LearningOutcome
- RubricCriterion
- Enrolment
- Assignment
- Assessment
- RubricResult

## API Alignment

The current API uses the following relationships:

Student → Assessment → Assignment → Subject

The `Assessment` model stores:

- student_id
- assignment_id
- feedback_text
- grade
- mastery_estimate
- identified_gap

The API uses the assessment's assignment relationship to obtain the
corresponding subject ID.

## Synthetic Test Data

The local test data creates:

- One test student
- One subject: CSE1DBS / Database Systems
- One assignment
- Two assessment records

The assessment records include different mastery states:

- Achieved with a grade of 85
- Not Yet Achieved with a grade of 35
- Query optimization identified as a skill gap

This structure supports the current dashboard's strengths, gaps and
assessment views.

## Verification

| Area | Schema | Synthetic Data | API | Status |
|---|---|---|---|---|
| Students | Student | Yes | `/students` | Aligned |
| Subjects | Subject | Yes | `/subjects` | Aligned |
| Assignments | Assignment | Yes | Assessment relationship | Aligned |
| Assessments | Assessment | Yes | `/assessments` | Aligned |
| Grades | Assessment.grade | Yes | `/assessments` | Aligned |
| Mastery | Assessment.mastery_estimate | Yes | `/assessments` | Aligned |
| Identified gaps | Assessment.identified_gap | Yes | `/assessments` | Aligned |
| Competencies | Competency | No seed data | Not directly exposed by current dashboard API | Partially covered |
| Learning outcomes | LearningOutcome | No seed data | Moodle/API support exists | Partially covered |
| Rubric criteria | RubricCriterion | No seed data | `/rubric/<criterion_id>` | Partially covered |
| Rubric results | RubricResult | No seed data | Not currently used by dashboard seed data | Partially covered |

## Limitations

The current synthetic dataset focuses on the data required for the
dashboard demonstration.

Competency, learning outcome and rubric-related models exist in the
database schema, but the current local seed script does not populate
those tables.

Therefore, the verification confirms the current synthetic dashboard
data path but does not represent full production competency or rubric
data validation.

## Conclusion

The database schema and current synthetic assessment data support the
API requirements used by the dashboard.

The core Student → Assessment → Assignment → Subject data path is
consistent with the current backend API implementation.
