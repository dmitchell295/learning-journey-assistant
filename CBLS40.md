# CBLS-40 - AI Input and Output Format

## What this is for

This is the basic JSON format for the AI gap-detection prototype.

The input gives the AI the student's rubric result and feedback. The output gives back a mastery estimate for the related learning outcome and shows the main gap that was found.

## Input

The input will include:

* `student_id`
* `subject_code`
* `assignment`
* `competency`
* `learning_outcome`
* `rubric_criterion`
* `rubric_level`
* `feedback`

Example:

```json
{
  "student_id": 3,
  "subject_code": "CSE1DBS",
  "assignment": "CSE1DBS Assignment 1",
  "competency": "Technical Implementation",
  "learning_outcome": "CSE1DBS-LO1",
  "rubric_criterion": "Query Design & Normalization",
  "rubric_level": "Adequate",
  "feedback": "[Technical Implementation] The solution is mostly correct but needs stronger technical detail."
}
```

## Output

The output should show the mastery estimate and the main learning gap.

Example:

```json
{
  "student_id": 3,
  "subject_code": "CSE1DBS",
  "learning_outcome": "CSE1DBS-LO1",
  "competency": "Technical Implementation",
  "mastery_estimate": "Partially Achieved",
  "identified_gap": "Technical detail needs improvement",
  "evidence": {
    "rubric_level": "Adequate",
    "feedback": "The solution is mostly correct but needs stronger technical detail."
  }
}
```

## Mastery levels

For the current prototype, the mastery estimate will use:

* `Not Yet Achieved`
* `Partially Achieved`
* `Achieved`

The rubric result uses:

* `Weak`
* `Adequate`
* `Strong`

## Notes

* The prototype is using synthetic student data.
* The current Moodle data script does not pull rubric or learning outcome data yet.
* The format can be updated later if the AI or Backend needs any extra fields.
* For Sprint 2, the aim is to keep the format simple for the first prototype.


