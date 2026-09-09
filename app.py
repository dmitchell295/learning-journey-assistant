from flask import Flask
from flask_cors import CORS
from models import db, Student, Subject, Assessment, RubricCriterion

app = Flask(__name__)
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///learning_journey.db'
CORS(app)
db.init_app(app)

with app.app_context():
    db.create_all()


@app.route("/")
def home():
    return {"message": "Learning Journey Assistant API is running"}


@app.route("/subjects")
def get_subjects():
    subjects = Subject.query.all()
    return {
        "subjects": [
            {
                "id": subject.id,
                "code": subject.code,
                "name": subject.name
            }
            for subject in subjects
        ]
    }


@app.route("/students")
def get_students():
    students = Student.query.all()
    return {
        "students": [
            {
                "id": student.id,
                "name": student.name
            }
            for student in students
        ]
    }


@app.route("/assessments")
def get_assessments():
    assessments = Assessment.query.all()
    return {
        "assessments": [
            {
                "id": assessment.id,
                "student_id": assessment.student_id,
                "subject_id": assessment.subject_id,
                "feedback_text": assessment.feedback_text,
                "mastery_score": assessment.mastery_score
            }
            for assessment in assessments
        ]
    }

@app.route("/subject/<int:subject_id>")
def get_subject(subject_id):
    subject = db.session.get(Subject, subject_id)

    if subject is None:
        return {"error": "Subject not found"}, 404

    return {
        "id": subject.id,
        "code": subject.code,
        "name": subject.name
    }


@app.route("/assessment/<int:assessment_id>")
def get_assessment(assessment_id):
    assessment = db.session.get(Assessment, assessment_id)

    if assessment is None:
        return {"error": "Assessment not found"}, 404

    return {
        "id": assessment.id,
        "student_id": assessment.student_id,
        "subject_id": assessment.subject_id,
        "feedback_text": assessment.feedback_text,
        "mastery_score": assessment.mastery_score
    }


@app.route("/rubric/<int:criterion_id>")
def get_rubric(criterion_id):
    criterion = db.session.get(RubricCriterion, criterion_id)

    if criterion is None:
        return {"error": "Rubric criterion not found"}, 404

    return {
        "id": criterion.id,
        "outcome_id": criterion.outcome_id,
        "description": criterion.description
    }

if __name__ == "__main__":
    app.run(debug=True, port=5000)