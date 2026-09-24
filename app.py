from flask import Flask
from flask_cors import CORS
from models import db, Student, Subject, Assessment, RubricCriterion
from moodle_client import get_courses, get_course_outcomes, get_grade_items, get_assignments, get_grading_definitions, MoodleAPIError

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
            {"id": subject.id, "code": subject.code, "name": subject.name}
            for subject in subjects
        ]
    }


@app.route("/students")
def get_students():
    students = Student.query.all()
    return {
        "students": [
            {"id": student.id, "name": student.name}
            for student in students
        ]
    }


@app.route("/assessments")
def get_assessments():
    assessments = Assessment.query.all()
    return {
        "assessments": [
            {
                "id": a.id,
                "student_id": a.student_id,
                "subject_id": a.assignment.subject_id,
                "feedback_text": a.feedback_text,
                "grade": a.grade,
                "mastery_estimate": a.mastery_estimate,
                "identified_gap": a.identified_gap,
            }
            for a in assessments
        ]
    }


@app.route("/subject/<int:subject_id>")
def get_subject(subject_id):
    subject = db.session.get(Subject, subject_id)
    if subject is None:
        return {"error": "Subject not found"}, 404
    return {"id": subject.id, "code": subject.code, "name": subject.name}


@app.route("/assessment/<int:assessment_id>")
def get_assessment(assessment_id):
    a = db.session.get(Assessment, assessment_id)
    if a is None:
        return {"error": "Assessment not found"}, 404
    return {
        "id": a.id,
        "student_id": a.student_id,
        "subject_id": a.assignment.subject_id,
        "feedback_text": a.feedback_text,
        "grade": a.grade,
        "mastery_estimate": a.mastery_estimate,
        "identified_gap": a.identified_gap,
    }


@app.route("/rubric/<int:criterion_id>")
def get_rubric(criterion_id):
    criterion = db.session.get(RubricCriterion, criterion_id)
    if criterion is None:
        return {"error": "Rubric criterion not found"}, 404
    return {
        "id": criterion.id,
        "outcome_id": criterion.outcome_id,
        "description": criterion.description,
    }


@app.route("/student/<int:student_id>/summary")
def student_summary(student_id):
    student = db.session.get(Student, student_id)
    if student is None:
        return {"error": "Student not found"}, 404

    assessments = Assessment.query.filter_by(student_id=student_id).all()
    strengths = [a.identified_gap for a in assessments if a.mastery_estimate == "Achieved"]
    gaps = [a.identified_gap for a in assessments if a.mastery_estimate in ("Not Yet Achieved", "Partially Achieved")]

    return {
        "student_id": student.id,
        "student_name": student.name,
        "strengths": strengths,
        "gaps": gaps,
        "assessment_count": len(assessments),
    }

@app.route("/moodle/courses")
def moodle_courses():
    try:
        courses = get_courses()
        return {"courses": courses}
    except MoodleAPIError as e:
        return {"error": str(e)}, 502


@app.route("/moodle/course/<int:course_id>/outcomes")
def moodle_course_outcomes(course_id):
    try:
        outcomes = get_course_outcomes(course_id)
        return {"outcomes": outcomes}
    except MoodleAPIError as e:
        return {"error": str(e)}, 502


@app.route("/moodle/course/<int:course_id>/grades")
def moodle_course_grades(course_id):
    try:
        grades = get_grade_items(course_id)
        return {"grades": grades}
    except MoodleAPIError as e:
        return {"error": str(e)}, 502


@app.route("/moodle/course/<int:course_id>/assignments")
def moodle_course_assignments(course_id):
    try:
        assignments = get_assignments([course_id])
        return {"assignments": assignments}
    except MoodleAPIError as e:
        return {"error": str(e)}, 502

if __name__ == "__main__":
    app.run(debug=True, port=5000)
