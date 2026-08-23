from flask_sqlalchemy import SQLAlchemy

db = SQLAlchemy()

class Student(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)

class Subject(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    code = db.Column(db.String(20), nullable=False)
    name = db.Column(db.String(100), nullable=False)

class LearningOutcome(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    subject_id = db.Column(db.Integer, db.ForeignKey('subject.id'), nullable=False)
    description = db.Column(db.String(255), nullable=False)

class RubricCriterion(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    outcome_id = db.Column(db.Integer, db.ForeignKey('learning_outcome.id'), nullable=False)
    description = db.Column(db.String(255), nullable=False)

class Assessment(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    student_id = db.Column(db.Integer, db.ForeignKey('student.id'), nullable=False)
    subject_id = db.Column(db.Integer, db.ForeignKey('subject.id'), nullable=False)
    feedback_text = db.Column(db.Text)
    mastery_score = db.Column(db.Float)