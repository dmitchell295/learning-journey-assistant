"""
save_results.py - writes live AI mastery results into the local database,
so the dashboard (/assessments) shows real AI output instead of seed data.

Called from live_ai_data.py after each estimate_mastery() call.
One Assessment row is saved per rubric criterion:
  identified_gap   = criterion / learning outcome name
  mastery_score    = AI score (0-100)
  mastery_estimate = category derived from mastery_score
  feedback_text    = AI evidence text
  grade            = overall assignment grade from Moodle
"""

from app import app
from models import db, Student, Subject, Assignment, Assessment

# Category thresholds - team to confirm
ACHIEVED_MIN = 75
PARTIAL_MIN = 50


def score_to_category(score):
    if score is None:
        return None
    if score >= ACHIEVED_MIN:
        return "Achieved"
    if score >= PARTIAL_MIN:
        return "Partially Achieved"
    return "Not Yet Achieved"


def _get_or_create(model, defaults=None, **filters):
    obj = model.query.filter_by(**filters).first()
    if obj is None:
        obj = model(**filters, **(defaults or {}))
        db.session.add(obj)
        db.session.flush()  # assigns obj.id without committing yet
    return obj


def save_student_results(moodle_userid, grade, results,
                         subject_code="CSE3CYB",
                         assignment_name="CSE3CYB Assignment 1"):
    """results: the list returned by estimate_mastery()."""
    with app.app_context():
        student = _get_or_create(Student, name=f"Moodle user {moodle_userid}")
        subject = _get_or_create(Subject, code=subject_code,
                                 defaults={"name": subject_code})
        assignment = _get_or_create(Assignment, subject_id=subject.id,
                                    name=assignment_name)

        # Re-running replaces this student's previous results for this
        # assignment instead of duplicating them
        Assessment.query.filter_by(student_id=student.id,
                                   assignment_id=assignment.id).delete()

        for r in results:
            score = r.get("mastery_score")
            score = int(score) if score is not None else None
            db.session.add(Assessment(
                student_id=student.id,
                assignment_id=assignment.id,
                feedback_text=r.get("evidence"),
                grade=float(grade) if grade is not None else None,
                mastery_score=score,
                mastery_estimate=score_to_category(score),
                identified_gap=r.get("learning_outcome"),
            ))

        db.session.commit()
        print(f"Saved {len(results)} results for Moodle user {moodle_userid}")