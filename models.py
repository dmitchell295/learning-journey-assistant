from flask_sqlalchemy import SQLAlchemy

db = SQLAlchemy()

class Student(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)

class Subject(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    code = db.Column(db.String(20), nullable=False)
    name = db.Column(db.String(100), nullable=False)

class Competency(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)

class LearningOutcome(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    subject_id = db.Column(db.Integer, db.ForeignKey('subject.id'), nullable=False)
    competency_id = db.Column(db.Integer, db.ForeignKey('competency.id'), nullable=False)
    description = db.Column(db.String(255), nullable=False)

class RubricCriterion(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    outcome_id = db.Column(db.Integer, db.ForeignKey('learning_outcome.id'), nullable=False)
    description = db.Column(db.String(255), nullable=False)

class Enrolment(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    student_id = db.Column(db.Integer, db.ForeignKey('student.id'), nullable=False)
    subject_id = db.Column(db.Integer, db.ForeignKey('subject.id'), nullable=False)

class Assignment(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    subject_id = db.Column(db.Integer, db.ForeignKey('subject.id'), nullable=False)
    name = db.Column(db.String(150), nullable=False)
    subject = db.relationship('Subject')

class Assessment(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    student_id = db.Column(db.Integer, db.ForeignKey('student.id'), nullable=False)
    assignment_id = db.Column(db.Integer, db.ForeignKey('assignment.id'), nullable=False)
    feedback_text = db.Column(db.Text)
    grade = db.Column(db.Float)
    mastery_estimate = db.Column(db.String(30))  # "Not Yet Achieved" | "Partially Achieved" | "Achieved"
    identified_gap = db.Column(db.Text)
    assignment = db.relationship('Assignment')

class RubricResult(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    assessment_id = db.Column(db.Integer, db.ForeignKey('assessment.id'), nullable=False)
    criterion_id = db.Column(db.Integer, db.ForeignKey('rubric_criterion.id'), nullable=False)
    level = db.Column(db.String(20))  # "Weak" | "Adequate" | "Strong"
    points = db.Column(db.Integer)
