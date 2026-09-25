# LJA course outcomes API
Targets Moodle 3.6.2 with PHP 7.3. Install the ljaoutcomes folder under local/ via the Moodle plugin installer, then run the Moodle upgrade step. Add local_ljaoutcomes_get_course_outcomes to the existing restricted LJA Service. No new service or token is created automatically.

POST to the existing HTTPS /webservice/rest/server.php endpoint with wstoken, wsfunction=local_ljaoutcomes_get_course_outcomes, moodlewsrestformat=json and courseid=9. Never commit tokens.

Checks Moodle external context access, moodle/course:view and gradereport/user:view. Returns only outcomes explicitly linked through grade_outcomes_courses, with their stored descriptions and scale labels. It does not create outcome links or infer rubric-to-outcome mappings, student achievement or mastery. Descriptions can contain HTML; render safely.

No database tables, writes, scheduled tasks or personal-data storage. Validation on the target site is still required: course 9 should return outcome IDs 29–32; check all eight project courses and verify a token user without course access is denied. Grade results and assignments must remain unchanged.

Source reference: https://moodledev.io/docs/4.1/apis/subsystems/external/writing-a-service (legacy classname/methodname/classpath registration).
