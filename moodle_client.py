"""
moodle_client.py - thin wrapper around the Moodle REST web service.

Covers CBLS-62 (core connection) and the data needed for CBLS-63/64
(courses/grades, rubrics/outcomes). Every function here maps to one
confirmed-available Moodle web service function - see CBLS-62 notes
for the full list core_webservice_get_site_info returned.
"""

import os
import requests

MOODLE_URL = os.environ["MOODLE_URL"].rstrip("/")
MOODLE_TOKEN = os.environ["MOODLE_TOKEN"]
ENDPOINT = f"{MOODLE_URL}/webservice/rest/server.php"


def _call(function_name, **params):
    """Low-level call to any Moodle web service function."""
    payload = {
        "wstoken": MOODLE_TOKEN,
        "wsfunction": function_name,
        "moodlewsrestformat": "json",
        **params,
    }
    response = requests.get(ENDPOINT, params=payload, timeout=10)
    response.raise_for_status()
    data = response.json()

    if isinstance(data, dict) and "exception" in data:
        raise MoodleAPIError(f"{function_name} failed: {data.get('message')}")

    return data


class MoodleAPIError(Exception):
    pass


def get_site_info():
    return _call("core_webservice_get_site_info")


def get_courses():
    return _call("core_course_get_courses")


def get_assignments(course_ids):
    """course_ids: list of course IDs."""
    params = {}
    for i, cid in enumerate(course_ids):
        params[f"courseids[{i}]"] = cid
    return _call("mod_assign_get_assignments", **params)


def get_submissions(assignment_ids):
    params = {}
    for i, aid in enumerate(assignment_ids):
        params[f"assignmentids[{i}]"] = aid
    return _call("mod_assign_get_submissions", **params)


def get_grades(assignment_ids):
    params = {}
    for i, aid in enumerate(assignment_ids):
        params[f"assignmentids[{i}]"] = aid
    return _call("mod_assign_get_grades", **params)


def get_grade_items(course_id):
    return _call("gradereport_user_get_grade_items", courseid=course_id)


def get_grading_definitions(area_ids):
    """area_ids: list of grading area IDs (rubrics live under Moodle's generic grading API)."""
    params = {}
    for i, aid in enumerate(area_ids):
        params[f"areaids[{i}]"] = aid
    return _call("core_grading_get_definitions", **params)


def get_course_outcomes(course_id):
    """Custom plugin - LJA-specific learning outcomes for a course."""
    return _call("local_ljaoutcomes_get_course_outcomes", courseid=course_id)