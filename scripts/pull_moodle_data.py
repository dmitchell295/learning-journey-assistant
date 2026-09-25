"""Read LJA's live Moodle API. Credentials come only from environment variables."""
import argparse
import json
import os
from pathlib import Path
import urllib.parse
import urllib.request

DEFAULT_URL = 'https://lja-moodle.australiaeast.cloudapp.azure.com/webservice/rest/server.php'


def call_moodle(function, params=None):
    token = os.environ.get('LJA_TOKEN', '')
    if not token:
        raise RuntimeError('Set LJA_TOKEN before running this script.')
    data = dict(params or {})
    data.update(wstoken=token, wsfunction=function, moodlewsrestformat='json')
    request = urllib.request.Request(os.environ.get('LJA_MOODLE_URL', DEFAULT_URL),
                                    data=urllib.parse.urlencode(data).encode())
    with urllib.request.urlopen(request, timeout=60) as response:
        result = json.load(response)
    if isinstance(result, dict) and 'exception' in result:
        raise RuntimeError('{}: {}'.format(function, result.get('errorcode', 'Moodle error')))
    return result


def collect():
    discovery = call_moodle('mod_assign_get_assignments')
    result = {'assignments': discovery, 'courses': []}
    for course in discovery.get('courses', []):
        cid = course['id']
        entry = {'courseid': cid, 'outcomes': call_moodle(
            'local_ljaoutcomes_get_course_outcomes', {'courseid': cid}), 'assignments': [], 'grade_reports': {}}
        students = set()
        for assignment in course.get('assignments', []):
            aid, cmid = assignment['id'], assignment['cmid']
            grades = call_moodle('mod_assign_get_grades', {'assignmentids[0]': aid, 'since': 0})
            for group in grades.get('assignments', []):
                students.update(g['userid'] for g in group.get('grades', []))
            definitions = call_moodle('core_grading_get_definitions',
                {'cmids[0]': cmid, 'areaname': 'submissions', 'activeonly': 1})
            instances = {}
            for area in definitions.get('areas', []):
                for definition in area.get('definitions', []):
                    if definition.get('method') == 'rubric':
                        instances[str(definition['id'])] = call_moodle(
                            'core_grading_get_gradingform_instances', {'definitionid': definition['id'], 'since': 0})
            entry['assignments'].append({'assignmentid': aid, 'cmid': cmid, 'grades': grades,
                'submissions': call_moodle('mod_assign_get_submissions', {'assignmentids[0]': aid}),
                'definitions': definitions, 'instances_by_definition': instances})
        # Grade records identify graded students; this is not a full enrolment list.
        for uid in sorted(students):
            entry['grade_reports'][str(uid)] = call_moodle('gradereport_user_get_grade_items',
                                                        {'courseid': cid, 'userid': uid})
        result['courses'].append(entry)
        print('Read course {}'.format(cid))
    return result


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', default='moodle_data.json')
    args = parser.parse_args()
    try:
        result = collect()
    except Exception as exc:
        # Avoid printing request objects/URLs that may carry authentication data.
        raise SystemExit('Pull failed ({}). Check token, permissions and connectivity; no output written.'.format(type(exc).__name__))
    Path(args.output).write_text(json.dumps(result, indent=2, ensure_ascii=False), encoding='utf-8')
    print('Saved {}. Review API warnings retained in the JSON.'.format(args.output))


if __name__ == '__main__':
    main()
