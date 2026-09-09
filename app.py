from flask import Flask
from flask_cors import CORS
from models import db, Student, Subject, Assessment

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


if __name__ == "__main__":
    app.run(debug=True, port=5000)