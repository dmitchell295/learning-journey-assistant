# CBLS-71 - AI Input and Output for Dashboard

This file shows what data the AI needs and what it sends back to the dashboard.

## Input

Example:

```json
{
  "student_id": 6,
  "subject_code": "CSE3CYB",
  "assignment": "CSE3CYB Assignment 1",
  "competency": "Technical Implementation",
  "learning_outcome": "CSE3CYB-LO1",
  "rubric_criterion": "Example rubric criterion",
  "rubric_level": "Adequate",
  "feedback": "The student shows a reasonable understanding but needs more technical detail."
}
```

The main input fields are:

- `student_id`: student ID
- `subject_code`: subject code
- `assignment`: assignment name
- `competency`: related competency
- `learning_outcome`: related learning outcome
- `rubric_criterion`: rubric criterion
- `rubric_level`: Weak, Adequate or Strong
- `feedback`: marker feedback

The learning outcome and competency should use the mapping agreed by the team. They should not be guessed from the rubric order.

## Output

Example:

```json
{
  "student_id": 6,
  "subject_code": "CSE3CYB",
  "learning_outcome": "CSE3CYB-LO1",
  "competency": "Technical Implementation",
  "mastery_estimate": "Partially Achieved",
  "identified_gap": "Needs more technical detail.",
  "evidence": "The feedback says the student needs more technical detail."
}
```

The output fields are:

- `student_id`: student ID
- `subject_code`: subject code
- `learning_outcome`: learning outcome being checked
- `competency`: related competency
- `mastery_estimate`: Not Yet Achieved, Partially Achieved or Achieved
- `identified_gap`: main area the student needs to improve
- `evidence`: short reason based on the rubric or feedback

## Dashboard

The dashboard can use `mastery_estimate` to show the student's current level.

`identified_gap` can be shown as an area to improve, and `evidence` gives a short reason for the result.

The backend API is still being finished, so the API route may change later. The input and output fields can be updated if needed after the backend is confirmed.
