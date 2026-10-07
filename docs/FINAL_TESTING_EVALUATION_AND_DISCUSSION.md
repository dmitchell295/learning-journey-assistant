# Learning Journey Assistant
## Final Testing, Evaluation and Discussion

## 1. Testing Overview

The Learning Journey Assistant was evaluated through end-to-end integration testing across the main system pipeline.

The primary workflow tested was:

Moodle → Backend API → AI/Assessment Processing → React Dashboard

The testing focused on verifying that information could be retrieved, processed and presented correctly across the connected components.

The main components evaluated were:

- Moodle REST API integration
- Moodle client
- Flask backend API
- Database models
- AI mastery component
- React frontend
- Dashboard data-processing layer
- Error handling

## 2. Integration Testing Results

The integration testing covered the following areas.

### Moodle Course Data Retrieval

The `/moodle/courses` endpoint was tested to verify that course information could be retrieved from Moodle.

Result: Passed.

The backend uses the Moodle `core_course_get_courses` web service function.

### Learning Outcome Retrieval

The `/moodle/course/<course_id>/outcomes` endpoint was tested to verify that learning outcomes could be retrieved.

Result: Passed.

The integration uses the Moodle outcome service and supports the learning outcome scale:

- Not Yet Achieved
- Partially Achieved
- Achieved

### Assignment and Grade Retrieval

Assignment and grade retrieval were tested through:

- `/moodle/course/<course_id>/assignments`
- `/moodle/assignment/<assignment_id>/grades`

Result: Passed.

The Moodle client supports the relevant assignment and grade web service functions.

### Backend Subject API

The `/subjects` endpoint was tested to verify that subject information could be provided to the frontend.

Result: Passed.

The API returns:

- subject ID
- subject code
- subject name

### Backend Assessment API

The `/assessments` endpoint was tested to verify that assessment information was available for dashboard processing.

Result: Passed.

The returned assessment data includes:

- student ID
- subject ID
- feedback
- grade
- mastery estimate
- identified gap

### Student Learning Summary

The student summary endpoint was tested to verify that strengths, gaps and assessment counts could be generated.

Result: Passed.

### Frontend Data Processing

The `useDashboardData.js` hook was evaluated to verify that backend data was transformed correctly for dashboard presentation.

Result: Passed.

The processing includes:

- matching assessments with subjects
- calculating mastery percentages
- calculating average mastery
- identifying subjects on track
- identifying flagged gaps
- identifying strengths
- identifying learning gaps

### Dashboard Display

The React dashboard components were evaluated to verify that processed information was displayed correctly.

Result: Passed.

The tested dashboard functionality included:

- current understanding
- strengths
- skill gaps
- progress trends

### Error Handling

Error handling was also evaluated.

Result: Passed.

The frontend provides loading and error states when API requests fail, while the backend returns error responses when integration calls fail.

## 3. Overall Evaluation

The integration testing indicates that the main application pipeline is functioning across the major system components.

The system can retrieve academic information, expose it through backend APIs, process assessment information and present student learning information through the dashboard.

The testing therefore demonstrates that the project has progressed beyond an isolated prototype and provides an integrated foundation for the Learning Journey Assistant.

## 4. Discussion

The testing demonstrates that the separation between Moodle, the Flask backend and React frontend provides a clear system architecture.

The Moodle client isolates Moodle-specific API calls from the rest of the backend. The Flask API provides a consistent interface for the frontend, while the React data-processing layer converts backend responses into information suitable for dashboard visualisation.

The dashboard therefore does not need to understand the underlying Moodle API structure directly.

The testing also identified areas that require further development, particularly around the final mastery calculation, rubric/outcome/competency mapping and deployment using real student information.

These findings are important because successful API connectivity does not by itself guarantee that the resulting learning insights are academically valid. The quality of the mastery model depends on the consistency and correctness of the underlying assessment, rubric and learning-outcome relationships.

## 5. Evaluation Against Project Objectives

The project objectives were evaluated against the implemented functionality.

| Objective | Evaluation |
|---|---|
| Integrate with Moodle | Demonstrated through Moodle REST API integration |
| Retrieve assessment information | Demonstrated through assignment and grade endpoints |
| Connect assessment information to learning data | Partially demonstrated, with further mapping required |
| Estimate student mastery | Demonstrated through the mastery processing component |
| Identify strengths and gaps | Demonstrated through dashboard data processing |
| Visualise student progress | Demonstrated through React dashboard components |
| Provide an integrated system foundation | Demonstrated through end-to-end integration testing |

## 6. Testing Conclusion

The testing results indicate that the major system components can communicate and perform their intended functions within the current implementation.

The project provides a functional foundation for personalised learning support while identifying clear areas for further refinement before production use with real student data.
