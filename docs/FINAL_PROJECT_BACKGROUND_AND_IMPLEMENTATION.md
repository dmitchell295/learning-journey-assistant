CBLS-157: Write project objectives, background and methodology

1. Project Background
The Learning Journey Assistant (LJA) is designed to help students understand their academic progress by bringing together assessment information, learning outcomes, rubric results and AI-assisted mastery estimates.
The system uses a Flask backend to provide REST API endpoints and a React frontend to display assessment information in a student-friendly dashboard. Moodle is used as the source of academic information, with the backend providing the integration layer between Moodle data and the frontend application.
2. Project Objectives
The main objectives of the Learning Journey Assistant are to:
- Retrieve relevant academic and assessment information from Moodle.
- Provide a backend API for accessing student, subject and assessment information.
- Connect assessment results with learning outcomes and rubric information.
- Use AI-assisted analysis to estimate student mastery.
- Identify areas of strength and potential learning gaps.
- Present the results through an accessible React dashboard.
- Provide a foundation that can be deployed as a complete connected application rather than only running locally.
3. System Methodology
The project followed an iterative Agile/Scrum methodology.
Work was divided into sprints, with each sprint addressing a specific part of the system. Development progressed from initial prototyping and data modelling through backend API development, Moodle integration, AI integration, frontend integration, testing and final deployment/reporting.
The overall process can be described as:
Moodle → Backend/Moodle API layer → Flask REST API → React frontend → Student dashboard
The backend contains models representing entities such as:
- Student
- Subject
- Competency
- Learning Outcome
- Rubric Criterion
- Enrolment
- Assignment
- Assessment
- Rubric Result
The Flask application exposes endpoints such as /subjects, /students, /assessments, and Moodle-related endpoints for courses, grades, assignments, outcomes and rubrics.
On the frontend, api.js provides a central API layer. useDashboardData.js retrieves assessments and subjects concurrently and transforms the returned data into dashboard-ready information.
4. Data Processing Methodology
The frontend does not directly display the raw backend response.
The useDashboardData hook:
1. Requests assessments and subjects.
2. Creates a subject lookup using the subject ID.
3. Enriches each assessment with its subject name.
4. Converts the mastery category into a percentage.
5. Calculates average mastery.
6. Identifies assessments that are on-track or below the threshold.
7. Sorts assessments to identify strengths and gaps.
8. Passes the processed data to dashboard components.
The current mastery mapping is:
Mastery category	Display percentage
Achieved	95%
Partially Achieved	60%
Not Yet Achieved	25%


The dashboard then uses these processed values for components such as Understanding Level and Progress Trends.


CBLS-158: Document implementation and system development

1. Backend Implementation
The backend is implemented using Python and Flask.
The application initialises SQLAlchemy and exposes REST endpoints for accessing system data.
For example:
GET /subjects
GET /students
GET /assessments
GET /assessment/<id>
GET /subject/<id>
GET /rubric/<id>

The backend also contains Moodle integration endpoints:
GET /moodle/courses
GET /moodle/course/<id>/outcomes
GET /moodle/course/<id>/grades
GET /moodle/course/<id>/assignments
GET /moodle/rubric/<cmid>
GET /moodle/rubric/definition/<id>/instances
GET /moodle/assignment/<id>/grades

This provides a separation between the external Moodle service and the React frontend.
2. Moodle Integration
moodle_client.py acts as a thin wrapper around the Moodle REST web service.
The general process is:
React Frontend
      ↓
Flask API
      ↓
moodle_client.py
      ↓
Moodle REST Web Service
      ↓
Moodle data

The client provides functions for retrieving:
- courses
- assignments
- submissions
- grades
- grade items
- grading definitions
- grading instances
- course outcomes
Errors returned by Moodle are converted into MoodleAPIError, allowing the Flask routes to return an appropriate error response.
3. AI Mastery Estimation
The project also contains an AI mastery estimation component.
mastery_estimator.py takes rubric and feedback information and sends it to an Anthropic model.
The intended output contains:
learning_outcome
mastery_score
evidence

The response is parsed as JSON and validated against the expected structure before being returned.
This provides an AI-assisted mechanism for converting qualitative assessment feedback into a structured mastery estimate.
Important: Don't claim that this is already fully integrated into the live Moodle pipeline unless your team has actually completed that connection.
4. Frontend Implementation
The frontend is implemented using React.
api.js provides the central communication layer between the frontend and Flask backend.
For example:
getSubjects()
getAssessments()
getStudents()
getAssessment(id)

The frontend therefore does not need to implement separate fetch logic inside every component.
5. Dashboard Data Processing
useDashboardData.js acts as the main data-processing layer for the dashboard.
It retrieves:
Assessments
Subjects

using:
Promise.all(...)

It then enriches the assessment data with:
- subject name
- mastery percentage
- mastery label
It calculates:
- average mastery
- subjects on track
- gaps flagged
- top strengths
- learning gaps
This processed object is then supplied to the dashboard components.
6. User Interface
The current dashboard includes components such as:
Understanding Level
Displays:
CURRENT UNDERSTANDING

and the calculated average mastery percentage using a progress bar.
Progress Trends
Displays the number of assessments and calculates the average mastery across them.
The frontend also uses normalizeMastery() to convert categorical mastery results into values suitable for visualisation.
