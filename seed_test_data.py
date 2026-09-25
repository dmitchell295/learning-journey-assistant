"""
LOCAL TESTING ONLY - do not commit.
Inserts fake rows matching the current schema (Student, Subject,
Assignment, Assessment) so CBLS-74/75 has something real to render.
"""

from app import app
from models import db, Student, Subject, Assignment, Assessment

with app.app_context():
    s = Student(name="Test Student")
    subj = Subject(code="CSE1DBS", name="Database Systems")
    db.session.add_all([s, subj])
    db.session.commit()

    a1 = Assignment(subject_id=subj.id, name="Assignment 1")
    db.session.add(a1)
    db.session.commit()

    db.session.add_all([
        Assessment(student_id=s.id, assignment_id=a1.id,
                   feedback_text="Strong normalization skills.",
                   grade=85, mastery_estimate="Achieved",
                   identified_gap=""),
        Assessment(student_id=s.id, assignment_id=a1.id,
                   feedback_text="Needs work on query optimization.",
                   grade=35, mastery_estimate="Not Yet Achieved",
                   identified_gap="Query optimization"),
    ])
    db.session.commit()
    print("Seeded test data.")